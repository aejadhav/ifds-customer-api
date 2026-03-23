<?php

declare(strict_types=1);

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    public function sendOtp(string $mobile, string $otp): bool
    {
        $provider = config('services.sms.provider', 'log');

        if ($provider === 'log' || app()->environment('local', 'testing')) {
            Log::info("OTP for {$mobile}: {$otp}");
            return true;
        }

        // 2Factor.in
        $apiKey = config('services.sms.twofactor_api_key');
        $response = Http::get("https://2factor.in/API/V1/{$apiKey}/SMS/{$mobile}/{$otp}/OTP1");

        return $response->successful() && $response->json('Status') === 'Success';
    }
}
