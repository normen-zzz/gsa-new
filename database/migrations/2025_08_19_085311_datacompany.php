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
        Schema::create('datacompany', function (Blueprint $table) {
            $table->id('id_datacompany');
            $table->string('name');
            $table->string('account_number');
            $table->string('bank');
            $table->string('branch');
            $table->string('swift')->nullable();
            $table->timestamps();
            $table->integer('created_by')->unsigned();
            $table->softDeletes();
            $table->integer('deleted_by')->nullable();
        });

        Schema::create('log_datacompany', function (Blueprint $table) {
            $table->id('id_log_datacompany');
            $table->integer('id_datacompany')->unsigned();
            $table->json('action');
            $table->timestamps();
            $table->integer('created_by')->nullable();
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
