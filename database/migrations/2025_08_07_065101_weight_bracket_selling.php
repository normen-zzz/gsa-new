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
        Schema::create('weight_bracket_selling', function (Blueprint $table) {
            $table->id('id_weight_bracket_selling');
            $table->decimal('min_weight', 10, 2);
            $table->integer('created_by')->unsigned();
            $table->integer('updated_by')->unsigned()->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->integer('deleted_by')->nullable()->unsigned();
            $table->enum('status', ['active', 'inactive'])->default('active');
        });

        Schema::create('log_weight_bracket_selling', function (Blueprint $table) {
            $table->id('id_log_weight_bracket_selling');
            $table->integer('id_weight_bracket_selling')->unsigned();
            $table->json('action');
            $table->integer('id_user')->unsigned();
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
