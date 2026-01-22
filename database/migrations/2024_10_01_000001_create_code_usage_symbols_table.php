<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('code_usage_symbols', function (Blueprint $table) {
            $table->id();
            $table->string('symbol')->unique();
            $table->enum('kind', [
                'controller',
                'job',
                'event',
                'listener',
                'command',
                'method',
                'class',
                'other',
            ])->default('other');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('code_usage_symbols');
    }
};
