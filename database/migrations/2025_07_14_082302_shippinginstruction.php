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
        Schema::create('shippinginstruction', function (Blueprint $table) {
            $table->id('id_shippinginstruction');
            $table->integer('agent')->unsigned();
            $table->integer('consignee')->unsigned();
            $table->datetime('date');
            $table->dateTime('eta')->nullable();
            $table->dateTime('etd')->nullable();
            $table->integer('pol')->unsigned();
            $table->integer('pod')->unsigned();
            $table->string('commodity')->nullable();
            $table->decimal('weight', 8, 2)->nullable();
            $table->integer('pieces')->unsigned()->nullable();
            $table->json('dimensions')->nullable();
            $table->text('special_instructions')->nullable();
            $table->integer('created_by')->unsigned();
            $table->integer('updated_by')->unsigned()->nullable();
            
            $table->enum('status', ['created_by_sales', 'received_by_cs', 'rejected_by_cs','deleted'])->default('created_by_sales');
            $table->softDeletes();
            $table->integer('deleted_by')->unsigned()->nullable();
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
