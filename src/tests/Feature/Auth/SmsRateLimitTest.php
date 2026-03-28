<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\SmsAeroService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmsRateLimitTest extends TestCase
{
    use RefreshDatabase;

    private array $payload = [
        'email'                 => 'user@example.com',
        'password'              => 'password123',
        'password_confirmation' => 'password123',
        'phone'                 => '+79991234567',
        'name'                  => 'Test',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(SmsAeroService::class)
            ->shouldReceive('send')
            ->andReturn(true);
    }

    public function test_registration_is_rate_limited_per_minute(): void
    {
        // Первый запрос проходит
        $this->postJson('/register', $this->payload)
            ->assertStatus(201);

        // Удаляем юзера чтобы не было ошибки unique
        User::where('phone', '+79991234567')->delete();

        // Второй запрос в ту же минуту — rate limit
        $this->postJson('/register', $this->payload)
            ->assertStatus(429);
    }
}
