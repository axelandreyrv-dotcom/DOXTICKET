<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mail_accounts', function (Blueprint $table): void {
            $table->string('oauth_provider_user_id', 255)->nullable()->after('oauth_refresh_token');
            $table->json('oauth_scopes')->nullable()->after('oauth_expires_at');
            $table->timestamp('oauth_connected_at')->nullable()->after('oauth_scopes');
        });
    }

    public function down(): void
    {
        Schema::table('mail_accounts', function (Blueprint $table): void {
            $table->dropColumn([
                'oauth_provider_user_id',
                'oauth_scopes',
                'oauth_connected_at',
            ]);
        });
    }
};
