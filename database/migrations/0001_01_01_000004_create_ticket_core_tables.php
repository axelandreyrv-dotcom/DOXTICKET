<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('color', 16)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'name']);
            $table->index(['company_id', 'is_active']);
        });

        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('mail_account_id')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('assigned_to_membership_id')->nullable()->constrained('memberships')->nullOnDelete();
            $table->foreignId('created_by_membership_id')->nullable()->constrained('memberships')->nullOnDelete();
            $table->string('requester_email', 180)->nullable();
            $table->string('requester_name', 180)->nullable();
            $table->string('subject')->default('Sin Asunto');
            $table->unsignedBigInteger('public_number');
            $table->string('public_key', 40);
            $table->string('status', 32)->default('new');
            $table->string('priority', 16)->default('medium');
            $table->string('source', 16);
            $table->string('external_thread_id')->nullable();
            $table->timestamp('first_opened_at')->nullable();
            $table->timestamp('first_response_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->boolean('merged')->default(false);
            $table->foreignId('merged_into_ticket_id')->nullable()->constrained('tickets')->nullOnDelete();
            $table->timestamp('merged_at')->nullable();
            $table->foreignId('merged_by_membership_id')->nullable()->constrained('memberships')->nullOnDelete();
            $table->timestamp('sla_due_at')->nullable();
            $table->timestamp('last_activity_at');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'public_number']);
            $table->unique(['company_id', 'public_key']);
            $table->index(['company_id', 'status', 'last_activity_at']);
            $table->index(['company_id', 'assigned_to_membership_id', 'status']);
            $table->index(['company_id', 'priority', 'status']);
            $table->index(['company_id', 'category_id']);
            $table->index(['company_id', 'requester_email']);
            $table->index(['company_id', 'sla_due_at']);
            $table->index(['company_id', 'merged_into_ticket_id']);
            $table->index(['company_id', 'deleted_at']);
            $table->index('external_thread_id');
        });

        Schema::create('ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('author_membership_id')->nullable()->constrained('memberships')->nullOnDelete();
            $table->string('author_email', 180)->nullable();
            $table->string('author_name', 180)->nullable();
            $table->string('visibility', 16);
            $table->string('direction', 16);
            $table->text('body_html')->nullable();
            $table->text('body_text')->nullable();
            $table->boolean('external_images_blocked')->default(false);
            $table->string('message_id_header')->nullable();
            $table->string('in_reply_to_header')->nullable();
            $table->text('references_header')->nullable();
            $table->jsonb('headers_raw')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'ticket_id', 'created_at']);
            $table->index(['author_membership_id', 'created_at']);
            $table->index('message_id_header');
            $table->index('in_reply_to_header');
            $table->index('direction');
            $table->index('visibility');
        });

        Schema::create('ticket_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('actor_membership_id')->nullable()->constrained('memberships')->nullOnDelete();
            $table->string('type', 64);
            $table->jsonb('payload');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['company_id', 'ticket_id', 'created_at']);
            $table->index(['actor_membership_id', 'created_at']);
            $table->index('type');
        });

        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ticket_message_id')->nullable()->constrained('ticket_messages')->nullOnDelete();
            $table->string('filename');
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('size_bytes');
            $table->string('disk', 32)->default('private');
            $table->string('path', 512);
            $table->char('checksum_sha256', 64)->nullable();
            $table->text('blocked_reason')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->softDeletes();

            $table->index(['company_id', 'ticket_id']);
            $table->index('ticket_message_id');
            $table->index('checksum_sha256');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
        Schema::dropIfExists('ticket_events');
        Schema::dropIfExists('ticket_messages');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('categories');
    }
};
