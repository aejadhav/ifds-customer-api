<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Stub — serialised by BFF with correct class name so IFDS worker can execute it.
 * handle() is intentionally empty.
 */
class UpdateCustomerProfileFromBffJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int     $ifdsCustomerId,
        public readonly ?string $name        = null,
        public readonly ?string $email       = null,
        public readonly ?string $companyName = null,
        public readonly ?string $gstin       = null,
    ) {}

    public function handle(): void
    {
        // Intentionally empty — executed on IFDS side only.
    }
}
