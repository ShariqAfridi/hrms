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
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'password_signature')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('password_signature', 64)->nullable()->after('password');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'password_signature')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('password_signature');
            });
        }
    }
};
