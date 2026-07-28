<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DeleteAccountTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        return User::create([
            'name' => 'Test',
            'phone' => '+77001234567',
            'password' => Hash::make('secret123'),
            'is_phone_confirmed' => true,
        ]);
    }

    public function test_delete_account_with_correct_password_soft_deletes_user(): void
    {
        $user = $this->makeUser();
        $token = $user->createToken('api')->plainTextToken;

        $response = $this->withToken($token)
            ->deleteJson('/api/user/delete-account', ['password' => 'secret123']);

        $response->assertStatus(200)->assertJsonPath('data.success', true);
        $this->assertSoftDeleted('users', ['id' => $user->id]);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_delete_account_with_wrong_password_returns_401(): void
    {
        $user = $this->makeUser();
        $token = $user->createToken('api')->plainTextToken;

        $response = $this->withToken($token)
            ->deleteJson('/api/user/delete-account', ['password' => 'wrong']);

        $response->assertStatus(401);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'deleted_at' => null]);
    }

    public function test_delete_account_without_token_is_unauthorized(): void
    {
        $response = $this->deleteJson('/api/user/delete-account', ['password' => 'secret123']);
        $response->assertStatus(401);
    }
}
