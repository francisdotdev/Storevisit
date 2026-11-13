<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationAndUserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Role $adminRole;
    protected Role $supervisorRole;
    protected Role $managerRole;
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed basic roles for test environment
        $this->adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name' => 'Administrator', 'description' => 'System Administrator']
        );

        $this->managerRole = Role::firstOrCreate(
            ['name' => 'manager'],
            ['display_name' => 'Manager', 'description' => 'Operations Manager']
        );

        $this->supervisorRole = Role::firstOrCreate(
            ['name' => 'area_treasury_supervisor'],
            ['display_name' => 'Area Treasury Supervisor', 'description' => 'Treasury Supervisor']
        );

        // Seed admin user
        $this->adminUser = User::create([
            'employee_id' => 'ADMIN01',
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'email' => 'admin@lcc.com.ph',
            'password' => Hash::make('password'),
            'role_id' => $this->adminRole->id,
            'branch_code' => 'HQ',
            'status' => 'active',
            'approved_at' => now(),
        ]);
    }

    public function test_public_can_fetch_roles_list(): void
    {
        $response = $this->getJson('/api/auth/roles');

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'admin'])
            ->assertJsonFragment(['name' => 'area_treasury_supervisor']);
    }

    public function test_registration_creates_pending_user_per_brd(): void
    {
        $payload = [
            'employee_id' => 'SUP001',
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'email' => 'maria.santos@lcc.com.ph',
            'branch_code' => 'BR-LEG-01',
            'role_id' => $this->supervisorRole->id,
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
        ];

        $response = $this->postJson('/api/auth/register', $payload);

        $response->assertStatus(201)
            ->assertJsonFragment(['status' => 'pending']);

        $this->assertDatabaseHas('users', [
            'employee_id' => 'SUP001',
            'email' => 'maria.santos@lcc.com.ph',
            'status' => 'pending',
            'approved_by' => null,
            'approved_at' => null,
        ]);
    }

    public function test_pending_user_cannot_login_and_receives_403_status(): void
    {
        User::create([
            'employee_id' => 'PEND01',
            'first_name' => 'Pedro',
            'last_name' => 'Penduko',
            'email' => 'pedro@lcc.com.ph',
            'password' => Hash::make('password'),
            'role_id' => $this->supervisorRole->id,
            'status' => 'pending',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'login' => 'PEND01',
            'password' => 'password',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'status' => 'pending',
            ]);

        $this->assertGuest();
    }

    public function test_disabled_user_cannot_login_and_receives_403_status(): void
    {
        User::create([
            'employee_id' => 'DIS001',
            'first_name' => 'Disabled',
            'last_name' => 'User',
            'email' => 'disabled@lcc.com.ph',
            'password' => Hash::make('password'),
            'role_id' => $this->supervisorRole->id,
            'status' => 'disabled',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'login' => 'disabled@lcc.com.ph',
            'password' => 'password',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'status' => 'disabled',
            ]);

        $this->assertGuest();
    }

    public function test_login_fails_with_invalid_password(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'login' => 'ADMIN01',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['login']);

        $this->assertGuest();
    }

    public function test_active_user_can_login_with_employee_id_and_email(): void
    {
        // Login via Employee ID
        $response1 = $this->postJson('/api/auth/login', [
            'login' => 'ADMIN01',
            'password' => 'password',
        ]);

        $response1->assertStatus(200)
            ->assertJsonFragment(['employee_id' => 'ADMIN01']);

        $this->assertAuthenticatedAs($this->adminUser);

        // Logout
        $this->postJson('/api/auth/logout')->assertStatus(200);
        $this->assertGuest();

        // Login via Email
        $response2 = $this->postJson('/api/auth/login', [
            'login' => 'admin@lcc.com.ph',
            'password' => 'password',
        ]);

        $response2->assertStatus(200)
            ->assertJsonFragment(['email' => 'admin@lcc.com.ph']);

        $this->assertAuthenticatedAs($this->adminUser);
    }

    public function test_authenticated_user_can_fetch_me_profile(): void
    {
        $response = $this->actingAs($this->adminUser)->getJson('/api/auth/me');

        $response->assertStatus(200)
            ->assertJsonPath('user.email', 'admin@lcc.com.ph')
            ->assertJsonPath('user.role.name', 'admin');
    }

    public function test_non_admin_cannot_access_user_management(): void
    {
        $supervisor = User::create([
            'employee_id' => 'SUP999',
            'first_name' => 'Supervisor',
            'last_name' => 'User',
            'email' => 'sup999@lcc.com.ph',
            'password' => Hash::make('password'),
            'role_id' => $this->supervisorRole->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($supervisor)->getJson('/api/admin/users');

        $response->assertStatus(403);
    }

    public function test_admin_can_view_users_list_and_stats(): void
    {
        // Add pending and disabled users
        User::create([
            'employee_id' => 'U_PEND',
            'first_name' => 'Pending',
            'last_name' => 'One',
            'email' => 'upend@lcc.com.ph',
            'password' => Hash::make('password'),
            'role_id' => $this->supervisorRole->id,
            'status' => 'pending',
        ]);

        User::create([
            'employee_id' => 'U_DIS',
            'first_name' => 'Disabled',
            'last_name' => 'One',
            'email' => 'udis@lcc.com.ph',
            'password' => Hash::make('password'),
            'role_id' => $this->supervisorRole->id,
            'status' => 'disabled',
        ]);

        $response = $this->actingAs($this->adminUser)->getJson('/api/admin/users');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'users' => [
                    'data',
                    'current_page',
                    'total',
                ],
                'stats' => [
                    'total',
                    'pending',
                    'active',
                    'disabled',
                ],
            ])
            ->assertJsonPath('stats.total', 3)
            ->assertJsonPath('stats.pending', 1)
            ->assertJsonPath('stats.active', 1)
            ->assertJsonPath('stats.disabled', 1);
    }

    public function test_admin_can_approve_pending_user(): void
    {
        $pendingUser = User::create([
            'employee_id' => 'PEND02',
            'first_name' => 'Juana',
            'last_name' => 'Change',
            'email' => 'juana@lcc.com.ph',
            'password' => Hash::make('password'),
            'role_id' => $this->supervisorRole->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->postJson("/api/admin/users/{$pendingUser->id}/approve");

        $response->assertStatus(200)
            ->assertJsonPath('user.status', 'active');

        $this->assertDatabaseHas('users', [
            'id' => $pendingUser->id,
            'status' => 'active',
            'approved_by' => $this->adminUser->id,
        ]);
    }

    public function test_admin_can_toggle_user_status_and_cannot_disable_self(): void
    {
        $targetUser = User::create([
            'employee_id' => 'TARGET01',
            'first_name' => 'Target',
            'last_name' => 'User',
            'email' => 'target@lcc.com.ph',
            'password' => Hash::make('password'),
            'role_id' => $this->supervisorRole->id,
            'status' => 'active',
        ]);

        // Toggle Active -> Disabled
        $res1 = $this->actingAs($this->adminUser)
            ->postJson("/api/admin/users/{$targetUser->id}/toggle-status");

        $res1->assertStatus(200)
            ->assertJsonPath('user.status', 'disabled');

        $this->assertEquals('disabled', $targetUser->fresh()->status);

        // Toggle Disabled -> Active
        $res2 = $this->actingAs($this->adminUser)
            ->postJson("/api/admin/users/{$targetUser->id}/toggle-status");

        $res2->assertStatus(200)
            ->assertJsonPath('user.status', 'active');

        $this->assertEquals('active', $targetUser->fresh()->status);

        // Admin cannot toggle self
        $resSelf = $this->actingAs($this->adminUser)
            ->postJson("/api/admin/users/{$this->adminUser->id}/toggle-status");

        $resSelf->assertStatus(422);
    }

    public function test_admin_can_create_user_directly(): void
    {
        $payload = [
            'employee_id' => 'MGR001',
            'first_name' => 'Elena',
            'last_name' => 'Cruz',
            'email' => 'elena.cruz@lcc.com.ph',
            'branch_code' => 'HQ',
            'role_id' => $this->managerRole->id,
            'status' => 'active',
            'password' => 'managerPass123',
        ];

        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/admin/users', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('user.employee_id', 'MGR001')
            ->assertJsonPath('user.status', 'active')
            ->assertJsonPath('user.approved_by', $this->adminUser->id);

        $this->assertDatabaseHas('users', [
            'employee_id' => 'MGR001',
            'status' => 'active',
            'approved_by' => $this->adminUser->id,
        ]);
    }

    public function test_admin_can_update_user_details(): void
    {
        $user = User::create([
            'employee_id' => 'EDIT01',
            'first_name' => 'Original',
            'last_name' => 'Name',
            'email' => 'original@lcc.com.ph',
            'password' => Hash::make('password'),
            'role_id' => $this->supervisorRole->id,
            'status' => 'active',
        ]);

        $payload = [
            'employee_id' => 'EDIT01_UPDATED',
            'first_name' => 'UpdatedFirst',
            'last_name' => 'UpdatedLast',
            'email' => 'updated@lcc.com.ph',
            'branch_code' => 'BR-NAGA-01',
            'role_id' => $this->managerRole->id,
            'status' => 'active',
            'password' => 'newPassword123',
        ];

        $response = $this->actingAs($this->adminUser)
            ->putJson("/api/admin/users/{$user->id}", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('user.employee_id', 'EDIT01_UPDATED')
            ->assertJsonPath('user.first_name', 'UpdatedFirst')
            ->assertJsonPath('user.email', 'updated@lcc.com.ph');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'employee_id' => 'EDIT01_UPDATED',
            'first_name' => 'UpdatedFirst',
            'email' => 'updated@lcc.com.ph',
        ]);

        // Verify updated password works
        $this->assertTrue(Hash::check('newPassword123', $user->fresh()->password));
    }
}

