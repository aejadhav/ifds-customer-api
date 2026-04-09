<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class CircuitBreaker
{
    private const FAILURE_THRESHOLD = 5;   // open after 5 failures
    private const RECOVERY_WINDOW   = 60;  // seconds before trying again (half-open)
    private const FAILURE_TTL       = 120; // failure counter TTL

    public function isOpen(string $service): bool
    {
        return (bool) Cache::get("cb:{$service}:open");
    }

    public function recordSuccess(string $service): void
    {
        Cache::forget("cb:{$service}:open");
        Cache::forget("cb:{$service}:failures");
    }

    public function recordFailure(string $service): void
    {
        $failures = (int) Cache::get("cb:{$service}:failures", 0) + 1;
        Cache::put("cb:{$service}:failures", $failures, self::FAILURE_TTL);

        if ($failures >= self::FAILURE_THRESHOLD) {
            Cache::put("cb:{$service}:open", true, self::RECOVERY_WINDOW);
        }
    }
}
