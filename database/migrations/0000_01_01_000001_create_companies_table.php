<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name', 160);
            $table->string('slug', 80)->unique();
            $table->string('country', 120)->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('status', 32)->default('active');
            $table->string('logo_path')->nullable();
            $table->string('locale_default', 8)->default('es');
            $table->unsignedBigInteger('storage_limit_bytes')->nullable();
            $table->unsignedBigInteger('storage_used_bytes')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
