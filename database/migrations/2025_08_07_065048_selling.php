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
        Schema::create('selling', function (Blueprint $table) {
            $table->id('id_selling');
            $table->integer('id_weight_bracket_selling')->unsigned();
            $table->integer('id_typeselling')->unsigned();
            $table->integer('id_route')->unsigned();
            $table->decimal('selling_value', 10, 2)->nullable()->comment('Value in Indonesian Rupiah (IDR)');
            $table->enum('charge_by', ['chargeable_weight', 'gross_weight','awb'])->default('chargeable_weight');
            $table->integer('created_by')->unsigned();
            $table->timestamps();
            $table->softDeletes();
            $table->integer('deleted_by')->nullable()->unsigned();
            $table->integer('updated_by')->unsigned()->nullable();  
            $table->enum('status', ['active', 'inactive'])->default('active');
        });

        Schema::create('log_selling', function (Blueprint $table) {
            $table->id('id_logselling');
            $table->unsignedBigInteger('id_selling');
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
        //
    }
};
