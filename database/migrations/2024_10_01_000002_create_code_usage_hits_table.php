<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('code_usage_hits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('symbol_id')->constrained('code_usage_symbols')->cascadeOnDelete();
            $table->date('day');
            $table->unsignedInteger('hits')->default(0);
            $table->dateTime('first_seen_at')->nullable();
            $table->dateTime('last_seen_at')->nullable();
            $table->string('meta_hash', 64)->nullable();
            $table->timestamps();

            $table->unique(['symbol_id', 'day']);
            $table->index('last_seen_at');
            $table->index('day');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('code_usage_hits');
    }
};
