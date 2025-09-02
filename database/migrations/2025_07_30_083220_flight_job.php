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
        Schema::create('flight_job', function (Blueprint $table) {
            $table->id('id_flightjob');
            $table->unsignedBigInteger('id_job');
            $table->string('flight_number', 20);
            $table->dateTime('departure');
            $table->string('departure_timezone', 50)->nullable();
            $table->dateTime('arrival');
            $table->string('arrival_timezone', 50)->nullable();
            $table->integer('created_by')->unsigned();
            $table->integer('updated_by')->unsigned()->nullable();
            $table->timestamps();
            $table->softDeletes();
           
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flight_job');
    }
};
