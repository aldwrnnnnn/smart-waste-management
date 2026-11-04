<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WasteReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Throwable;

class WasteReportController extends Controller
{
    private const SAMPLE_FILES = [
        'reports/sample_organic.jpg',
        'reports/sample_plastic.jpg',
        'reports/sample_paper.jpg',
    ];
    public function dashboardSummary(Request $request): JsonResponse
    {
        $user = $request->user();
        $baseQuery = WasteReport::query();

        if (! $user->isAdmin()) {
            $baseQuery->where('user_id', $user->id);
        }

        $statusMap = (clone $baseQuery)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $wasteMap = (clone $baseQuery)
            ->selectRaw('waste_type, COUNT(*) as total')
            ->groupBy('waste_type')
            ->pluck('total', 'waste_type');

        $pendingCount = (int) ($statusMap['Pending'] ?? 0);
        $inProgressCount = (int) ($statusMap['In Progress'] ?? 0);
        $completedCount = (int) ($statusMap['Completed'] ?? 0);
        $rejectedCount = (int) ($statusMap['Rejected'] ?? 0);

        $organicCount = (int) ($wasteMap['Organic Waste'] ?? 0);
        $plasticCount = (int) ($wasteMap['Plastic Waste'] ?? 0);
        $paperCount = (int) ($wasteMap['Paper Waste'] ?? 0);

        $recentReports = (clone $baseQuery)
            ->latest()
            ->take(5)
            ->get()
            ->map(fn (WasteReport $report) => $this->formatReport($report, false));

        return response()->json([
            'success' => true,
            'message' => 'Data ringkasan dashboard berhasil diambil',
            'data' => [
                'total_reports' => $pendingCount + $inProgressCount + $completedCount + $rejectedCount,
                'status_counts' => [
                    'Pending' => $pendingCount,
                    'In Progress' => $inProgressCount,
                    'Completed' => $completedCount,
                    'Rejected' => $rejectedCount,
                    'pending' => $pendingCount,
                    'in_progress' => $inProgressCount,
                    'completed' => $completedCount,
                    'rejected' => $rejectedCount,
                ],
                'waste_type_counts' => [
                    'Organic Waste' => $organicCount,
                    'Plastic Waste' => $plasticCount,
                    'Paper Waste' => $paperCount,
                    'organic_waste' => $organicCount,
                    'plastic_waste' => $plasticCount,
                    'paper_waste' => $paperCount,
                ],
                'recent_reports' => $recentReports,
            ],
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = WasteReport::with('user:id,name,email');

        if (! $user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('waste_type')) {
            $query->where('waste_type', $request->query('waste_type'));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->query('search'));
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('description', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%")
                    ->orWhere('waste_type', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%");
            });
        }

        $perPage = max(1, min(50, (int) $request->query('per_page', 10)));
        $paginator = $query->latest()->paginate($perPage);

        $items = collect($paginator->items())->map(
            fn (WasteReport $report) => $this->formatReport($report, true)
        );

        return response()->json([
            'success' => true,
            'message' => 'Daftar laporan berhasil diambil',
            'data' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $query = WasteReport::with('user:id,name,email');

        if (! $user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        $report = $query->find($id);

        if (! $report) {
            return response()->json([
                'success' => false,
                'message' => 'Laporan sampah tidak ditemukan',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail laporan berhasil diambil',
            'data' => $this->formatReport($report, true),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $query = WasteReport::query();

        if (! $user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        $report = $query->find($id);

        if (! $report) {
            return response()->json([
                'success' => false,
                'message' => 'Laporan sampah tidak ditemukan',
                'data' => null,
            ], 404);
        }

        if (! $report->canBeDeletedBy($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Laporan tidak dapat dihapus karena sudah diproses oleh admin (status: ' . $report->status . ').',
                'data' => null,
            ], 422);
        }

        $this->deleteStoredPhotoIfCustom($report->photo_path);
        $report->delete();

        return response()->json([
            'success' => true,
            'message' => 'Laporan sampah #' . $id . ' berhasil dihapus',
            'data' => null,
        ]);
    }

    public function detect(Request $request): JsonResponse
    {
        $fileKey = $request->hasFile('photo') ? 'photo' : 'file';

        $validator = Validator::make($request->all(), [
            $fileKey => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'File gambar tidak valid',
                'errors' => $validator->errors(),
            ], 422);
        }

        /** @var UploadedFile $imageFile */
        $imageFile = $request->file($fileKey);
        $prediction = $this->callMlService($imageFile);

        if (! $prediction['ok']) {
            return response()->json([
                'success' => false,
                'message' => $prediction['message'],
                'data' => null,
            ], 503);
        }

        return response()->json([
            'success' => true,
            'message' => 'Deteksi jenis sampah berhasil',
            'data' => [
                'waste_type' => $prediction['waste_type'],
                'confidence_score' => $prediction['confidence_score'],
                'all_probabilities' => $prediction['all_probabilities'],
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'address' => ['nullable', 'string', 'max:1000'],
            'description' => ['required', 'string', 'min:5', 'max:5000'],
            'waste_type' => ['nullable', Rule::in(['Organic Waste', 'Plastic Waste', 'Paper Waste'])],
            'confidence_score' => ['nullable', 'numeric', 'between:0,1'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Data laporan tidak valid',
                'errors' => $validator->errors(),
            ], 422);
        }

        /** @var UploadedFile $photo */
        $photo = $request->file('photo');
        $wasteType = $request->input('waste_type');
        $confidenceScore = $request->filled('confidence_score')
            ? (float) $request->input('confidence_score')
            : null;

        if (! $wasteType) {
            $prediction = $this->callMlService($photo);

            if (! $prediction['ok']) {
                return response()->json([
                    'success' => false,
                    'message' => $prediction['message'],
                    'data' => null,
                ], 503);
            }

            $wasteType = $prediction['waste_type'];
            $confidenceScore = $prediction['confidence_score'];
        }

        $photoPath = $photo->store('reports', 'public');

        $report = WasteReport::create([
            'user_id' => $request->user()->id,
            'photo_path' => $photoPath,
            'waste_type' => $wasteType,
            'confidence_score' => $confidenceScore,
            'latitude' => (float) $request->input('latitude'),
            'longitude' => (float) $request->input('longitude'),
            'address' => $request->input('address'),
            'description' => $request->input('description'),
            'status' => 'Pending',
            'admin_notes' => null,
        ]);

        $report->load('user:id,name,email');

        return response()->json([
            'success' => true,
            'message' => 'Laporan sampah berhasil disimpan',
            'data' => $this->formatReport($report, true),
        ], 201);
    }

    /**
     * @return array{ok: bool, waste_type?: string, confidence_score?: float, all_probabilities?: array<string, float>, message?: string}
     */
    private function callMlService(UploadedFile $file): array
    {
        $mlUrl = rtrim((string) config('services.ml_service.url', 'http://127.0.0.1:8001'), '/');

        try {
            $response = Http::connectTimeout(3)->timeout(10)
                ->attach(
                    'file',
                    file_get_contents($file->getRealPath()),
                    $file->getClientOriginalName() ?: 'waste.jpg'
                )
                ->post("{$mlUrl}/predict");

            if (! $response->successful()) {
                return [
                    'ok' => false,
                    'message' => 'Gagal memproses prediksi gambar pada layanan AI.',
                ];
            }

            $payload = $response->json();
            $data = $payload['data'] ?? $payload;

            return [
                'ok' => true,
                'waste_type' => $data['waste_type'] ?? 'Plastic Waste',
                'confidence_score' => isset($data['confidence_score']) ? (float) $data['confidence_score'] : 0.0,
                'all_probabilities' => $data['all_probabilities'] ?? [],
            ];
        } catch (Throwable) {
            return [
                'ok' => false,
                'message' => 'Layanan deteksi AI (FastAPI) sedang tidak dapat diakses. Pastikan server ML aktif di ' . $mlUrl,
            ];
        }
    }

    private function formatReport(WasteReport $report, bool $includeUser): array
    {
        $result = [
            'id' => $report->id,
            'user_id' => $report->user_id,
            'photo_path' => $report->photo_path,
            'photo_url' => $report->photo_url,
            'waste_type' => $report->waste_type,
            'confidence_score' => $report->confidence_score,
            'latitude' => (float) $report->latitude,
            'longitude' => (float) $report->longitude,
            'address' => $report->address,
            'description' => $report->description,
            'status' => $report->status,
            'admin_notes' => $report->admin_notes,
            'created_at' => $report->created_at?->toISOString(),
            'updated_at' => $report->updated_at?->toISOString(),
        ];

        if ($includeUser && $report->relationLoaded('user') && $report->user) {
            $result['user_name'] = $report->user->name;
            $result['user'] = [
                'id' => $report->user->id,
                'name' => $report->user->name,
                'email' => $report->user->email,
            ];
        }

        return $result;
    }

    private function deleteStoredPhotoIfCustom(?string $path): void
    {
        if (! $path || in_array($path, self::SAMPLE_FILES, true)) {
            return;
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
