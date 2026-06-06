<?php

namespace Tests\Feature\Mail;

use App\Services\Mail\OAuthStateStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class OAuthStateStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_state_is_bound_to_provider_and_company_then_consumed_once(): void
    {
        Session::start();

        $state = app(OAuthStateStore::class)->create('gmail', 123);
        $payload = app(OAuthStateStore::class)->consume($state, 'gmail', 123);

        $this->assertSame('gmail', $payload['provider']);
        $this->assertSame(123, $payload['company_id']);
        $this->assertNull(app(OAuthStateStore::class)->consume($state, 'gmail', 123));
    }

    public function test_state_rejects_provider_or_company_mismatch(): void
    {
        Session::start();

        $state = app(OAuthStateStore::class)->create('microsoft365', 456);

        $this->assertNull(app(OAuthStateStore::class)->consume($state, 'gmail', 456));
        $this->assertNull(app(OAuthStateStore::class)->consume($state, 'microsoft365', 999));
    }
}
