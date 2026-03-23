<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'customer';

    public function up(): void
    {
        Schema::connection('customer')->create('customer_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(\Illuminate\Support\Facades\DB::raw('gen_random_uuid()'));
            $table->uuid('customer_id');
            $table->foreign('customer_id')->references('id')->on('customer_accounts')->onDelete('cascade');
            $table->string('token_hash', 64); // SHA256 of JWT for revocation
            $table->jsonb('device_info')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->timestamp('last_active_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('created_at')->useCurrent();

            $table->index('token_hash');
            $table->index('customer_id');
        });
    }

    public function down(): void
    {
        Schema::connection('customer')->dropIfExists('customer_sessions');
    }
};
