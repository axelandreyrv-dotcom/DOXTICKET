<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('memberships', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 32);
            $table->string('status', 32)->default('active');
            $table->foreignId('invited_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('last_selected_at')->nullable();
            $table->jsonb('preferences')->default('{}');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'user_id']);
            $table->index(['user_id', 'status']);
            $table->index(['company_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memberships');
    }
};
