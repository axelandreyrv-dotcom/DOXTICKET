<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\MailAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MailAccount>
 */
class MailAccountFactory extends Factory
{
    protected $model = MailAccount::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'provider' => 'imap_smtp',
            'from_name' => 'Soporte TI',
            'from_email' => $this->faker->unique()->safeEmail(),
            'host_imap' => 'imap.example.test',
            'port_imap' => 993,
            'security_imap' => 'ssl',
            'host_smtp' => 'smtp.example.test',
            'port_smtp' => 587,
            'security_smtp' => 'tls',
            'username' => $this->faker->unique()->safeEmail(),
            'password_encrypted' => 'mail-secret',
            'folder_in' => 'INBOX',
            'auto_reply_enabled' => true,
            'is_active' => true,
        ];
    }
}
