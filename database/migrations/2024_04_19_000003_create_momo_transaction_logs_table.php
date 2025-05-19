<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('momo_transaction_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('event')->nullable();
            $table->string('status')->nullable();
            $table->json('data')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->foreignUuid('transaction_id')->constrained('momo_transactions')->onDelete('cascade');
            $table->timestamps();

            $table->index(['transaction_id', 'event']);
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('momo_transaction_logs');
    }
};
