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
        Schema::create('salesorder', function (Blueprint $table) {
            $table->id('id_salesorder');
            $table->unsignedBigInteger('id_shippinginstruction')->nullable();
            $table->unsignedBigInteger('id_job')->nullable();
            $table->unsignedBigInteger('id_awb')->nullable();
            $table->text('remarks')->nullable();
            $table->integer('created_by')->unsigned();
            $table->softDeletes();
            $table->integer('deleted_by')->nullable();
            $table->timestamps();
            $table->enum('status', [
                'so_created_by_sales',
                'so_approved_by_manager',
                'so_rejected_by_manager',
                'so_approved_by_cs',
                'so_rejected_by_cs',
                'so_approved_by_manager_cs',
                'so_rejected_by_manager_cs',
                'so_approved_by_finance',
                'so_rejected_by_finance',
                'so_approved_by_manager_finance',
                'so_rejected_by_manager_finance',
                'so_approved_by_director',
                'so_rejected_by_director',
                'so_deleted'
            ])->default('so_created_by_sales');
        });

        Schema::create('attachments_salesorder', function (Blueprint $table) {
            $table->id('id_attachment_salesorder');
            $table->unsignedBigInteger('id_salesorder');
            $table->string('file_name');
            $table->string('url');
            $table->string('public_id');
            $table->integer('created_by')->unsigned();
            $table->timestamps();
            $table->softDeletes();
            $table->integer('deleted_by')->nullable();
        });

        Schema::create('selling_salesorder', function (Blueprint $table) {
            $table->id('id_selling_salesorder');
            $table->unsignedBigInteger('id_salesorder');
            $table->unsignedBigInteger('id_typeselling');
            $table->decimal('selling_value', 10, 2)->comment('Selling value in the sales order in rupiah');
            $table->enum('charge_by', ['chargeable_weight', 'gross_weight', 'awb']);
            $table->text('description')->nullable();
            $table->integer('created_by')->unsigned();
            $table->timestamps();
        });

         Schema::create('log_salesorder', function (Blueprint $table) {
            $table->id('id_log_salesorder');
            $table->unsignedBigInteger('id_salesorder');
            $table->json('action');
            $table->integer('created_by')->unsigned();
            $table->timestamps();
        });

       

        Schema::create('approval_salesorder', function (Blueprint $table) {
            $table->id('id_approval_salesorder');
            $table->unsignedBigInteger('id_salesorder');
            $table->unsignedBigInteger('approval_position');
            $table->unsignedBigInteger('approval_division');
            $table->integer('step_no');
            $table->integer('next_step')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('remarks')->nullable();
            $table->datetime('approved_at')->nullable();
            $table->integer('approved_by')->nullable();
            $table->timestamps();
            $table->integer('created_by')->unsigned();
        });

         Schema::create('flowapproval_salesorder', function (Blueprint $table) {
            $table->id('id_flowapproval_salesorder');
            $table->unsignedBigInteger('request_position');
            $table->unsignedBigInteger('request_division');
            $table->unsignedBigInteger('approval_position');
            $table->unsignedBigInteger('approval_division');
            //urutan
            $table->integer('step_no');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->integer('next_step')->nullable(); //no step
            $table->integer('created_by')->unsigned();
            $table->timestamps();
        });

        Schema::create('log_flowapproval_salesorder', function (Blueprint $table) {
            $table->id('id_log_flowapproval_salesorder');
            $table->unsignedBigInteger('id_flowapproval_salesorder');
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
        Schema::dropIfExists('salesorder');
        Schema::dropIfExists('attachments_salesorder');
        Schema::dropIfExists('selling_salesorder');
        Schema::dropIfExists('log_salesorder');
        Schema::dropIfExists('flowapproval_salesorder');
        Schema::dropIfExists('log_flowapproval_salesorder');
        Schema::dropIfExists('approval_salesorder');
    }
};
