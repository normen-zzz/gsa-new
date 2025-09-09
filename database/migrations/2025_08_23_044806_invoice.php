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
        Schema::create('invoice', function (Blueprint $table) {
            $table->id('id_invoice');
            $table->unsignedBigInteger('agent');
            $table->unsignedBigInteger('data_agent');
            $table->string('no_invoice')->nullable()->unique();
            $table->date('invoice_date');
            $table->date('due_date');
            $table->text('remarks')->nullable();
            $table->integer('id_datacompany')->unsigned();
            $table->integer('created_by')->unsigned();
            $table->integer('updated_by')->unsigned();
            $table->softDeletes();
            $table->integer('deleted_by')->nullable();
            $table->timestamps();
            $table->enum('status', [
                'invoice_created',
                'invoice_sent',
                'invoice_paid',
                'invoice_cancelled'
            ])->default('invoice_created');
        });

        Schema::create('detail_invoice', function (Blueprint $table) {
            $table->id('id_detail_invoice');
            $table->unsignedBigInteger('id_invoice');
             $table->unsignedBigInteger('id_salesorder');
            $table->unsignedBigInteger('id_jobsheet');
            $table->unsignedBigInteger('id_awb');
            $table->timestamps();
            

        });

          Schema::create('listothercharge_invoice', function (Blueprint $table) {
            $table->id('id_listothercharge_invoice');
            $table->string('name');
            $table->enum('type', ['percentage_subtotal','multiple_chargeableweight','multiple_grossweight','nominal']);
            $table->timestamps();
            $table->integer('created_by')->unsigned();
            $table->softDeletes();
            $table->integer('deleted_by')->nullable();
        });

        Schema::create('otherscharge_invoice', function (Blueprint $table) {
            $table->id('id_otherscharge_invoice');
            $table->unsignedBigInteger('id_listothercharge_invoice');
            $table->unsignedBigInteger('id_invoice');
            $table->decimal('amount', 15, 2)->nullable();
            $table->timestamps();
        });

         Schema::create('approval_invoice', function (Blueprint $table) {
            $table->id('id_approval_invoice');
            $table->unsignedBigInteger('id_invoice');
            $table->unsignedBigInteger('approval_position');
            $table->unsignedBigInteger('approval_division');
            $table->integer('step_no');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('remarks')->nullable();
            $table->datetime('approved_at')->nullable();
            $table->integer('approved_by')->nullable();
            $table->timestamps();
            $table->integer('created_by')->unsigned();
        });

        Schema::create('flowapproval_invoice', function (Blueprint $table) {
            $table->id('id_flowapproval_invoice');
            $table->unsignedBigInteger('request_position');
            $table->unsignedBigInteger('request_division');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->integer('created_by')->unsigned();
            $table->timestamps();
        });

        Schema::create('detailflowapproval_invoice', function (Blueprint $table) {
            $table->id('id_detailflowapproval_invoice');
            $table->unsignedBigInteger('id_flowapproval_invoice');
            $table->unsignedBigInteger('approval_position');
            $table->unsignedBigInteger('approval_division');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->integer('step_no');
            $table->integer('created_by')->unsigned();
            $table->timestamps();
        });

        Schema::create('log_flowapproval_invoice', function (Blueprint $table) {
            $table->id('id_log_flowapproval_invoice');
            $table->unsignedBigInteger('id_flowapproval_invoice');
            $table->json('action');
            $table->integer('created_by')->unsigned();
            $table->timestamps();
        });

        Schema::create('log_invoice', function (Blueprint $table) {
            $table->id('id_log_invoice');
            $table->unsignedBigInteger('id_invoice');
            $table->json('action');
            $table->integer('created_by')->unsigned();
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
