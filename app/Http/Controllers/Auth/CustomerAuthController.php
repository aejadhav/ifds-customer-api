<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Jobs\Customer\DispatchRegisterToIfdsJob;
use App\Models\CustomerDb\CustomerAccount;
use App\Models\CustomerDb\CustomerProfile;
use App\Services\Auth\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class CustomerAuthController extends Controller
{
    public function __construct(private OtpService $otpService) {}

    // POST /auth/register
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'         => 'nullable|string|max:255',
            'mobile'       => 'required|string|size:10|unique:customer.customer_accounts,mobile',
            'email'        => 'nullable|email|unique:customer.customer_accounts,email',
            'company_name' => 'nullable|string|max:255',
            'gstin'        => 'nullable|string|max:20',
        ]);

        $customer = DB::connection('customer')->transaction(function () use ($data) {
            $account = CustomerAccount::create([
                'id'           => Str::uuid()->toString(),
                'mobile'       => $data['mobile'],
                'email'        => $data['email'] ?? null,
                'name'         => $data['name'] ?? $data['mobile'],
                'company_name' => $data['company_name'] ?? null,
                'gstin'        => $data['gstin'] ?? null,
                'status'       => 'pending',
            ]);

            CustomerProfile::create(['customer_id' => $account->id]);

            return $account;
        });

        // Dispatch registration to ifds for customer record creation + sync-back
        DispatchRegisterToIfdsJob::dispatch(
            bffCustomerId: $customer->id,
            mobile:        $customer->mobile,
            name:          $customer->name,
            email:         $customer->email,
            companyName:   $customer->company_name,
            gstin:         $customer->gstin,
        )->onQueue(config('services.bff.ifds_queue', 'bff_customer'));

        return response()->json([
            'success'     => true,
            'message'     => 'Registration received. You will receive an OTP to verify your mobile.',
            'customer_id' => $customer->id,
        ], 202);
    }

    // POST /auth/send-otp
    public function sendOtp(Request $request): JsonResponse
    {
        $request->validate(['mobile' => 'required|string|size:10']);

        $mobile = $request->mobile;
        $ip     = $request->ip();

        // Check if customer exists
        $exists = CustomerAccount::where('mobile', $mobile)->exists();
        if (!$exists) {
            return response()->json([
                'success' => false,
                'message' => 'No account found with this mobile number.',
            ], 404);
        }

        $result = $this->otpService->send($mobile, $ip);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
        ], $result['success'] ? 200 : 429);
    }

    // POST /auth/verify-otp
    public function verifyOtp(Request $request): JsonResponse
    {
        $request->validate([
            'mobile' => 'required|string|size:10',
            'otp'    => 'required|string|size:6',
        ]);

        $result = $this->otpService->verify($request->mobile, $request->otp, $request->ip());

        if (!$result['success']) {
            return response()->json(['success' => false, 'message' => $result['message']], 401);
        }

        $customer = CustomerAccount::where('mobile', $request->mobile)->firstOrFail();

        if ($customer->status === 'suspended') {
            return response()->json(['success' => false, 'message' => 'Account suspended. Contact support.'], 403);
        }

        $token = JWTAuth::fromUser($customer);

        return response()->json([
            'success'      => true,
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => config('jwt.ttl') * 60,
            'customer'     => [
                'id'            => $customer->id,
                'name'          => $customer->name,
                'mobile'        => $customer->mobile,
                'email'         => $customer->email,
                'company_name'  => $customer->company_name,
                'status'        => $customer->status,
                'ifds_synced'   => $customer->ifds_synced,
            ],
        ]);
    }

    // POST /auth/refresh
    public function refresh(): JsonResponse
    {
        $token = JWTAuth::refresh(JWTAuth::getToken());
        return response()->json([
            'success'      => true,
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => config('jwt.ttl') * 60,
        ]);
    }

    // POST /auth/logout
    public function logout(): JsonResponse
    {
        JWTAuth::invalidate(JWTAuth::getToken());
        return response()->json(['success' => true, 'message' => 'Logged out successfully.']);
    }

    // GET /auth/me
    public function me(Request $request): JsonResponse
    {
        $customer = $request->user();
        return response()->json([
            'success' => true,
            'data'    => [
                'id'            => $customer->id,
                'name'          => $customer->name,
                'mobile'        => $customer->mobile,
                'email'         => $customer->email,
                'company_name'  => $customer->company_name,
                'status'        => $customer->status,
                'ifds_synced'   => $customer->ifds_synced,
                'ifds_customer_id' => $customer->ifds_customer_id,
            ],
        ]);
    }
}
