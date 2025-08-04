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
        Schema::create('hawb', function (Blueprint $table) {
            $table->id('id_hawb');
            $table->unsignedBigInteger('id_awb');
            $table->string('hawb_number')->unique();
            $table->integer('created_by')->unsigned();
            $table->softDeletes();
            $table->integer('deleted_by')->nullable();
            $table->timestamps();

        });

        Schema::create('log_hawb', function (Blueprint $table) {
            $table->id('id_loghawb');
            $table->unsignedBigInteger('id_hawb');
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
        Schema::dropIfExists('hawb');
    }
};
