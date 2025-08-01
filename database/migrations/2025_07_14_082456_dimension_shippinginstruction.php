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
        Schema::create('dimension_shippinginstruction', function (Blueprint $table) {
            $table->id('id_dimension');
            $table->unsignedBigInteger('id_shippinginstruction')->nullable();
            $table->decimal('pieces')->nullable();
            $table->decimal('length')->nullable();
            $table->decimal('width')->nullable();
            $table->decimal('height')->nullable();
            $table->decimal('weight')->nullable();
            $table->integer('created_by')->unsigned();
            // remarks 
            $table->text('remarks')->nullable();
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
        Schema::dropIfExists('dimension_shippinginstruction');
        // --- IGNORE ---
    }
};
