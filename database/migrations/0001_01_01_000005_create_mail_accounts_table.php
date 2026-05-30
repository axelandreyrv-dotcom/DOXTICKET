<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_accounts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 32)->default('imap_smtp');
            $table->string('from_name', 120)->nullable();
            $table->string('from_email', 180);
            $table->string('host_imap')->nullable();
            $table->unsignedInteger('port_imap')->nullable();
            $table->string('security_imap', 16)->nullable();
            $table->string('host_smtp')->nullable();
            $table->unsignedInteger('port_smtp')->nullable();
            $table->string('security_smtp', 16)->nullable();
            $table->string('username', 180)->nullable();
            $table->text('password_encrypted')->nullable();
            $table->text('oauth_access_token')->nullable();
            $table->text('oauth_refresh_token')->nullable();
            $table->timestamp('oauth_expires_at')->nullable();
            $table->string('folder_in', 120)->default('INBOX');
            $table->boolean('auto_reply_enabled')->default(true);
            $table->boolean('is_active')->default(true);
            $table->string('last_uid', 120)->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->unique('company_id');
            $table->unique(['company_id', 'from_email']);
            $table->index(['company_id', 'is_active']);
            $table->index('provider');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_accounts');
    }
};
