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
        Schema::create('job', function (Blueprint $table) {
            $table->id('id_job');
            $table->unsignedBigInteger('id_shippinginstruction');
            $table->unsignedBigInteger('id_awb');
            $table->unsignedBigInteger('agent')->nullable();
            $table->unsignedBigInteger('consignee')->nullable();
            $table->date('etd');
            $table->date('eta');
            $table->timestamps();
            $table->integer('created_by')->unsigned();
            $table->softDeletes();
            $table->integer('deleted_by')->nullable();
            $table->integer('updated_by')->unsigned()->nullable();
            $table->enum('status', ['created_by_cs', 'handled_by_ops', 'declined_by_ops','deleted'])->default('created_by_cs');

        });
        Schema::create('log_job', function (Blueprint $table) {
            $table->id('id_logjob');
            $table->unsignedBigInteger('id_job');
            $table->text('action');
            $table->unsignedBigInteger('id_user');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job');
    }
};
