<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('awb', function (Blueprint $table) {
            $table->id('id_awb');
            $table->string('awb')->unique();
            $table->date('etd')->nullable();
            $table->date('eta')->nullable();
            $table->text('handling_instructions')->nullable();
            $table->integer('created_by')->unsigned();
            $table->softDeletes();
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('awb');
    }
};
