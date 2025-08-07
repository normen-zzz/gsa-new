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
        Schema::create('cost', function (Blueprint $table) {
            $table->id('id_cost');
            $table->integer('id_weight_bracket_cost')->unsigned();
            $table->integer('id_typecost')->unsigned();
            $table->integer('id_route')->unsigned();
            $table->timestamps();
            $table->softDeletes();
            $table->integer('deleted_by')->nullable()->unsigned();
            $table->integer('created_by')->unsigned();
            $table->enum('status', ['active', 'inactive'])->default('active');
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
