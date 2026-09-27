<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Labels for the access matrix: a permission's title ("Manage") and the
 * section it is listed under ("Patient Directory"), as os_acl_action and
 * os_acl_controller held them; a role keeps the user group's details.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table(config('permission.table_names.permissions'), function (Blueprint $table) {
            $table->string('title', 150)->nullable()->after('guard_name');
            $table->string('group', 150)->nullable()->after('title');
        });

        Schema::table(config('permission.table_names.roles'), function (Blueprint $table) {
            $table->text('details')->nullable()->after('guard_name');
        });
    }

    public function down(): void
    {
        Schema::table(config('permission.table_names.permissions'), function (Blueprint $table) {
            $table->dropColumn(['title', 'group']);
        });

        Schema::table(config('permission.table_names.roles'), function (Blueprint $table) {
            $table->dropColumn('details');
        });
    }
};
