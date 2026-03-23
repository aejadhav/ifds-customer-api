<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'customer';

    public function up(): void
    {
        // Change from uuid to bigint to match ifds customers.id (bigIncrements)
        DB::connection('customer')->statement(
            'ALTER TABLE customer_accounts ALTER COLUMN ifds_customer_id TYPE bigint USING NULL'
        );
    }

    public function down(): void
    {
        DB::connection('customer')->statement(
            'ALTER TABLE customer_accounts ALTER COLUMN ifds_customer_id TYPE uuid USING NULL'
        );
    }
};
