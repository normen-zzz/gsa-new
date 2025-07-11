<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id('id_customer');
            $table->string('name_customer')->unique();
            $table->timestamps();
            $table->boolean('status')->default(true);
            $table->integer('created_by')->unsigned();
            
            
        });

        Schema::create('customer_details', function (Blueprint $table) {
            $table->id('id_customerdetail');
            $table->integer('id_customer')->unsigned();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address');
            $table->string('tax_id')->nullable();
            $table->string('pic')->nullable();
            $table->timestamps();
            $table->datetime('deleted_at')->nullable();
        });

        Schema::create('log_customer', function (Blueprint $table) {
            $table->id('id_logcustomer');
            $table->integer('id_customer')->unsigned();
            $table->integer('id_customerdetail')->unsigned()->nullable();
            $table->text('action');
            $table->integer('id_user')->unsigned();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
