<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'customer';

    public function up(): void
    {
        Schema::connection('customer')->create('customer_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(\Illuminate\Support\Facades\DB::raw('gen_random_uuid()'));
            $table->string('mobile', 15)->unique();
            $table->string('email')->unique()->nullable();
            $table->string('name');
            $table->string('company_name')->nullable();
            $table->string('gstin', 20)->nullable();
            $table->enum('status', ['pending', 'active', 'suspended'])->default('pending');
            $table->boolean('ifds_synced')->default(false);
            $table->uuid('ifds_customer_id')->nullable(); // set after RegisterCustomerJob succeeds
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('customer')->dropIfExists('customer_accounts');
    }
};
