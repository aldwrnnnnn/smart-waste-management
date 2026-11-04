<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WasteReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_api_register_and_login_and_logout_flow(): void
    {
        // 1. Register new mobile user
        $registerResponse = $this->postJson('/api/register', [
            'name' => 'Andi Pratama',
            'email' => 'andi@waste.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $registerResponse->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'andi@waste.com')
            ->assertJsonPath('data.user.role', 'user')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => ['id', 'name', 'email', 'role'],
                    'access_token',
                    'token_type',
                ],
            ]);

        // 2. Duplicate email validation (422)
        $this->postJson('/api/register', [
            'name' => 'Andi Duplikat',
            'email' => 'andi@waste.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(422)->assertJsonPath('success', false);

        // 3. Invalid login credentials (401)
        $this->postJson('/api/login', [
            'email' => 'andi@waste.com',
            'password' => 'wrong-password',
        ])->assertStatus(401)->assertJsonPath('success', false);

        // 4. Valid login (200)
        $loginResponse = $this->postJson('/api/login', [
            'email' => 'andi@waste.com',
            'password' => 'password123',
        ]);
        $loginResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.token_type', 'Bearer');

        $token = $loginResponse->json('data.access_token');

        // 5. Logout with Bearer token (200)
        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/logout')
            ->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_api_protected_endpoints_reject_unauthenticated_requests(): void
    {
        $this->getJson('/api/dashboard-summary')->assertStatus(401);
        $this->getJson('/api/reports')->assertStatus(401);
        $this->postJson('/api/reports')->assertStatus(401);
        $this->postJson('/api/reports/detect')->assertStatus(401);
    }

    public function test_api_dashboard_summary_and_reports_filter_and_detail(): void
    {
        $user = User::where('email', 'user@waste.com')->firstOrFail();
        $otherUser = User::where('email', 'siti@waste.com')->firstOrFail();

        Sanctum::actingAs($user);

        $expectedTotal = WasteReport::where('user_id', $user->id)->count();
        $expectedPending = WasteReport::where('user_id', $user->id)->where('status', 'Pending')->count();

        // Dashboard summary
        $summaryResponse = $this->getJson('/api/dashboard-summary');
        $summaryResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_reports', $expectedTotal)
            ->assertJsonPath('data.status_counts.Pending', $expectedPending);

        // Reports list with status filter
        $listResponse = $this->getJson('/api/reports?status=Pending');
        $listResponse->assertStatus(200)
            ->assertJsonPath('success', true);

        foreach ($listResponse->json('data') as $item) {
            $this->assertEquals('Pending', $item['status']);
            $this->assertEquals($user->id, $item['user_id']);
        }

        // Report detail for own report
        $ownReport = WasteReport::where('user_id', $user->id)->firstOrFail();
        $this->getJson("/api/reports/{$ownReport->id}")
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $ownReport->id);

        // Cannot view another user's report
        $otherReport = WasteReport::where('user_id', $otherUser->id)->firstOrFail();
        $this->getJson("/api/reports/{$otherReport->id}")
            ->assertStatus(404)
            ->assertJsonPath('success', false);

        // Admin sees all reports across all users in dashboard-summary and index
        $admin = User::where('email', 'admin@waste.com')->firstOrFail();
        Sanctum::actingAs($admin);
        $allTotal = WasteReport::count();

        $this->getJson('/api/dashboard-summary')
            ->assertStatus(200)
            ->assertJsonPath('data.total_reports', $allTotal);

        $this->getJson('/api/reports?per_page=50')
            ->assertStatus(200)
            ->assertJsonPath('meta.total', $allTotal);
    }

    public function test_api_detect_and_create_report_endpoints(): void
    {
        Storage::fake('public');
        $user = User::where('email', 'user@waste.com')->firstOrFail();
        Sanctum::actingAs($user);

        Http::fake([
            '*/predict' => Http::response([
                'success' => true,
                'message' => 'Prediction successful',
                'data' => [
                    'waste_type' => 'Plastic Waste',
                    'confidence_score' => 0.9912,
                    'all_probabilities' => [
                        'Organic Waste' => 0.0010,
                        'Paper Waste' => 0.0078,
                        'Plastic Waste' => 0.9912,
                    ],
                ],
                'waste_type' => 'Plastic Waste',
                'confidence_score' => 0.9912,
            ], 200),
        ]);

        // 1. POST /api/reports/detect
        $detectResponse = $this->postJson('/api/reports/detect', [
            'photo' => UploadedFile::fake()->image('botol_plastik.jpg', 500, 500),
        ]);

        $detectResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.waste_type', 'Plastic Waste')
            ->assertJsonPath('data.confidence_score', 0.9912);

        // 2. POST /api/reports (create report with multipart photo + coordinates)
        $createResponse = $this->postJson('/api/reports', [
            'photo' => UploadedFile::fake()->image('botol_plastik.jpg', 640, 480),
            'latitude' => -6.20876340,
            'longitude' => 106.84559900,
            'address' => 'Halte Kuningan Madya, Jakarta Selatan',
            'description' => 'Tumpukan botol plastik air mineral di samping tempat sampah.',
            'waste_type' => 'Plastic Waste',
            'confidence_score' => 0.9912,
        ]);

        $createResponse->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.waste_type', 'Plastic Waste')
            ->assertJsonPath('data.status', 'Pending');

        $savedPath = $createResponse->json('data.photo_path');
        Storage::disk('public')->assertExists($savedPath);

        $createdId = $createResponse->json('data.id');

        // 3. DELETE /api/reports/{id} fails if report is already processed (In Progress / Completed)
        WasteReport::where('id', $createdId)->update(['status' => 'In Progress']);
        $this->deleteJson("/api/reports/{$createdId}")
            ->assertStatus(422)
            ->assertJsonPath('success', false);
        $this->assertDatabaseHas('waste_reports', ['id' => $createdId]);

        // 4. DELETE /api/reports/{id} succeeds when status is Pending
        WasteReport::where('id', $createdId)->update(['status' => 'Pending']);
        $this->deleteJson("/api/reports/{$createdId}")
            ->assertStatus(200)
            ->assertJsonPath('success', true);
        $this->assertDatabaseMissing('waste_reports', ['id' => $createdId]);

        // 5. Admin can delete report of any status (even Completed)
        $admin = User::where('role', 'admin')->firstOrFail();
        $completedReport = WasteReport::create([
            'user_id' => $user->id,
            'photo_path' => 'reports/sample_organic.jpg',
            'waste_type' => 'Organic Waste',
            'confidence_score' => 0.88,
            'latitude' => -6.20876340,
            'longitude' => 106.84559900,
            'description' => 'Laporan uji coba status completed yang dihapus admin.',
            'status' => 'Completed',
        ]);

        Sanctum::actingAs($admin);
        $this->deleteJson("/api/reports/{$completedReport->id}")
            ->assertStatus(200)
            ->assertJsonPath('success', true);
        $this->assertDatabaseMissing('waste_reports', ['id' => $completedReport->id]);
    }
}
