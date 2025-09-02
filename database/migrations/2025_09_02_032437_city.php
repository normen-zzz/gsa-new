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
        Schema::create('city', function (Blueprint $table) {
            $table->id('id_city');
            $table->unsignedBigInteger('id_country');
            $table->string('name_city')->unique();
            $table->timestamps();
            $table->boolean('status')->default(true);
            $table->unsignedBigInteger('created_by');
            $table->softDeletes();
            $table->unsignedBigInteger('deleted_by')->nullable();
        });

        Schema::create('log_city', function (Blueprint $table) {
            $table->id('id_logcity');
            $table->unsignedBigInteger('id_city');
            $table->json('action');
            $table->unsignedBigInteger('id_user');
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
