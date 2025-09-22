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
        Schema::table('revisisalesorder', function (Blueprint $table) {
            $table->id('id_revisisalesorder');
            $table->unsignedBigInteger('id_salesorder');
            $table->text('revision_notes')->nullable();
            $table->timestamps();
            $table->integer('created_by')->unsigned();
            $table->softDeletes();
            $table->integer('deleted_by')->nullable();
            $table->enum('status', [
                'revision_created',
                'revision_approved',
                'revision_rejected',
                'revision_deleted'
            ])->default('revision_created');
        });

        Schema::table('detailfrom_revisisalesorder', function (Blueprint $table) {
            $table->id('id_detail_revisisalesorder');
            $table->unsignedBigInteger('id_revisisalesorder');
            $table->unsignedBigInteger('id_typeselling');
            $table->decimal('selling_value', 10, 2)->comment('Selling value in the sales order revision in rupiah');
            $table->enum('charge_by', ['chargeable_weight','gross_weight','awb'])->comment('Charge by in the sales order revision');
            $table->string('description')->nullable();
        });

        Schema::table('detailto_revisisalesorder', function (Blueprint $table) {
            $table->id('id_detail_revisisalesorder');
            $table->unsignedBigInteger('id_revisisalesorder');
            $table->unsignedBigInteger('id_typeselling');
            $table->decimal('selling_value', 10, 2)->comment('Selling value in the sales order revision in rupiah');
            $table->enum('charge_by', ['chargeable_weight','gross_weight','awb'])->comment('Charge by in the sales order revision');
            $table->string('description')->nullable();
        });

        Schema::create('approval_revisisalesorder', function (Blueprint $table) {
            $table->id('id_approval_revisisalesorder');
            $table->unsignedBigInteger('id_revisisalesorder');
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

        Schema::create('flowapproval_salesorder', function (Blueprint $table) {
            $table->id('id_flowapproval_salesorder');
            $table->unsignedBigInteger('request_position');
            $table->unsignedBigInteger('request_division');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->integer('created_by')->unsigned();
            $table->timestamps();
        });

        Schema::create('detailflowapproval_salesorder', function (Blueprint $table) {
            $table->id('id_detailflowapproval_salesorder');
            $table->unsignedBigInteger('id_flowapproval_salesorder');
            $table->unsignedBigInteger('approval_position');
            $table->unsignedBigInteger('approval_division');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->integer('step_no');
            $table->integer('created_by')->unsigned();
            $table->timestamps();
        });

        Schema::table('log_revisisalesorder', function (Blueprint $table) {
            $table->id('id_log_revisisalesorder');
            $table->unsignedBigInteger('id_revisisalesorder');
            $table->json('action');
            $table->timestamps();
            $table->integer('created_by')->nullable();
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
