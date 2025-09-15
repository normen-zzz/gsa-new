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
        Schema::create('dimension_awb', function (Blueprint $table) {
            $table->id('id_dimensionawb');
            $table->unsignedBigInteger('id_awb');
            $table->decimal('pieces')->nullable();
            $table->decimal('length')->nullable();
            $table->decimal('width')->nullable();
            $table->decimal('height')->nullable();
            $table->decimal('weight')->nullable();
            $table->enum('type_weight',['total', 'per_piece'])->default('per_piece');
            $table->text('remarks')->nullable();
            $table->integer('created_by')->unsigned();
            $table->softDeletes();
            $table->integer('deleted_by')->nullable();
            $table->timestamps();
            $table->boolean('is_taken')->default(false);
            $table->integer('updated_by')->unsigned()->nullable();
            $table->enum('status_flight', ['pending', 'on_board', 'delivered'])->default('pending');
        });

        Schema::create('statusflight_dimensionawb', function (Blueprint $table) {
            $table->id('id_statusflight');
            $table->unsignedBigInteger('id_dimensionawb');
            $table->enum('status', ['pending', 'on_board', 'delivered'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dimension_awb');
    }
};
