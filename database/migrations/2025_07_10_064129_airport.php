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
        Schema::create('airports', function (Blueprint $table) {
            $table->id('id_airport');
            $table->string('name_airport')->unique();
            $table->string('code_airport')->unique();
            $table->unsignedBigInteger('id_country');
            $table->timestamps();
            $table->boolean('status')->default(true);
            $table->unsignedBigInteger('created_by');
            $table->softDeletes();
        });

        Schema::create('log_airport', function (Blueprint $table) {
            $table->id('id_logairport');
            $table->unsignedBigInteger('id_airport');
            $table->text('action');
            $table->unsignedBigInteger('id_user');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('airports');
    }
};
