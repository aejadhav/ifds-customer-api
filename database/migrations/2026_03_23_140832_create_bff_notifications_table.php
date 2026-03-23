<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'customer';

    public function up(): void
    {
        Schema::connection('customer')->create('bff_notifications', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(\Illuminate\Support\Facades\DB::raw('gen_random_uuid()'));
            $table->uuid('customer_id');
            $table->foreign('customer_id')->references('id')->on('customer_accounts')->onDelete('cascade');
            $table->string('type', 50); // order_confirmed, payment_received, etc.
            $table->string('title');
            $table->text('body')->nullable();
            $table->jsonb('data')->nullable(); // order_id, invoice_id, etc.
            $table->timestamp('read_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['customer_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::connection('customer')->dropIfExists('bff_notifications');
    }
};
