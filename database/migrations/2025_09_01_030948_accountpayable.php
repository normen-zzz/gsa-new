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
        Schema::create('account_payable', function (Blueprint $table) {
            $table->id('id_accountpayable');
            $table->string('no_accountpayable')->unique();
            $table->enum('type', ['RE', 'PO','CA','CAR']);
            $table->text('description')->nullable();
            $table->decimal('total', 15, 2);
            $table->string('no_ca')->nullable();
            $table->timestamps();
            $table->integer('created_by');
            $table->integer('updated_by');
            $table->softDeletes();
            $table->integer('deleted_by')->nullable();

        });
        

        Schema::create('type_pengeluaran', function (Blueprint $table) {
            $table->id('id_typepengeluaran');
            $table->string('name');
            $table->timestamps();
            $table->integer('created_by');
            $table->integer('updated_by');
            $table->softDeletes();
            $table->integer('deleted_by')->nullable();
        });

        Schema::create('detail_accountpayable', function (Blueprint $table) {
            $table->id('id_detailaccountpayable');
            $table->unsignedBigInteger('id_accountpayable');
            $table->integer('type_pengeluaran');
            $table->string('description');
            $table->decimal('amount', 15, 2);
            $table->timestamps();
            $table->integer('created_by');
            $table->integer('updated_by');
        });

        Schema::create('log_accountspayable', function (Blueprint $table) {
            $table->id('id_logaccountpayable');
            $table->unsignedBigInteger('id_accountpayable');
            $table->json('action');
            $table->unsignedBigInteger('id_user');
            $table->timestamps();
        });

          Schema::create('approval_accountpayable', function (Blueprint $table) {
            $table->id('id_approval_accountpayable');
            $table->unsignedBigInteger('id_accountpayable');
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

        Schema::create('flowapproval_accountpayable', function (Blueprint $table) {
            $table->id('id_flowapproval_accountpayable');
            $table->unsignedBigInteger('request_position');
            $table->unsignedBigInteger('request_division');
            $table->enum('status', ['active', 'inactive'])->default('active');
           
            $table->integer('created_by')->unsigned();
            $table->timestamps();
        });
        Schema::create('detailflowapproval_accountpayable', function (Blueprint $table) {
            $table->id('id_detailflowapproval_accountpayable');
            $table->unsignedBigInteger('id_flowapproval_accountpayable');
            $table->unsignedBigInteger('approval_position');
            $table->unsignedBigInteger('approval_division');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->integer('step_no');
            $table->integer('created_by')->unsigned();
            $table->timestamps();
        });

        Schema::create('log_flowapproval_accountpayable', function (Blueprint $table) {
            $table->id('id_log_flowapproval_accountpayable');
            $table->unsignedBigInteger('id_flowapproval_accountpayable');
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
