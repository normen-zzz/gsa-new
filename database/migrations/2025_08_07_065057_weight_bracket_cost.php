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
        Schema::create('weight_bracket_costs', function (Blueprint $table) {
            $table->id('id_weight_bracket_cost');
            $table->decimal('min_weight');
            $table->timestamps();
            $table->softDeletes();
            $table->integer('created_by')->unsigned();
            $table->integer('updated_by')->unsigned()->nullable();
            $table->integer('deleted_by')->nullable()->unsigned();
            
            $table->enum('status', ['active', 'inactive'])->default('active');
          
        });
        Schema::create('log_weight_bracket_costs', function (Blueprint $table) {
            $table->id('id_log_weight_bracket_cost');
            $table->unsignedBigInteger('id_weight_bracket_cost');
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
        Schema::dropIfExists('weight_bracket_costs');
    }
};
