<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('ai_usage_logs')) {
            Schema::create('ai_usage_logs', function (Blueprint $table) {
                $table->id();
                $table->uuid('user_id')->nullable()->index();
                $table->string('user_name')->nullable();
                $table->string('model_used')->default('gemini-1.5-flash')->index();
                $table->text('prompt');
                $table->text('response_summary')->nullable();
                $table->json('tools_called')->nullable();
                $table->unsignedInteger('prompt_tokens')->default(0);
                $table->unsignedInteger('response_tokens')->default(0);
                $table->unsignedInteger('total_tokens')->default(0);
                $table->decimal('cost_usd', 10, 6)->default(0.000000);
                $table->unsignedInteger('latency_ms')->default(0);
                $table->string('status', 30)->default('SUCCESS')->index();
                $table->text('error_message')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('ai_usage_logs');
    }
};
