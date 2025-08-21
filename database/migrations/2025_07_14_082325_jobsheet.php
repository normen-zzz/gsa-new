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
        Schema::create('jobsheet', function (Blueprint $table) {
            $table->id('id_jobsheet');
            $table->unsignedBigInteger('id_shippinginstruction');
            $table->unsignedBigInteger('id_job');
            $table->unsignedBigInteger('id_awb');
            $table->unsignedBigInteger('id_salesorder');
            $table->text('remarks')->nullable();
            $table->integer('created_by')->unsigned();
            $table->softDeletes();
            $table->integer('deleted_by')->nullable();
            $table->timestamps();
            $table->enum('status', [
                'js_created_by_cs',
                'js_approved_by_manager_cs',
                'js_rejected_by_manager_cs',
                'js_approved_by_finance',
                'js_rejected_by_finance',
                'js_approved_by_manager_finance',
                'js_rejected_by_manager_finance',
                'js_approved_by_director',
                'js_rejected_by_director',
                'js_deleted'
            ])->default('js_created_by_cs');
        });

        

        Schema::create('attachments_jobsheet', function (Blueprint $table) {
            $table->id('id_attachment_jobsheet');
            $table->unsignedBigInteger('id_jobsheet');
            $table->string('file_name');
            $table->string('url');
            $table->string('public_id');
            $table->integer('created_by')->unsigned();
            $table->timestamps();
            $table->softDeletes();
            $table->integer('deleted_by')->nullable();
        });

        Schema::create('cost_jobsheet', function (Blueprint $table) {
            $table->id('id_cost_jobsheet');
            $table->unsignedBigInteger('id_jobsheet');
            $table->unsignedBigInteger('id_typecost');
            $table->decimal('cost_value', 10, 2)->comment('Cost value in the jobsheet in dollar');
            $table->enum('charge_by', ['chargeable_weight', 'gross_weight', 'awb']);
            $table->text('description')->nullable();
            $table->integer('created_by')->unsigned();
            $table->integer('updated_by')->nullable();
            $table->timestamps();
        });

        

         Schema::create('log_jobsheet', function (Blueprint $table) {
            $table->id('id_log_jobsheet');
            $table->unsignedBigInteger('id_jobsheet');
            $table->json('action');
            $table->integer('created_by')->unsigned();
            $table->timestamps();
        });

        

         Schema::create('approval_jobsheet', function (Blueprint $table) {
            $table->id('id_approval_jobsheet');
            $table->unsignedBigInteger('id_jobsheet');
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

        Schema::create('flowapproval_jobsheet', function (Blueprint $table) {
            $table->id('id_flowapproval_jobsheet');
            $table->unsignedBigInteger('request_position');
            $table->unsignedBigInteger('request_division');
          
            $table->integer('step_no');
            $table->enum('status', ['active', 'inactive'])->default('active');
           
            $table->integer('created_by')->unsigned();
            $table->timestamps();
        });
        Schema::create('detailflowapproval_jobsheet', function (Blueprint $table) {
            $table->id('id_detailflowapproval_jobsheet');
            $table->unsignedBigInteger('id_flowapproval_jobsheet');
            $table->unsignedBigInteger('approval_position');
            $table->unsignedBigInteger('approval_division');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->integer('step_no');
            $table->integer('created_by')->unsigned();
            $table->timestamps();
        });

        Schema::create('log_flowapproval_jobsheet', function (Blueprint $table) {
            $table->id('id_log_flowapproval_jobsheet');
            $table->unsignedBigInteger('id_flowapproval_jobsheet');
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
        Schema::dropIfExists('jobsheet');
        Schema::dropIfExists('cost_jobsheet');
        Schema::dropIfExists('attachments_jobsheet');
        Schema::dropIfExists('log_jobsheet');
        Schema::dropIfExists('flowapproval_jobsheet');
        Schema::dropIfExists('log_flowapproval_jobsheet');
        Schema::dropIfExists('approval_jobsheet');
    }
};
