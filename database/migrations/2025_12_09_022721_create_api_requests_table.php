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
        Schema::create('api_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('method', 10);
            $table->string('path', 500);
            $table->string('full_url', 2000);
            $table->string('route_name')->nullable();
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->json('headers')->nullable();
            $table->json('query_params')->nullable();
            $table->json('request_body')->nullable();
            $table->integer('status_code');
            $table->json('response_body')->nullable();
            $table->integer('response_time_ms');
            $table->string('device_name')->nullable();
            $table->string('token_id')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('method');
            $table->index('status_code');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_requests');
    }
};
