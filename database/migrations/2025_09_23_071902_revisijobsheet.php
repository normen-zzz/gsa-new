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
        Schema::create('revisijobsheet', function (Blueprint $table) {
            $table->id('id_revisijobsheet');
            $table->unsignedBigInteger('id_jobsheet');
            $table->text('revision_notes')->nullable();
            $table->timestamps();
            $table->integer('created_by')->unsigned();
            $table->softDeletes();
            $table->integer('deleted_by')->nullable();
            $table->enum('status_revisijobsheet', [
                'revision_created',
                'revision_approved',
                'revision_rejected',
                'revision_deleted'
            ])->default('revision_created');
        });

        Schema::create('detailfrom_revisijobsheet', function (Blueprint $table) {
            $table->id('id_detail_revisijobsheet');
            $table->unsignedBigInteger('id_revisijobsheet');
            $table->unsignedBigInteger('id_typecost');
            $table->decimal('cost_value', 10, 2)->comment('Selling value in the sales order revision in rupiah');
            $table->enum('charge_by', ['chargeable_weight','gross_weight','awb'])->comment('Charge by in the sales order revision');
            $table->string('description')->nullable();
        });

        Schema::create('detailto_revisijobsheet', function (Blueprint $table) {
            $table->id('id_detail_revisijobsheet');
            $table->unsignedBigInteger('id_revisijobsheet');
            $table->unsignedBigInteger('id_typecost');
            $table->decimal('cost_value', 10, 2)->comment('Cost value in the sales order revision in rupiah');
            $table->enum('charge_by', ['chargeable_weight','gross_weight','awb'])->comment('Charge by in the sales order revision');
            $table->string('description')->nullable();
        });

        Schema::create('approval_revisijobsheet', function (Blueprint $table) {
            $table->id('id_approval_revisijobsheet');
            $table->unsignedBigInteger('id_revisijobsheet');
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

        Schema::create('flowapproval_revisijobsheet', function (Blueprint $table) {
            $table->id('id_flowapproval_revisijobsheet');
            $table->unsignedBigInteger('request_position');
            $table->unsignedBigInteger('request_division');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->integer('created_by')->unsigned();
            $table->timestamps();
        });

        Schema::create('detailflowapproval_revisijobsheet', function (Blueprint $table) {
            $table->id('id_detailflowapproval_revisijobsheet');
            $table->unsignedBigInteger('id_flowapproval_revisijobsheet');
            $table->unsignedBigInteger('approval_position');
            $table->unsignedBigInteger('approval_division');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->integer('step_no');
            $table->integer('created_by')->unsigned();
            $table->timestamps();
        });

        Schema::create('log_revisijobsheet', function (Blueprint $table) {
            $table->id('id_log_revisijobsheet');
            $table->unsignedBigInteger('id_revisijobsheet');
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
