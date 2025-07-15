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
            // pol 
            $table->integer('pol')->unsigned();
            // pod
            $table->integer('pod')->unsigned();
            $table->string('commodity')->nullable();
            // weight 
            $table->decimal('weight', 8, 2)->nullable();
            //pieces
            $table->integer('pieces')->unsigned()->nullable();
            //special_instructions
            $table->text('special_instructions')->nullable();
            //created_by
            $table->integer('created_by')->unsigned();
            //status
            $table->enum('status', ['created by sales', 'receive by cs', 'rejected by cs'])->default('created by sales');

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
