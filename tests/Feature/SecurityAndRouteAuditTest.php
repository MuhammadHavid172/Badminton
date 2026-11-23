<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\BookingSlot;
use App\Models\Court;
use App\Models\Payment;
use App\Models\PlaySession;
use App\Models\Setting;
use App\Models\SlotBlock;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SecurityAndRouteAuditTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test header keamanan standar terpasang pada seluruh respons HTTP.
     */
    public function test_security_headers_terpasang_pada_respons(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);

        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $this->assertTrue($response->headers->has('Content-Security-Policy-Report-Only'));
    }

    /**
     * Test audit seluruh rute non-publik memiliki proteksi middleware auth dan peran yang tepat.
     */
    public function test_audit_seluruh_rute_non_publik_dilindungi_auth_dan_peran(): void
    {
        $routes = Route::getRoutes();

        foreach ($routes as $route) {
            $uri = $route->uri();
            $middleware = $route->gatherMiddleware();

            // Rute Admin wajib memiliki auth dan role:admin
            if (str_starts_with($uri, 'admin')) {
                $this->assertContains(
                    'auth',
                    $middleware,
                    "Rute admin {$uri} tidak memiliki middleware auth"
                );
                $this->assertTrue(
                    in_array('role:admin', $middleware) || in_array('App\Http\Middleware\EnsureUserRole:admin', $middleware),
                    "Rute admin {$uri} tidak memiliki middleware role:admin"
                );
            }

            // Rute Owner wajib memiliki auth dan role:owner
            if (str_starts_with($uri, 'owner')) {
                $this->assertContains(
                    'auth',
                    $middleware,
                    "Rute owner {$uri} tidak memiliki middleware auth"
                );
                $this->assertTrue(
                    in_array('role:owner', $middleware) || in_array('App\Http\Middleware\EnsureUserRole:owner', $middleware),
                    "Rute owner {$uri} tidak memiliki middleware role:owner"
                );
            }

            // Rute Akun & Notifikasi wajib memiliki auth
            if (str_starts_with($uri, 'akun') || str_starts_with($uri, 'notifikasi')) {
                $this->assertContains(
                    'auth',
                    $middleware,
                    "Rute privat {$uri} tidak memiliki middleware auth"
                );
            }

            // Rute Tamu (masuk & daftar) wajib guest
            if ($uri === 'masuk' || $uri === 'daftar') {
                $this->assertContains(
                    'guest',
                    $middleware,
                    "Rute autentikasi {$uri} tidak memiliki middleware guest"
                );
            }
        }
    }

    /**
     * Test seeder di lingkungan produksi hanya membuat data dasar tanpa akun atau transaksi demo.
     */
    public function test_seeder_lingkungan_produksi_hanya_data_dasar(): void
    {
        // Simulasi environment production
        $this->app['env'] = 'production';

        $seeder = new DatabaseSeeder();
        $seeder->run();

        // Data dasar wajib ada (lapangan, sesi, pengaturan)
        $this->assertGreaterThan(0, Court::count());
        $this->assertGreaterThan(0, PlaySession::count());
        $this->assertNotNull(Setting::get('price_single'));

        // Akun demo dan booking demo TIDAK BOLEH dibuat di production
        $this->assertDatabaseMissing('users', ['phone' => '081100000001']);
        $this->assertDatabaseMissing('users', ['phone' => '081100000003']);
        $this->assertDatabaseMissing('users', ['phone' => '081100000004']);
        $this->assertEquals(0, Booking::count());
        $this->assertEquals(0, BookingSlot::count());
        $this->assertEquals(0, SlotBlock::count());
    }

    /**
     * Test preventLazyLoading aktif pada non-production untuk mencegah masalah N+1 queries.
     */
    public function test_prevent_lazy_loading_aktif_di_non_production(): void
    {
        $this->assertTrue(Model::preventsLazyLoading());
    }

    /**
     * Test halaman error kustom (403, 404, 500) mengikuti sistem desain dan memiliki tepat satu h1.
     */
    public function test_halaman_error_bergaya_desain_dan_satu_h1(): void
    {
        $views = ['errors.403', 'errors.404', 'errors.500'];

        foreach ($views as $viewName) {
            $rendered = view($viewName)->render();

            // Wajib memiliki tepat satu <h1>
            $h1Count = preg_match_all('/<h1[^>]*>/i', $rendered);
            $this->assertEquals(1, $h1Count, "View {$viewName} harus memiliki tepat satu <h1>");

            // Menggunakan token warna sistem desain
            $this->assertStringContainsString('var(--lime)', $rendered);
            $this->assertStringContainsString('Kembali ke Beranda', $rendered);
        }
    }

    /**
     * Test perlindungan mass-assignment pada model-model inti sistem.
     */
    public function test_perlindungan_mass_assignment_model(): void
    {
        $models = [
            User::class,
            Court::class,
            PlaySession::class,
            Booking::class,
            BookingSlot::class,
            SlotBlock::class,
            Payment::class,
            Setting::class,
            ActivityLog::class,
        ];

        foreach ($models as $modelClass) {
            /** @var Model $instance */
            $instance = new $modelClass();
            $fillable = $instance->getFillable();
            $guarded = $instance->getGuarded();

            // Model harus mendefinisikan fillable atau guarded secara eksplisit
            $hasProtection = !empty($fillable) || $guarded !== [];
            $this->assertTrue($hasProtection, "Model {$modelClass} tidak memiliki perlindungan mass assignment.");
        }
    }
}
