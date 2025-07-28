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
        Schema::create('countries', function (Blueprint $table) {
            $table->id('id_country');
            $table->string('name_country')->unique();
            $table->timestamps();
            $table->boolean('status')->default(true);
            $table->integer('created_by')->unsigned();
        });

        Schema::table('countries', function (Blueprint $table) {
            $table->unsignedBigInteger('updated_by')->nullable()->after('updated_at');
        });

        Schema::create('log_country', function (Blueprint $table) {
            $table->id('id_logcountry');
            $table->integer('id_country')->unsigned();
            $table->text('action');
            $table->integer('id_user')->unsigned();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('countries');
        Schema::table('countries', function (Blueprint $table) {
            $table->dropColumn('updated_by');
        });
        //
    }
};
