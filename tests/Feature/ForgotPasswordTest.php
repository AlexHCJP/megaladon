<?php

namespace Tests\Feature;

use App\Models\PhoneConfirmation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $phone = '+77001234567'): User
    {
        return User::create([
            'name' => 'Test',
            'phone' => $phone,
            'password' => Hash::make('oldpassword'),
            'is_phone_confirmed' => true,
        ]);
    }

    public function test_forgot_password_returns_code_for_existing_user(): void
    {
        $this->makeUser();

        $response = $this->postJson('/api/auth/forgot-password', [
            'phone' => '+77001234567',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.success', true)
            ->assertJsonStructure(['data' => ['verification_code']]);
    }

    public function test_forgot_password_404_for_unknown_user(): void
    {
        $response = $this->postJson('/api/auth/forgot-password', [
            'phone' => '+77009999999',
        ]);

        $response->assertStatus(404);
    }

    public function test_reset_password_with_valid_code_changes_password(): void
    {
        $user = $this->makeUser();
        PhoneConfirmation::create([
            'user_id' => $user->id,
            'phone' => $user->phone,
            'code' => '101010',
        ]);

        $response = $this->postJson('/api/auth/reset-password', [
            'phone' => $user->phone,
            'code' => '101010',
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
        ]);

        $response->assertStatus(200)->assertJsonPath('data.success', true);
        $this->assertTrue(Hash::check('newpassword', $user->fresh()->password));
    }

    public function test_reset_password_uses_latest_code_when_multiple_exist(): void
    {
        $user = $this->makeUser();
        // Стойкая (старая) запись с другим кодом — например, от прошлой регистрации.
        PhoneConfirmation::create([
            'user_id' => $user->id,
            'phone' => $user->phone,
            'code' => '999999',
        ]);
        // Свежая запись от forgot-password.
        PhoneConfirmation::create([
            'user_id' => $user->id,
            'phone' => $user->phone,
            'code' => '101010',
        ]);

        $response = $this->postJson('/api/auth/reset-password', [
            'phone' => $user->phone,
            'code' => '101010',
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
        ]);

        $response->assertStatus(200)->assertJsonPath('data.success', true);
        $this->assertTrue(Hash::check('newpassword', $user->fresh()->password));
    }

    public function test_reset_password_rejects_wrong_code(): void
    {
        $user = $this->makeUser();
        PhoneConfirmation::create([
            'user_id' => $user->id,
            'phone' => $user->phone,
            'code' => '101010',
        ]);

        $response = $this->postJson('/api/auth/reset-password', [
            'phone' => $user->phone,
            'code' => '000000',
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
        ]);

        $response->assertStatus(400);
    }

    public function test_reset_password_validation_requires_confirmation(): void
    {
        $user = $this->makeUser();
        PhoneConfirmation::create([
            'user_id' => $user->id,
            'phone' => $user->phone,
            'code' => '101010',
        ]);

        $response = $this->postJson('/api/auth/reset-password', [
            'phone' => $user->phone,
            'code' => '101010',
            'password' => 'newpassword',
        ]);

        $response->assertStatus(422);
    }
}
