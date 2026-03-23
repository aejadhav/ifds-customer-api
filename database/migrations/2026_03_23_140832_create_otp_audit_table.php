<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'customer';

    public function up(): void
    {
        Schema::connection('customer')->create('otp_audit', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(\Illuminate\Support\Facades\DB::raw('gen_random_uuid()'));
            $table->string('mobile', 15);
            $table->enum('action', ['sent', 'verified', 'failed', 'expired', 'locked']);
            $table->ipAddress('ip_address')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['mobile', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::connection('customer')->dropIfExists('otp_audit');
    }
};
