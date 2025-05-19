<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('momo_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('provider');
            $table->string('transaction_id')->unique();
            $table->string('reference')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('phone');
            $table->string('network');
            $table->string('status')->default('pending');
            $table->string('type')->default('receive'); // receive, send
            $table->string('currency')->default('GHS');
            $table->json('meta')->nullable();
            $table->json('request')->nullable(); // Store the original request data
            $table->json('response')->nullable(); // Store the provider response
            $table->json('callback_data')->nullable(); // Store webhook callback data
            $table->timestamp('callback_received_at')->nullable();
            $table->text('error_message')->nullable();
            $table->string('error_code')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            // Indexes for better query performance
            $table->index(['provider', 'status']);
            $table->index(['transaction_id', 'reference']);
            $table->index(['phone', 'network']);
            $table->index(['type', 'status']);
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('momo_transactions');
    }
};
