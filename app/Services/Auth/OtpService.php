<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\CustomerDb\OtpAudit;
use App\Services\Sms\SmsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class OtpService
{
    private const OTP_TTL_SECONDS    = 300;  // 5 minutes
    private const MAX_ATTEMPTS       = 5;
    private const LOCKOUT_SECONDS    = 900;  // 15 minutes
    private const RESEND_COOLDOWN    = 60;   // 1 minute

    public function __construct(private SmsService $sms) {}

    public function send(string $mobile, string $ip): array
    {
        // Check lockout
        if (Cache::get("otp_locked:{$mobile}")) {
            return ['success' => false, 'message' => 'Too many failed attempts. Try again in 15 minutes.'];
        }

        // Check resend cooldown (stored as Unix timestamp to avoid Carbon serialisation issues)
        $sentAt = Cache::get("otp_sent_at:{$mobile}");
        if ($sentAt && (time() - (int) $sentAt) < self::RESEND_COOLDOWN) {
            $wait = self::RESEND_COOLDOWN - (time() - (int) $sentAt);
            return ['success' => false, 'message' => "Please wait {$wait} seconds before requesting a new OTP."];
        }

        $otp = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        Cache::put("otp:{$mobile}", $otp, self::OTP_TTL_SECONDS);
        Cache::put("otp_sent_at:{$mobile}", time(), self::RESEND_COOLDOWN + 10);
        Cache::forget("otp_attempts:{$mobile}");

        $this->logAudit($mobile, 'sent', $ip);
        $this->sms->sendOtp($mobile, $otp);

        return ['success' => true, 'message' => 'OTP sent successfully.'];
    }

    public function verify(string $mobile, string $otp, string $ip): array
    {
        if (Cache::get("otp_locked:{$mobile}")) {
            return ['success' => false, 'message' => 'Account locked. Try again in 15 minutes.'];
        }

        $stored = Cache::get("otp:{$mobile}");

        if (!$stored) {
            $this->logAudit($mobile, 'expired', $ip);
            return ['success' => false, 'message' => 'OTP expired. Please request a new one.'];
        }

        if (!hash_equals($stored, $otp)) {
            $attempts = Cache::increment("otp_attempts:{$mobile}");
            $this->logAudit($mobile, 'failed', $ip);

            if ($attempts >= self::MAX_ATTEMPTS) {
                Cache::put("otp_locked:{$mobile}", true, self::LOCKOUT_SECONDS);
                Cache::forget("otp:{$mobile}");
                $this->logAudit($mobile, 'locked', $ip);
                return ['success' => false, 'message' => 'Too many failed attempts. Account locked for 15 minutes.'];
            }

            $remaining = self::MAX_ATTEMPTS - $attempts;
            return ['success' => false, 'message' => "Invalid OTP. {$remaining} attempts remaining."];
        }

        // Success — clear everything
        Cache::forget("otp:{$mobile}");
        Cache::forget("otp_attempts:{$mobile}");
        $this->logAudit($mobile, 'verified', $ip);

        return ['success' => true];
    }

    private function logAudit(string $mobile, string $action, string $ip): void
    {
        \DB::connection('customer')->table('otp_audit')->insert([
            'id'         => Str::uuid()->toString(),
            'mobile'     => $mobile,
            'action'     => $action,
            'ip_address' => $ip,
            'created_at' => now(),
        ]);
    }
}
