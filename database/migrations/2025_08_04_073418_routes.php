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
       Schema::create('routes', function (Blueprint $table) {
            $table->id('id_route');
            $table->integer('airline')->unsigned();
            $table->integer('pol')->unsigned();
            $table->integer('pod')->unsigned();
            $table->integer('created_by')->unsigned();
            $table->softDeletes();
            $table->integer('deleted_by')->nullable();
            $table->timestamps();
            $table->integer('updated_by')->unsigned()->nullable();
            //status
            $table->enum('status', ['active', 'inactive'])->default('active');
        });
        Schema::create('log_routes', function (Blueprint $table) {
            $table->id('id_logroute');
            $table->unsignedBigInteger('id_route');
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
        Schema::dropIfExists('routes');
    }
};
