<?php

declare(strict_types=1);

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Jobs\UpdateCustomerProfileFromBffJob;
use App\Models\CustomerDb\CustomerAddress;
use App\Models\IfdsReadOnly\CustomerCreditSummary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    // ── GET /v1/profile ────────────────────────────────────────────────────────

    public function show(): JsonResponse
    {
        $customer = Auth::user();
        $profile  = $customer->profile;

        $data = [
            'id'           => $customer->id,
            'name'         => $customer->name,
            'mobile'       => $customer->mobile,
            'email'        => $customer->email,
            'company_name' => $customer->company_name,
            'gstin'        => $customer->gstin,
            'status'       => $customer->status,
            'ifds_synced'  => $customer->ifds_synced,
            'preferences'  => $profile ? [
                'preferred_language'          => $profile->preferred_language,
                'notify_order_status'         => $profile->notify_order_status,
                'notify_delivery_updates'     => $profile->notify_delivery_updates,
                'notify_invoice_due'          => $profile->notify_invoice_due,
                'notify_payment_received'     => $profile->notify_payment_received,
                'notify_promotional'          => $profile->notify_promotional,
                'notify_sms'                  => $profile->notify_sms,
                'notify_email'                => $profile->notify_email,
                'notify_push'                 => $profile->notify_push,
            ] : null,
        ];

        // Append credit summary if customer is synced to ifds
        if ($customer->isSyncedToIfds()) {
            $credit = CustomerCreditSummary::find($customer->ifds_customer_id);
            if ($credit) {
                $data['credit'] = [
                    'credit_limit'       => (float) $credit->credit_limit,
                    'outstanding_balance'=> (float) $credit->outstanding_balance,
                    'available_credit'   => (float) $credit->available_credit,
                    'overdue_amount'     => (float) $credit->overdue_amount,
                    'overdue_invoices'   => (int) $credit->overdue_invoices,
                ];
            }
        }

        return response()->json(['data' => $data]);
    }

    // ── PATCH /v1/profile ──────────────────────────────────────────────────────

    public function update(Request $request): JsonResponse
    {
        $customer = Auth::user();

        $validated = $request->validate([
            'name'         => ['sometimes', 'string', 'max:255'],
            'email'        => ['sometimes', 'nullable', 'email', 'max:255'],
            'company_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'gstin'        => ['sometimes', 'nullable', 'string', 'max:20'],
        ]);

        $customer->fill($validated)->save();

        // Sync to IFDS if account is linked
        if ($customer->isSyncedToIfds()) {
            UpdateCustomerProfileFromBffJob::dispatch(
                ifdsCustomerId: $customer->ifds_customer_id,
                name:           $validated['name'] ?? null,
                email:          $validated['email'] ?? null,
                companyName:    $validated['company_name'] ?? null,
                gstin:          $validated['gstin'] ?? null,
            )->onQueue(config('services.bff.ifds_queue', 'bff_customer'));
        }

        return response()->json(['message' => 'Profile updated.', 'data' => $customer->only(['id', 'name', 'email', 'company_name', 'gstin'])]);
    }

    // ── PATCH /v1/profile/preferences ─────────────────────────────────────────

    public function updatePreferences(Request $request): JsonResponse
    {
        $customer = Auth::user();

        $validated = $request->validate([
            'preferred_language'      => ['sometimes', Rule::in(['en', 'mr', 'hi'])],
            'notify_order_status'     => ['sometimes', 'boolean'],
            'notify_delivery_updates' => ['sometimes', 'boolean'],
            'notify_invoice_due'      => ['sometimes', 'boolean'],
            'notify_payment_received' => ['sometimes', 'boolean'],
            'notify_promotional'      => ['sometimes', 'boolean'],
            'notify_sms'              => ['sometimes', 'boolean'],
            'notify_email'            => ['sometimes', 'boolean'],
            'notify_push'             => ['sometimes', 'boolean'],
        ]);

        $profile = $customer->profile;
        if ($profile) {
            $profile->fill($validated)->save();
        }

        return response()->json(['message' => 'Preferences updated.']);
    }

    // ── GET /v1/profile/addresses ──────────────────────────────────────────────

    public function addresses(): JsonResponse
    {
        $customer  = Auth::user();
        $addresses = CustomerAddress::where('customer_id', $customer->id)->orderByDesc('is_default')->get();

        return response()->json(['data' => $addresses]);
    }

    // ── POST /v1/profile/addresses ─────────────────────────────────────────────

    public function storeAddress(Request $request): JsonResponse
    {
        $customer = Auth::user();

        $validated = $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'address'         => ['required', 'string', 'max:1000'],
            'landmark'        => ['sometimes', 'nullable', 'string', 'max:255'],
            'contact_person'  => ['sometimes', 'nullable', 'string', 'max:255'],
            'contact_mobile'  => ['sometimes', 'nullable', 'string', 'max:15'],
            'is_default'      => ['sometimes', 'boolean'],
        ]);

        // If this is marked default, unset all others
        if (!empty($validated['is_default'])) {
            CustomerAddress::where('customer_id', $customer->id)
                ->update(['is_default' => false]);
        }

        $address = CustomerAddress::create(array_merge($validated, ['customer_id' => $customer->id]));

        return response()->json(['message' => 'Address added.', 'data' => $address], 201);
    }

    // ── DELETE /v1/profile/addresses/{id} ──────────────────────────────────────

    public function deleteAddress(string $id): JsonResponse
    {
        $customer = Auth::user();
        $address  = CustomerAddress::where('customer_id', $customer->id)->where('id', $id)->firstOrFail();
        $address->delete();

        return response()->json(['message' => 'Address removed.']);
    }
}
