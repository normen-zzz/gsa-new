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
            $table->integer('id_job')->unsigned();
            $table->integer('agent')->unsigned();
            $table->integer('data_agent')->unsigned();
            $table->string('consignee')->nullable();
            $table->integer('airline')->unsigned();
            $table->string('awb')->unique();
            $table->date('etd')->nullable();
            $table->date('eta')->nullable();
            $table->integer('pol')->unsigned();
            $table->integer('pod')->unsigned();
            $table->string('commodity');
            $table->integer('gross_weight')->unsigned();
            $table->integer('chargeable_weight')->unsigned();
            $table->integer('pieces')->unsigned();
            $table->text('special_instructions')->nullable();
            $table->integer('created_by')->unsigned();
            $table->softDeletes();
            $table->timestamps();
            $table->enum('status', [
                'awb_received_by_ops',
                'awb_handled_by_ops',
                'awb_declined_by_ops',
                'awb_deleted'
            ])->default('awb_received_by_ops');
            $table->integer('updated_by')->unsigned()->nullable();

        });

        Schema::create('log_awb', function (Blueprint $table) {
            $table->id('id_logawb');
            $table->unsignedBigInteger('id_awb');
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
        Schema::dropIfExists('awb');
    }
};
