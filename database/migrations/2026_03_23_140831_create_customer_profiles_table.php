<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'customer';

    public function up(): void
    {
        Schema::connection('customer')->create('customer_profiles', function (Blueprint $table) {
            $table->uuid('customer_id')->primary();
            $table->foreign('customer_id')->references('id')->on('customer_accounts')->onDelete('cascade');
            $table->boolean('notify_order_confirmed')->default(true);
            $table->boolean('notify_order_dispatched')->default(true);
            $table->boolean('notify_out_for_delivery')->default(true);
            $table->boolean('notify_order_delivered')->default(true);
            $table->boolean('notify_invoice_generated')->default(true);
            $table->boolean('notify_payment_due')->default(true);
            $table->boolean('notify_credit_80')->default(true);
            $table->boolean('notify_credit_exceeded')->default(true);
            $table->string('preferred_language', 10)->default('en');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::connection('customer')->dropIfExists('customer_profiles');
    }
};
