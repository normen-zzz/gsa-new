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
        Schema::create('job', function (Blueprint $table) {
            $table->id('id_job');
            $table->unsignedBigInteger('id_shippinginstruction')->nullable();
            $table->unsignedBigInteger('id_awb')->nullable();
            $table->unsignedBigInteger('id_hawb')->nullable();
            $table->string('job_number')->unique();
            $table->date('job_date')->nullable();
            $table->text('description')->nullable();
            $table->decimal('weight', 8, 2)->nullable();
            $table->decimal('volume', 8, 2)->nullable();
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
        //
    }
};
