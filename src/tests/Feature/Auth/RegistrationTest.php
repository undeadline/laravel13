<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\SmsAeroService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    private array $validPayload = [
        'email'                 => 'user@example.com',
        'password'              => 'password123',
        'password_confirmation' => 'password123',
        'phone'                 => '+79991234567',
        'name'                  => 'Test',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        // Мокаем SMS-сервис — не отправляем реальные СМС в тестах
        $this->mock(SmsAeroService::class)
            ->shouldReceive('send')
            ->andReturn(true);
    }

    public function test_user_can_register_with_valid_data(): void
    {
        $response = $this->postJson('/register', $this->validPayload);

        $response->assertStatus(201)
            ->assertJsonFragment(['message' => 'Код отправлен на номер +79991234567']);

        $this->assertDatabaseHas('users', [
            'email' => 'user@example.com',
            'phone' => '+79991234567',
        ]);
    }

    public function test_phone_verified_at_is_null_after_registration(): void
    {
        $this->postJson('/register', $this->validPayload);

        $user = User::where('email', 'user@example.com')->first();

        $this->assertNull($user->phone_verified_at);
    }

    public function test_registration_requires_all_fields(): void
    {
        $response = $this->postJson('/register', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password', 'phone']);
    }

    public function test_registration_fails_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'user@example.com']);

        $response = $this->postJson('/register', $this->validPayload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_registration_fails_with_duplicate_phone(): void
    {
        User::factory()->create(['phone' => '+79991234567']);

        $response = $this->postJson('/register', $this->validPayload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_registration_fails_with_invalid_phone_format(): void
    {
        $response = $this->postJson('/register', array_merge($this->validPayload, [
            'phone' => '89991234567', // без +7
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_registration_fails_with_short_password(): void
    {
        $response = $this->postJson('/register', array_merge($this->validPayload, [
            'password'              => '123',
            'password_confirmation' => '123',
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_sms_is_sent_after_registration(): void
    {
        $this->mock(SmsAeroService::class)
            ->shouldReceive('send')
            ->once()
            ->with('+79991234567', \Mockery::pattern('/\d{6}/'))
            ->andReturn(true);

        $this->postJson('/register', $this->validPayload);
    }
}
