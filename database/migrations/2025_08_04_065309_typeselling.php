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
        Schema::create('typeselling', function (Blueprint $table) {
            $table->id('id_typeselling');
            $table->string('initials')->unique();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->integer('created_by')->unsigned();
            $table->softDeletes();
            $table->integer('deleted_by')->nullable();
            $table->timestamps();
            $table->integer('updated_by')->unsigned()->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
        });

        Schema::create('log_typeselling', function (Blueprint $table) {
            $table->id('id_logtypeselling');
            $table->unsignedBigInteger('id_typeselling');
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
        Schema::dropIfExists('typeselling');
    }
};
