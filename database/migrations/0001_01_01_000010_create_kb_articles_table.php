<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kb_articles', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_membership_id')->nullable()->constrained('memberships')->nullOnDelete();
            $table->string('title', 180);
            $table->string('slug', 200);
            $table->text('body_markdown');
            $table->text('body_html_cached')->nullable();
            $table->jsonb('tags')->nullable();
            $table->string('status', 32)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'slug']);
            $table->index(['company_id', 'status', 'published_at']);
            $table->index(['author_membership_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kb_articles');
    }
};
