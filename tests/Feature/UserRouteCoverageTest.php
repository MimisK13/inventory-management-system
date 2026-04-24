<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserRouteCoverageTest extends TestCase
{
    public function test_user_resource_routes_render_for_authenticated_user(): void
    {
        $user = $this->createUser();
        $managedUser = User::factory()->create([
            'name' => 'managed-user',
            'username' => 'managed-user',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)->get(route('users.index', absolute: false))
            ->assertOk()
            ->assertViewIs('users.index');
        $this->actingAs($user)->get(route('users.create', absolute: false))
            ->assertOk()
            ->assertViewIs('users.create');
        $this->actingAs($user)->get(route('users.show', $managedUser, absolute: false))
            ->assertOk()
            ->assertViewIs('users.show');
        $this->actingAs($user)->get(route('users.edit', $managedUser, absolute: false))
            ->assertOk()
            ->assertViewIs('users.edit');
    }

    public function test_user_store_update_password_update_and_destroy_routes_work(): void
    {
        $authUser = $this->createUser();

        $storeResponse = $this->actingAs($authUser)->post(route('users.store', absolute: false), [
            'name' => 'new-user',
            'email' => 'new-user@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $storeResponse->assertRedirect(route('users.index', absolute: false));
        $this->assertDatabaseHas('users', [
            'name' => 'new-user',
            'email' => 'new-user@example.com',
        ]);

        $managedUser = User::factory()->create([
            'name' => 'edit-user',
            'username' => 'edit-user',
            'email' => 'edit-user@example.com',
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
        ]);

        $updateResponse = $this->actingAs($authUser)->put(route('users.update', $managedUser, absolute: false), [
            'name' => 'edit-user-updated',
            'email' => 'edit-user-updated@example.com',
        ]);

        $updateResponse->assertRedirect(route('users.index', absolute: false));
        $this->assertDatabaseHas('users', [
            'id' => $managedUser->id,
            'name' => 'edit-user-updated',
            'email' => 'edit-user-updated@example.com',
        ]);

        $passwordUpdateResponse = $this->actingAs($authUser)->put(
            route('users.updatePassword', ['username' => 'edit-user'], absolute: false),
            [
                'password' => 'new-secret-password',
                'password_confirmation' => 'new-secret-password',
            ]
        );

        $passwordUpdateResponse->assertRedirect(route('users.index', absolute: false));
        $this->assertTrue(Hash::check('new-secret-password', $managedUser->fresh()->password));

        $deleteResponse = $this->actingAs($authUser)->delete(route('users.destroy', $managedUser->fresh(), absolute: false));

        $deleteResponse->assertRedirect(route('users.index', absolute: false));
        $this->assertDatabaseMissing('users', [
            'id' => $managedUser->id,
        ]);
    }
}
