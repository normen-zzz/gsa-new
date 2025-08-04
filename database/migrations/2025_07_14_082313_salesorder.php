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
        Schema::create('salesorder', function (Blueprint $table) {
            $table->id('id_salesorder');
            $table->unsignedBigInteger('id_job')->nullable();
            $table->unsignedBigInteger('id_shippinginstruction')->nullable();
            $table->unsignedBigInteger('id_awb')->nullable();
            $table->date('date')->nullable();
            $table->text('remarks')->nullable();
            $table->integer('created_by')->unsigned();
            $table->softDeletes();
            $table->integer('deleted_by')->nullable();
            $table->timestamps();           
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salesorder');
    }
};
