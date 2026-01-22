<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('code_usage_meta', function (Blueprint $table) {
            $table->string('meta_hash', 64)->primary();
            $table->json('payload_json');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('code_usage_meta');
    }
};
