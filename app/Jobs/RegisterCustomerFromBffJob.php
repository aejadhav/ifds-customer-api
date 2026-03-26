<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Stub class — exists only so the BFF can serialise a job payload with the
 * correct class name (App\Jobs\RegisterCustomerFromBffJob) that the IFDS
 * queue worker can deserialise and execute.
 *
 * handle() is intentionally empty — this job is never executed on the BFF side.
 */
class RegisterCustomerFromBffJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string  $bffCustomerId,
        public readonly string  $mobile,
        public readonly string  $name,
        public readonly ?string $email       = null,
        public readonly ?string $companyName = null,
        public readonly ?string $gstin       = null,
    ) {}

    public function handle(): void
    {
        // Intentionally empty — executed on IFDS side only.
    }
}
