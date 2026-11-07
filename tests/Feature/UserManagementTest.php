<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'name' => 'Super Admin',
            'email' => 'admin_test@sekolah.sch.id',
        ]);
    }

    public function test_admin_can_view_users_page_with_all_roles(): void
    {
        $kepsek = User::factory()->create([
            'role' => 'kepala_sekolah',
            'name' => 'Bapak Kepala Sekolah',
            'email' => 'kepsek@sekolah.sch.id',
        ]);

        $guru = User::factory()->create([
            'role' => 'guru',
            'name' => 'Ibu Guru Matematika',
            'email' => 'guru@sekolah.sch.id',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.users.index'));

        $response->assertStatus(200);
        $response->assertSee('Manajemen Pengguna (Users)');
        $response->assertSee('Bapak Kepala Sekolah');
        $response->assertSee('Ibu Guru Matematika');
        $response->assertSee('Super Admin');
        $response->assertSee('kepsek@sekolah.sch.id');
        $response->assertSee('guru@sekolah.sch.id');
    }

    public function test_non_admin_cannot_access_users_page(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);
        $kepsek = User::factory()->create(['role' => 'kepala_sekolah']);

        $responseGuru = $this->actingAs($guru)->get(route('admin.users.index'));
        $responseGuru->assertStatus(403);

        $responseKepsek = $this->actingAs($kepsek)->get(route('admin.users.index'));
        $responseKepsek->assertStatus(403);
    }

    public function test_admin_can_create_new_user_with_role(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.users.store'), [
            'name' => 'Wakil Kepala Sekolah',
            'email' => 'wakasek@sekolah.sch.id',
            'password' => 'secret123',
            'role' => 'kepala_sekolah',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'name' => 'Wakil Kepala Sekolah',
            'email' => 'wakasek@sekolah.sch.id',
            'role' => 'kepala_sekolah',
        ]);

        $newUser = User::where('email', 'wakasek@sekolah.sch.id')->first();
        $this->assertTrue(Hash::check('secret123', $newUser->password));
    }

    public function test_admin_can_update_user_details(): void
    {
        $targetUser = User::factory()->create([
            'name' => 'Nama Lama',
            'email' => 'lama@sekolah.sch.id',
            'role' => 'guru',
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.users.update', $targetUser->id), [
            'name' => 'Nama Baru Diperbarui',
            'email' => 'baru@sekolah.sch.id',
            'role' => 'kepala_sekolah',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $targetUser->id,
            'name' => 'Nama Baru Diperbarui',
            'email' => 'baru@sekolah.sch.id',
            'role' => 'kepala_sekolah',
        ]);
    }

    public function test_admin_can_reset_user_password(): void
    {
        $targetUser = User::factory()->create([
            'password' => Hash::make('oldpassword'),
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.users.reset_password', $targetUser->id), [
            'new_password' => 'passwordBaru2026',
        ]);

        $response->assertRedirect();
        $targetUser->refresh();
        $this->assertTrue(Hash::check('passwordBaru2026', $targetUser->password));
    }

    public function test_admin_can_delete_user(): void
    {
        $targetUser = User::factory()->create([
            'name' => 'User Mau Dihapus',
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.users.destroy', $targetUser->id));

        $response->assertRedirect();
        $this->assertDatabaseMissing('users', [
            'id' => $targetUser->id,
        ]);
    }

    public function test_admin_cannot_delete_their_own_active_account(): void
    {
        $response = $this->actingAs($this->admin)->delete(route('admin.users.destroy', $this->admin->id));

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $this->admin->id,
        ]);
    }
}
