<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'customer';

    public function up(): void
    {
        Schema::connection('customer')->create('customer_addresses', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(\Illuminate\Support\Facades\DB::raw('gen_random_uuid()'));
            $table->uuid('customer_id');
            $table->foreign('customer_id')->references('id')->on('customer_accounts')->onDelete('cascade');
            $table->string('name');
            $table->text('address');
            $table->string('landmark')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('contact_mobile', 15)->nullable();
            $table->boolean('is_default')->default(false);
            $table->integer('ifds_location_id')->nullable(); // set after AddDeliveryLocationJob
            $table->timestamps();

            $table->index('customer_id');
        });
    }

    public function down(): void
    {
        Schema::connection('customer')->dropIfExists('customer_addresses');
    }
};
