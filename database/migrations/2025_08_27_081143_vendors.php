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
        Schema::create('vendors', function (Blueprint $table) {
            $table->id('id_vendor');
            $table->string('name_vendor')->unique();
            $table->timestamps();
            $table->boolean('status')->default(true);
            $table->integer('created_by')->unsigned();
           
            $table->softDeletes();
            $table->integer('deleted_by')->unsigned()->nullable();
        });

        Schema::create('data_vendor', function (Blueprint $table) {
            $table->id('id_datavendor');
            $table->integer('id_vendor')->unsigned();
            $table->bigInteger('account_number')->nullable();
            $table->string('bank')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->string('pic')->nullable();
            $table->softDeletes();
            $table->integer('created_by')->unsigned();
            $table->integer('deleted_by')->unsigned()->nullable();
            $table->timestamps();
        });



        Schema::create('log_vendor', function (Blueprint $table) {
            $table->id('id_logvendor');
            $table->integer('id_vendor')->unsigned();
            $table->json('action');
            $table->integer('id_user')->unsigned();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        
    }
};
