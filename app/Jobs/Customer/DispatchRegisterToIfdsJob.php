<?php

declare(strict_types=1);

namespace App\Jobs\Customer;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Thin dispatcher — pushes a registration payload onto the shared Redis queue
 * that the ifds worker consumes as RegisterCustomerFromBffJob.
 *
 * The job class name MUST match the ifds side exactly so Laravel's queue
 * worker can deserialise and route it correctly.
 */
class DispatchRegisterToIfdsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string  $bffCustomerId,
        public readonly string  $mobile,
        public readonly string  $name,
        public readonly ?string $email,
        public readonly ?string $companyName,
        public readonly ?string $gstin,
    ) {}

    /**
     * This job itself just carries the payload onto the correct queue.
     * The actual handle() runs on the ifds side.
     *
     * Push via: DispatchRegisterToIfdsJob::dispatch(...)->onQueue('bff_customer');
     */
    public function handle(): void
    {
        // Intentionally empty on the BFF side.
        // This job is only serialised here and deserialised on the ifds worker.
    }
}
