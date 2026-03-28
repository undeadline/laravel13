<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\PhoneVerificationService;
use App\Services\SmsAeroService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhoneVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(SmsAeroService::class)
            ->shouldReceive('send')
            ->andReturn(true);
    }

    private function registerAndGetCode(string $phone = '+79991234567'): ?string
    {
        $capturedCode = '123456';

        // Один мок с обоими методами
        $this->instance(
            PhoneVerificationService::class,
            \Mockery::mock(PhoneVerificationService::class, function ($mock) use (&$capturedCode) {
                $mock->shouldReceive('generateCode')
                    ->andReturn($capturedCode);

                $mock->shouldReceive('verify')
                    ->andReturnUsing(function (string $phone, string $code) use (&$capturedCode) {
                        return $code === $capturedCode;
                    });
            })
        );

        $this->postJson('/register', [
            'email'                 => 'user@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'phone'                 => $phone,
            'name'                  => 'Test',
        ]);

        return $capturedCode;
    }

    public function test_user_can_verify_phone_with_correct_code(): void
    {
        $code = $this->registerAndGetCode();

        $response = $this->postJson('/verify-phone', [
            'phone' => '+79991234567',
            'code'  => $code,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['user']);

        $this->assertDatabaseHas('users', [
            'phone' => '+79991234567',
        ]);

        $user = User::where('phone', '+79991234567')->first();
        $this->assertNotNull($user->phone_verified_at);
    }

    public function test_user_is_authenticated_after_verification(): void
    {
        $code = $this->registerAndGetCode();

        $this->postJson('/verify-phone', [
            'phone' => '+79991234567',
            'code'  => $code,
        ]);

        $this->assertAuthenticated();
    }

    public function test_verification_fails_with_wrong_code(): void
    {
        $this->registerAndGetCode();

        $response = $this->postJson('/verify-phone', [
            'phone' => '+79991234567',
            'code'  => '000000',
        ]);

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'Неверный или истёкший код.']);
    }

    public function test_verification_fails_with_nonexistent_phone(): void
    {
        $response = $this->postJson('/verify-phone', [
            'phone' => '+70000000000',
            'code'  => '123456',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_verification_requires_all_fields(): void
    {
        $response = $this->postJson('/verify-phone', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone', 'code']);
    }
}
