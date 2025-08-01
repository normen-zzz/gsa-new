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
         Schema::create('flight_awb', function (Blueprint $table) {
            $table->id('id_flightawb');
            $table->unsignedBigInteger('id_awb');
            $table->string('flight_number', 20);
            $table->dateTime('departure');
            $table->string('departure_timezone', 50);
            $table->dateTime('arrival');
            $table->string('arrival_timezone', 50);
            $table->integer('created_by')->unsigned();
            $table->timestamps();
            $table->softDeletes();
            
           
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
