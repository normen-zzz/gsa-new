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
        Schema::create('users', function (Blueprint $table) {
            $table->id('id_user');
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
            $table->integer('id_position');
            $table->integer('id_division');
            $table->integer('id_role');
            $table->string('photo')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('status')->default(true);
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('positions', function (Blueprint $table) {
            $table->integer('id_position')->autoIncrement();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('divisions', function (Blueprint $table) {
            $table->integer('id_division')->autoIncrement();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->boolean('have_role')->default(false);
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->integer('id_role')->autoIncrement();
            $table->integer('id_division');
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id('id_permission');
            $table->integer('id_position');
            $table->integer('id_division')->nullable();
            $table->integer('id_role')->nullable();
            $table->string('path');
            $table->boolean('can_read')->default(false)->comment('Can read the resource 0 = No, 1 = Yes');
            $table->boolean('can_create')->default(false)->comment('Can create the resource 0 = No, 1 = Yes');
            $table->boolean('can_update')->default(false)->comment('Can update the resource 0 = No, 1 = Yes');
            $table->boolean('can_delete')->default(false)->comment('Can delete the resource 0 = No, 1 = Yes');
            $table->boolean('can_approve')->default(false)->comment('Can approve the resource 0 = No, 1 = Yes');
            $table->boolean('can_reject')->default(false)->comment('Can reject the resource 0 = No, 1 = Yes');
            $table->boolean('can_print')->default(false)->comment('Can print the resource 0 = No, 1 = Yes');
            $table->boolean('can_export')->default(false)->comment('Can export the resource 0 = No, 1 = Yes');
            $table->boolean('can_import')->default(false)->comment('Can import the resource 0 = No, 1 = Yes');
            $table->timestamps();
            $table->boolean('status')->default(true);
        });

        Schema::create('list_menu', function (Blueprint $table) {
            $table->integer('id_listmenu')->autoIncrement();
            $table->string('name');
            $table->string('icon')->nullable();
            $table->string('path')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
        Schema::create('list_childmenu', function (Blueprint $table) {
            $table->integer('id_listchildmenu')->autoIncrement();
            $table->integer('id_listmenu');
            $table->string('name');
            $table->string('icon')->nullable();
            $table->string('path')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('menu_user', function (Blueprint $table) {
            $table->integer('id_menu_user')->autoIncrement();
            $table->integer('id_position');
            $table->integer('id_division');
            $table->integer('id_role');
            $table->json('menu')->nullable()->comment('List of menu IDs');
            $table->timestamps();
        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
