<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Uji fungsi helper normalisasi_nomor_hp.
     */
    public function test_normalisasi_nomor_hp_helper(): void
    {
        $this->assertEquals('081234567890', normalisasi_nomor_hp('+6281234567890'));
        $this->assertEquals('081234567890', normalisasi_nomor_hp('6281234567890'));
        $this->assertEquals('081234567890', normalisasi_nomor_hp('81234567890'));
        $this->assertEquals('081234567890', normalisasi_nomor_hp('0812-3456-7890'));
        $this->assertEquals('081234567890', normalisasi_nomor_hp('0812 3456 7890'));
        $this->assertEquals('', normalisasi_nomor_hp(''));
        $this->assertEquals('', normalisasi_nomor_hp(null));
    }

    /**
     * Uji pendaftaran customer baru via POST /daftar.
     */
    public function test_user_can_register(): void
    {
        $response = $this->post('/daftar', [
            'name' => 'Pemai Baru',
            'phone' => '+62 813-1122-3344',
            'email' => 'pemain@baru.test',
            'team_name' => 'PB Rajawali',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertRedirect('/akun');
        $this->assertAuthenticated();

        $user = User::where('email', 'pemain@baru.test')->first();
        $this->assertNotNull($user);
        $this->assertEquals('081311223344', $user->phone); // Ternormalisasi
        $this->assertEquals(UserRole::CUSTOMER, $user->role);
        $this->assertTrue($user->is_active);
    }

    /**
     * Uji login menggunakan berbagai format nomor HP (+62, 62, tanda hubung).
     */
    public function test_user_can_login_with_phone_variations(): void
    {
        User::create([
            'name' => 'User HP',
            'phone' => '081234567890',
            'role' => UserRole::CUSTOMER,
            'password' => Hash::make('password123'),
        ]);

        // 1. Login dengan format +62
        $res1 = $this->post('/masuk', [
            'login' => '+62 812-3456-7890',
            'password' => 'password123',
        ]);
        $res1->assertRedirect('/akun');
        $this->assertAuthenticated();
        Auth::logout();

        // 2. Login dengan format 62
        $res2 = $this->post('/masuk', [
            'login' => '6281234567890',
            'password' => 'password123',
        ]);
        $res2->assertRedirect('/akun');
        $this->assertAuthenticated();
    }

    /**
     * Uji login menggunakan email.
     */
    public function test_user_can_login_with_email(): void
    {
        User::create([
            'name' => 'User Email',
            'phone' => '081234567891',
            'email' => 'user@email.test',
            'role' => UserRole::CUSTOMER,
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post('/masuk', [
            'login' => 'USER@EMAIL.TEST', // Case insensitive
            'password' => 'password123',
        ]);

        $response->assertRedirect('/akun');
        $this->assertAuthenticated();
    }

    /**
     * Uji akun yang dinonaktifkan (is_active = false) ditolak saat mencoba login.
     */
    public function test_inactive_account_cannot_login(): void
    {
        User::create([
            'name' => 'User Nonaktif',
            'phone' => '081234567892',
            'role' => UserRole::CUSTOMER,
            'is_active' => false,
            'password' => Hash::make('password123'),
        ]);

        $response = $this->from('/masuk')->post('/masuk', [
            'login' => '081234567892',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/masuk');
        $response->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    /**
     * Uji akun yang dinonaktifkan otomatis keluar (logout) pada request berikutnya.
     */
    public function test_inactive_account_is_logged_out_on_next_request(): void
    {
        $user = User::create([
            'name' => 'User Login',
            'phone' => '081234567893',
            'role' => UserRole::CUSTOMER,
            'is_active' => true,
            'password' => Hash::make('password123'),
        ]);

        $this->actingAs($user);
        $this->assertAuthenticated();

        // Admin menonaktifkan user
        $user->update(['is_active' => false]);

        // Request ke /akun harus ditolak middleware EnsureAccountIsActive dan user dikeluarkan
        $response = $this->get('/akun');
        $response->assertRedirect('/masuk');
        $this->assertGuest();
    }

    /**
     * Uji rate limiting login (5 percobaan gagal memicu pesan batas waktu).
     */
    public function test_login_rate_limiting(): void
    {
        User::create([
            'name' => 'User Throttle',
            'phone' => '081234567894',
            'role' => UserRole::CUSTOMER,
            'password' => Hash::make('password123'),
        ]);

        // 5 kali percobaan salah
        for ($i = 0; $i < 5; $i++) {
            $this->from('/masuk')->post('/masuk', [
                'login' => '081234567894',
                'password' => 'wrong-pass',
            ]);
        }

        // Percobaan ke-6 harus terkena blokir rate limit
        $response = $this->from('/masuk')->post('/masuk', [
            'login' => '081234567894',
            'password' => 'wrong-pass',
        ]);

        $response->assertRedirect('/masuk');
        $response->assertSessionHasErrors('login');

        $errorMessage = session('errors')->first('login');
        $this->assertStringContainsString('Terlalu banyak percobaan masuk', $errorMessage);
    }

    /**
     * Uji pengalihan setelah login sesuai peran masing-masing.
     */
    public function test_redirect_based_on_role(): void
    {
        // 1. Admin ke /admin
        $admin = User::create([
            'name' => 'Admin Role',
            'phone' => '081100000001',
            'role' => UserRole::ADMIN,
            'password' => Hash::make('password'),
        ]);
        $resAdmin = $this->post('/masuk', ['login' => '081100000001', 'password' => 'password']);
        $resAdmin->assertRedirect('/admin');
        Auth::logout();

        // 2. Owner ke /owner
        $owner = User::create([
            'name' => 'Owner Role',
            'phone' => '081100000004',
            'role' => UserRole::OWNER,
            'password' => Hash::make('password'),
        ]);
        $resOwner = $this->post('/masuk', ['login' => '081100000004', 'password' => 'password']);
        $resOwner->assertRedirect('/owner');
        Auth::logout();

        // 3. Customer ke /akun
        $customer = User::create([
            'name' => 'Customer Role',
            'phone' => '081100000003',
            'role' => UserRole::CUSTOMER,
            'password' => Hash::make('password'),
        ]);
        $resCustomer = $this->post('/masuk', ['login' => '081100000003', 'password' => 'password']);
        $resCustomer->assertRedirect('/akun');
    }

    /**
     * Uji intended URL (tamu mencoba buka /akun lalu setelah login langsung ke /akun).
     */
    public function test_intended_url_redirect(): void
    {
        $user = User::create([
            'name' => 'Intended User',
            'phone' => '081234567895',
            'role' => UserRole::CUSTOMER,
            'password' => Hash::make('password123'),
        ]);

        // Buka /akun saat belum login -> redirect ke /masuk
        $res1 = $this->get('/akun');
        $res1->assertRedirect('/masuk');

        // Login -> dialihkan ke intended URL (/akun)
        $res2 = $this->post('/masuk', [
            'login' => '081234567895',
            'password' => 'password123',
        ]);
        $res2->assertRedirect('/akun');
    }

    /**
     * Uji pembatasan hak akses middleware CheckRole (403 bergaya sistem desain).
     */
    public function test_role_middleware_blocks_unauthorized_users(): void
    {
        $customer = User::create([
            'name' => 'Customer User',
            'phone' => '081234567896',
            'role' => UserRole::CUSTOMER,
            'password' => Hash::make('password'),
        ]);

        // Customer dilarang buka /admin dan /owner
        $this->actingAs($customer);
        $this->get('/admin')->assertStatus(403)->assertSee('Akses Dibatasi');
        $this->get('/owner')->assertStatus(403)->assertSee('Akses Dibatasi');

        // Owner dilarang buka /admin
        $owner = User::create([
            'name' => 'Owner User',
            'phone' => '081234567897',
            'role' => UserRole::OWNER,
            'password' => Hash::make('password'),
        ]);
        $this->actingAs($owner);
        $this->get('/admin')->assertStatus(403);
        $this->get('/owner')->assertStatus(200); // Boleh buka /owner

        // Admin boleh buka /admin
        $admin = User::create([
            'name' => 'Admin User',
            'phone' => '081234567898',
            'role' => UserRole::ADMIN,
            'password' => Hash::make('password'),
        ]);
        $this->actingAs($admin);
        $this->get('/admin')->assertStatus(200);
    }

    /**
     * Uji keluar (POST /keluar).
     */
    public function test_logout_destroys_session(): void
    {
        $user = User::create([
            'name' => 'User Logout',
            'phone' => '081234567899',
            'role' => UserRole::CUSTOMER,
            'password' => Hash::make('password'),
        ]);

        $this->actingAs($user);
        $this->assertAuthenticated();

        $response = $this->post('/keluar');
        $response->assertRedirect('/');
        $this->assertGuest();
    }

    /**
     * Uji Artisan command gor:buat-akun dan alias gor:buat-admin.
     */
    public function test_artisan_commands_create_account(): void
    {
        // 1. gor:buat-akun dengan opsi
        $this->artisan('gor:buat-akun', [
            '--nama' => 'Admin CLI',
            '--hp' => '081999888777',
            '--password' => 'password123',
            '--peran' => 'admin',
        ])->assertExitCode(0);

        $admin = User::where('phone', '081999888777')->first();
        $this->assertNotNull($admin);
        $this->assertEquals(UserRole::ADMIN, $admin->role);

        // 2. gor:buat-admin alias
        $this->artisan('gor:buat-admin', [
            '--nama' => 'Admin Alias CLI',
            '--hp' => '081999888666',
            '--password' => 'password123',
        ])->assertExitCode(0);

        $aliasAdmin = User::where('phone', '081999888666')->first();
        $this->assertNotNull($aliasAdmin);
        $this->assertEquals(UserRole::ADMIN, $aliasAdmin->role);
    }

    /**
     * Uji update profil dan ganti password di /akun.
     */
    public function test_user_can_update_profile_and_password(): void
    {
        $user = User::create([
            'name' => 'User Akun',
            'phone' => '081234567800',
            'role' => UserRole::CUSTOMER,
            'password' => Hash::make('oldpassword'),
        ]);

        $this->actingAs($user);

        // Update profil
        $resProfil = $this->from('/akun')->put('/akun/profil', [
            'name' => 'User Akun Update',
            'team_name' => 'PB Garuda Baru',
            'email' => 'garuda@test.com',
        ]);
        $resProfil->assertRedirect();
        $this->assertEquals('User Akun Update', $user->fresh()->name);
        $this->assertEquals('PB Garuda Baru', $user->fresh()->team_name);

        // Update password gagal karena password lama salah
        $resPassFail = $this->put('/akun/password', [
            'current_password' => 'wrongpass',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);
        $resPassFail->assertSessionHasErrors('current_password');

        // Update password berhasil dengan password lama yang benar
        $resPassOk = $this->put('/akun/password', [
            'current_password' => 'oldpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);
        $resPassOk->assertSessionHas('status_password');
        $this->assertTrue(Hash::check('newpassword123', $user->fresh()->password));
    }
}
