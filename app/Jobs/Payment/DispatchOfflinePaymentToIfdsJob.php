<?php

declare(strict_types=1);

namespace App\Jobs\Payment;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Thin dispatcher — pushes an offline payment payload onto the shared Redis
 * queue that the ifds worker consumes as RecordOfflinePaymentFromBffJob.
 *
 * The job class name MUST match the ifds side exactly.
 */
class DispatchOfflinePaymentToIfdsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int     $ifdsCustomerId,
        public readonly string  $bffCustomerId,
        public readonly float   $amount,
        public readonly string  $paymentMethod,   // upi, neft, cheque, cash
        public readonly string  $paymentDate,     // Y-m-d
        public readonly ?string $transactionId,
        public readonly ?string $upiId,
        public readonly ?string $bankName,
        public readonly ?string $chequeNumber,
        public readonly ?string $notes,
        public readonly ?array  $invoiceIds,      // ifds invoice IDs to allocate against
    ) {}

    /**
     * Intentionally empty on the BFF side.
     * The actual handle() runs on the ifds worker.
     */
    public function handle(): void {}
}
