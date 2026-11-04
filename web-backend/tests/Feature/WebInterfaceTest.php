<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WasteReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WebInterfaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_login_and_register_pages_render_and_guests_are_redirected(): void
    {
        $this->get('/login')->assertStatus(200)->assertSee('Masuk ke Akun');
        $this->get('/register')->assertStatus(200)->assertSee('Buat Akun Pelapor');

        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->get('/admin/dashboard')->assertRedirect(route('login'));
        $this->get('/reports')->assertRedirect(route('login'));
        $this->get('/admin/users')->assertRedirect(route('login'));
    }

    public function test_role_based_login_redirection_and_middleware_protection(): void
    {
        $admin = User::where('email', 'admin@waste.com')->firstOrFail();
        $user = User::where('email', 'user@waste.com')->firstOrFail();

        $this->post('/login', ['email' => 'admin@waste.com', 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard'));

        $this->post('/logout')->assertRedirect(route('login'));

        $this->post('/login', ['email' => 'user@waste.com', 'password' => 'password'])
            ->assertRedirect(route('user.dashboard'));

        $this->actingAs($admin)->get('/dashboard')
            ->assertRedirect(route('admin.dashboard'));

        $this->actingAs($user)->get('/admin/dashboard')
            ->assertRedirect(route('user.dashboard'));

        $this->actingAs($user)->get('/admin/users')
            ->assertRedirect(route('user.dashboard'));
    }

    public function test_admin_and_user_dashboards_render_accurate_statistics_and_charts(): void
    {
        $admin = User::where('email', 'admin@waste.com')->firstOrFail();
        $user = User::where('email', 'user@waste.com')->firstOrFail();

        $totalInDb = WasteReport::count();
        $userTotalInDb = WasteReport::where('user_id', $user->id)->count();

        $this->actingAs($admin)->get('/admin/dashboard')
            ->assertStatus(200)
            ->assertSee((string) $totalInDb)
            ->assertSee('wasteCategoryChart')
            ->assertSee('statusDistributionChart');

        $this->actingAs($user)->get('/dashboard')
            ->assertStatus(200)
            ->assertSee((string) $userTotalInDb)
            ->assertSee('userCategoryChart')
            ->assertSee('userStatusChart');
    }

    public function test_reports_crud_filter_search_ai_detection_and_offline_fallback(): void
    {
        Storage::fake('public');

        $admin = User::where('email', 'admin@waste.com')->firstOrFail();
        $user1 = User::where('email', 'user@waste.com')->firstOrFail();
        $user2 = User::where('email', 'siti@waste.com')->firstOrFail();

        // Filter & Search
        $this->actingAs($admin)
            ->get('/reports?search=plastik&waste_type=Plastic+Waste&status=Pending')
            ->assertStatus(200);

        // Web AI Detect Proxy (Success)
        Http::fake([
            '*/predict' => Http::response([
                'success' => true,
                'data' => [
                    'waste_type' => 'Organic Waste',
                    'confidence_score' => 0.965,
                    'all_probabilities' => [
                        'Organic Waste' => 0.965,
                        'Paper Waste' => 0.020,
                        'Plastic Waste' => 0.015,
                    ],
                ],
            ], 200),
        ]);

        $detectResponse = $this->actingAs($user1)->postJson('/reports/detect-ai', [
            'photo' => UploadedFile::fake()->image('sampah_organik.jpg', 400, 400),
        ]);
        $detectResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.waste_type', 'Organic Waste');

        // Store report with automatic AI detection
        $storeResponse = $this->actingAs($user1)->post('/reports', [
            'photo' => UploadedFile::fake()->image('temuan.jpg', 640, 480),
            'latitude' => -6.20123456,
            'longitude' => 106.82123456,
            'address' => 'Jl. Kebon Sirih No. 12',
            'description' => 'Tumpukan sampah daun dan sisa sayuran pasar.',
            'waste_type' => '',
        ]);
        $storeResponse->assertRedirect();

        $createdReport = WasteReport::where('address', 'Jl. Kebon Sirih No. 12')->firstOrFail();
        $this->assertEquals('Organic Waste', $createdReport->waste_type);
        $this->assertEquals('Pending', $createdReport->status);

        // Ownership isolation: user2 cannot view or edit user1's report
        $this->actingAs($user2)->get("/reports/{$createdReport->id}")->assertStatus(403);
        $this->actingAs($user2)->get("/reports/{$createdReport->id}/edit")->assertStatus(403);

        // Owner can update report
        $this->actingAs($user1)->put("/reports/{$createdReport->id}", [
            'latitude' => -6.20123456,
            'longitude' => 106.82123456,
            'address' => 'Jl. Kebon Sirih No. 12A',
            'description' => 'Tumpukan sampah daun telah dikumpulkan dalam karung.',
            'waste_type' => 'Organic Waste',
            'confidence_score' => 0.965,
        ])->assertRedirect(route('reports.show', $createdReport));

        // Admin status update (Pending -> In Progress -> Completed)
        $this->actingAs($admin)->patch("/reports/{$createdReport->id}/status", [
            'status' => 'Completed',
            'admin_notes' => 'Telah diangkut oleh petugas dinas kebersihan.',
        ])->assertRedirect();

        $this->assertEquals('Completed', $createdReport->fresh()->status);

        // Processed / Completed report does not show delete or edit option for regular user
        $this->actingAs($user1)->get("/reports/{$createdReport->id}")
            ->assertStatus(200)
            ->assertDontSee('Hapus Laporan Ini')
            ->assertDontSee('Edit Laporan')
            ->assertDontSee('Laporan ini tidak dapat dihapus');

        // Processed / Completed report cannot be edited or updated by regular user
        $this->actingAs($user1)->get("/reports/{$createdReport->id}/edit")
            ->assertRedirect(route('reports.show', $createdReport))
            ->assertSessionHas('error');

        $this->actingAs($user1)->put("/reports/{$createdReport->id}", [
            'latitude' => -6.20123456,
            'longitude' => 106.82123456,
            'address' => 'Jl. Kebon Sirih No. 12A Diubah',
            'description' => 'Percobaan mengubah deskripsi setelah laporan selesai diproses.',
            'waste_type' => 'Plastic Waste',
        ])->assertRedirect(route('reports.show', $createdReport))
            ->assertSessionHas('error');

        $this->assertEquals('Organic Waste', $createdReport->fresh()->waste_type);

        // Processed / Completed report cannot be deleted by citizen
        $this->actingAs($user1)->delete("/reports/{$createdReport->id}")
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertDatabaseHas('waste_reports', ['id' => $createdReport->id]);

        // Admin CAN view edit & delete options on Completed report and CAN delete it
        $this->actingAs($admin)->get("/reports/{$createdReport->id}")
            ->assertStatus(200)
            ->assertSee('Edit Laporan')
            ->assertSee('Hapus Laporan Ini');

        $this->actingAs($admin)->get("/reports/{$createdReport->id}/edit")
            ->assertStatus(200);

        $this->actingAs($admin)->delete("/reports/{$createdReport->id}")
            ->assertRedirect(route('reports.index'))
            ->assertSessionHas('success');
        $this->assertDatabaseMissing('waste_reports', ['id' => $createdReport->id]);

        // Pending report can be deleted by user
        $pendingReport = WasteReport::create([
            'user_id' => $user1->id,
            'photo_path' => 'reports/sample_organic.jpg',
            'waste_type' => 'Organic Waste',
            'confidence_score' => 0.95,
            'latitude' => -6.20123456,
            'longitude' => 106.82123456,
            'address' => 'Jl. Kebon Sirih No. 99',
            'description' => 'Laporan masih pending dan dapat dihapus.',
            'status' => 'Pending',
        ]);

        $this->actingAs($user1)->get("/reports/{$pendingReport->id}")
            ->assertStatus(200)
            ->assertSee('Hapus Laporan Ini');

        $this->actingAs($user1)->delete("/reports/{$pendingReport->id}")
            ->assertRedirect(route('reports.index'))
            ->assertSessionHas('success');
        $this->assertDatabaseMissing('waste_reports', ['id' => $pendingReport->id]);
    }

    public function test_web_ai_detection_graceful_fallback_when_fastapi_offline(): void
    {
        Storage::fake('public');
        $user = User::where('email', 'user@waste.com')->firstOrFail();

        Http::fake([
            '*/predict' => Http::response(['detail' => 'Service down'], 503),
        ]);

        $this->actingAs($user)->postJson('/reports/detect-ai', [
            'photo' => UploadedFile::fake()->image('offline.jpg', 300, 300),
        ])->assertStatus(503)->assertJsonPath('success', false);

        // User can still submit manually by choosing waste_type even when AI is down
        $this->actingAs($user)->post('/reports', [
            'photo' => UploadedFile::fake()->image('manual.jpg', 300, 300),
            'latitude' => -6.20000000,
            'longitude' => 106.81666600,
            'address' => 'Jl. Thamrin No. 5',
            'description' => 'Laporan manual saat server AI sedang pemeliharaan.',
            'waste_type' => 'Paper Waste',
        ])->assertRedirect();

        $this->assertDatabaseHas('waste_reports', [
            'address' => 'Jl. Thamrin No. 5',
            'waste_type' => 'Paper Waste',
        ]);
    }

    public function test_admin_user_management_crud_and_self_protection(): void
    {
        Storage::fake('public');
        $admin = User::where('email', 'admin@waste.com')->firstOrFail();

        $this->actingAs($admin)->get('/admin/users')
            ->assertStatus(200)
            ->assertSee('Manajemen Pengguna');

        $tempEmail = 'temp_test_user@waste.com';

        $this->actingAs($admin)->post('/admin/users', [
            'name' => 'Temp Tester',
            'email' => $tempEmail,
            'role' => 'user',
            'password' => 'password123',
        ])->assertRedirect(route('admin.users.index'));

        $tempUser = User::where('email', $tempEmail)->firstOrFail();
        $tempUser->createToken('test-device');
        $this->assertEquals(1, $tempUser->tokens()->count());

        $this->actingAs($admin)->put("/admin/users/{$tempUser->id}", [
            'name' => 'Temp Tester Updated',
            'email' => $tempEmail,
            'role' => 'admin',
            'password' => '',
        ])->assertRedirect(route('admin.users.index'));

        $this->assertEquals('admin', $tempUser->fresh()->role);
        // Token must be revoked when role changes
        $this->assertEquals(0, $tempUser->tokens()->count());

        // Create custom report photo & sample report photo for tempUser
        Storage::disk('public')->put('reports/custom_temp.jpg', 'fake-image-bytes');
        Storage::disk('public')->put('reports/sample_organic.jpg', 'sample-image-bytes');
        WasteReport::create([
            'user_id' => $tempUser->id,
            'photo_path' => 'reports/custom_temp.jpg',
            'waste_type' => 'Plastic Waste',
            'latitude' => -6.2,
            'longitude' => 106.8,
            'description' => 'Laporan custom milik temp user.',
            'status' => 'Pending',
        ]);
        WasteReport::create([
            'user_id' => $tempUser->id,
            'photo_path' => 'reports/sample_organic.jpg',
            'waste_type' => 'Organic Waste',
            'latitude' => -6.2,
            'longitude' => 106.8,
            'description' => 'Laporan sample milik temp user.',
            'status' => 'Pending',
        ]);

        // Admin cannot delete their own active account
        $this->actingAs($admin)->delete("/admin/users/{$admin->id}")
            ->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $admin->id]);

        $this->actingAs($admin)->delete("/admin/users/{$tempUser->id}")
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseMissing('users', ['email' => $tempEmail]);
        Storage::disk('public')->assertMissing('reports/custom_temp.jpg');
        Storage::disk('public')->assertExists('reports/sample_organic.jpg');
    }
}
