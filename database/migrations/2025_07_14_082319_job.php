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
            $table->string('awb');
            $table->unsignedBigInteger('agent');
            $table->unsignedBigInteger('data_agent');
            $table->string('consignee')->nullable();
            $table->integer('airline')->unsigned();
            $table->date('etd');
            $table->date('eta');
            $table->integer('pol');
            $table->integer('pod');
            $table->string('commodity');
            $table->decimal('gross_weight', 8, 2)->nullable();
            $table->decimal('chargeable_weight', 8, 2)->nullable();
            $table->integer('pieces')->nullable();
            $table->string('special_instructions')->nullable();
            $table->timestamps();
            $table->integer('created_by')->unsigned();
            $table->softDeletes();
            $table->integer('deleted_by')->nullable();
            $table->integer('updated_by')->unsigned()->nullable();
            $table->enum('status', [
                'job_created_by_cs',
                'job_received_by_ops',
                'job_handled_by_ops',
                'job_declined_by_ops',
                'job_deleted'
            ])->default('job_created_by_cs');
        });
        Schema::create('log_job', function (Blueprint $table) {
            $table->id('id_logjob');
            $table->unsignedBigInteger('id_job');
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
        Schema::dropIfExists('job');
    }
};
