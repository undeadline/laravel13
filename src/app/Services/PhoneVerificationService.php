<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class PhoneVerificationService
{
    private int $ttl = 300;     // 5 минут
    private int $maxAttempts = 5;

    public function generateCode(string $phone): string
    {
        $code = (string) random_int(100000, 999999);

        Cache::put(
            $this->codeKey($phone),
            bcrypt($code),      // храним хеш, не сам код
            $this->ttl
        );

        // Сбрасываем счётчик попыток
        Cache::forget($this->attemptsKey($phone));

        return $code;
    }

    public function verify(string $phone, string $code): bool
    {
        // Проверяем число попыток
        $attempts = Cache::get($this->attemptsKey($phone), 0);
        if ($attempts >= $this->maxAttempts) {
            return false;
        }

        $hash = Cache::get($this->codeKey($phone));
        if (!$hash || !password_verify($code, $hash)) {
            Cache::increment($this->attemptsKey($phone));
            return false;
        }

        // Код верный — удаляем
        Cache::forget($this->codeKey($phone));
        Cache::forget($this->attemptsKey($phone));

        return true;
    }

    private function codeKey(string $phone): string
    {
        return "phone_verify:code:{$phone}";
    }

    private function attemptsKey(string $phone): string
    {
        return "phone_verify:attempts:{$phone}";
    }
}
