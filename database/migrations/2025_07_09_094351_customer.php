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
            $table->enum('type', ['agent', 'consignee'])->default('agent');
            $table->boolean('status')->default(true);
            $table->integer('created_by')->unsigned();
           
            $table->softDeletes();
            $table->integer('deleted_by')->unsigned()->nullable();
        });

        Schema::create('data_customer', function (Blueprint $table) {
            $table->id('id_datacustomer');
            $table->integer('id_customer')->unsigned();
            $table->json('data')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->softDeletes();
            $table->integer('created_by')->unsigned();
            $table->integer('deleted_by')->unsigned()->nullable();
            $table->timestamps();
        });



        Schema::create('log_customer', function (Blueprint $table) {
            $table->id('id_logcustomer');
            $table->integer('id_customer')->unsigned();

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
