<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            $table->string('ticket_type', 24)->default('request')->after('priority');
            $table->index(['company_id', 'ticket_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            $table->dropIndex(['company_id', 'ticket_type', 'status']);
            $table->dropColumn('ticket_type');
        });
    }
};
