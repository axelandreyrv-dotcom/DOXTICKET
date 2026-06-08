<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('system_settings')
            ->whereIn('key', [
                'donations.paypal_url',
                'donations.github_sponsors_url',
                'donations.buy_me_a_coffee_url',
            ])
            ->delete();
    }

    public function down(): void
    {
        //
    }
};
