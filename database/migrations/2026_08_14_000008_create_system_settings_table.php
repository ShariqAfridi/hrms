<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('group')->default('general'); // general, attendance, payroll, leave, ai_copilot, notifications
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('label');
            $table->text('description')->nullable();
            $table->string('type')->default('string'); // string, number, boolean, select
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('system_settings');
    }
};
