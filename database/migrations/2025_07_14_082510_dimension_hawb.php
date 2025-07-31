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
        Schema::create('dimension_hawb', function (Blueprint $table) {
            $table->id('id_dimensionhawb');
            $table->unsignedBigInteger('id_hawb');
            $table->decimal('length');
            $table->decimal('width');
            $table->decimal('height');
            $table->decimal('weight');
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
        Schema::dropIfExists('dimension_hawb');
       
    }
};
