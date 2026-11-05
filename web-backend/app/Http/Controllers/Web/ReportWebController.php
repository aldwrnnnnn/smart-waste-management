<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\WasteReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class ReportWebController extends Controller
{
    private const SAMPLE_FILES = [
        'reports/sample_organic.jpg',
        'reports/sample_plastic.jpg',
        'reports/sample_paper.jpg',
    ];

    public function index(Request $request): View
    {
        $user = $request->user();
        $query = WasteReport::with('user:id,name,email');

        if (! $user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        $search = trim((string) $request->query('search', ''));
        $wasteType = (string) $request->query('waste_type', '');
        $status = (string) $request->query('status', '');

        if ($search !== '') {
            $query->where(function ($sub) use ($search) {
                $sub->where('description', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($wasteType !== '') {
            $query->where('waste_type', $wasteType);
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        $reports = $query->latest()->paginate(10)->withQueryString();

        return view('reports.index', [
            'reports' => $reports,
            'filters' => [
                'search' => $search,
                'waste_type' => $wasteType,
                'status' => $status,
            ],
        ]);
    }

    public function create(): View
    {
        return view('reports.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'address' => ['nullable', 'string', 'max:1000'],
            'description' => ['required', 'string', 'min:5', 'max:5000'],
            'waste_type' => ['nullable', Rule::in(['Organic Waste', 'Plastic Waste', 'Paper Waste'])],
            'confidence_score' => ['nullable', 'numeric', 'between:0,1'],
        ], [
            'photo.required' => 'Foto bukti temuan sampah wajib diunggah.',
            'photo.image' => 'File yang diunggah harus berupa gambar.',
            'photo.max' => 'Ukuran foto maksimal 5 MB.',
            'latitude.required' => 'Koordinat garis lintang (Latitude) wajib diisi.',
            'longitude.required' => 'Koordinat garis bujur (Longitude) wajib diisi.',
            'description.required' => 'Deskripsi kondisi sampah di lokasi wajib diisi.',
            'description.min' => 'Deskripsi minimal terdiri dari 5 karakter.',
        ]);

        /** @var UploadedFile $photo */
        $photo = $request->file('photo');
        $wasteType = $validated['waste_type'] ?? null;
        $confidenceScore = isset($validated['confidence_score']) ? (float) $validated['confidence_score'] : null;

        if (! $wasteType) {
            $prediction = $this->callMlService($photo);
            if ($prediction['ok']) {
                $wasteType = $prediction['waste_type'];
                $confidenceScore = $prediction['confidence_score'];
            } else {
                return back()
                    ->withInput()
                    ->withErrors([
                        'waste_type' => $prediction['message'] . ' Silakan pilih kategori sampah secara manual atau nyalakan server FastAPI.',
                    ]);
            }
        }

        $photoPath = $photo->store('reports', 'public');

        $report = WasteReport::create([
            'user_id' => $request->user()->id,
            'photo_path' => $photoPath,
            'waste_type' => $wasteType,
            'confidence_score' => $confidenceScore,
            'latitude' => (float) $validated['latitude'],
            'longitude' => (float) $validated['longitude'],
            'address' => $validated['address'] ?? null,
            'description' => $validated['description'],
            'status' => 'Pending',
            'admin_notes' => null,
        ]);

        return redirect()->route('reports.show', $report)
            ->with('success', 'Laporan sampah berhasil dikirimkan dengan kategori ' . $report->waste_type . '.');
    }

    public function show(Request $request, WasteReport $report): View
    {
        $this->authorizeReportAccess($request, $report);
        $report->load('user:id,name,email,role');

        return view('reports.show', [
            'report' => $report,
        ]);
    }

    public function edit(Request $request, WasteReport $report): View|RedirectResponse
    {
        $this->authorizeReportAccess($request, $report);

        if (! $report->canBeEditedBy($request->user())) {
            return redirect()->route('reports.show', $report)
                ->with('error', 'Laporan sampah #' . $report->id . ' tidak dapat diedit karena sudah diproses oleh admin (status: ' . $report->status . ').');
        }

        $report->load('user:id,name,email');

        return view('reports.edit', [
            'report' => $report,
        ]);
    }

    public function update(Request $request, WasteReport $report): RedirectResponse
    {
        $this->authorizeReportAccess($request, $report);

        if (! $report->canBeEditedBy($request->user())) {
            return redirect()->route('reports.show', $report)
                ->with('error', 'Laporan sampah #' . $report->id . ' tidak dapat diperbarui karena sudah diproses oleh admin (status: ' . $report->status . ').');
        }

        $isAdmin = $request->user()->isAdmin();

        $rules = [
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'address' => ['nullable', 'string', 'max:1000'],
            'description' => ['required', 'string', 'min:5', 'max:5000'],
            'waste_type' => ['required', Rule::in(['Organic Waste', 'Plastic Waste', 'Paper Waste'])],
            'confidence_score' => ['nullable', 'numeric', 'between:0,1'],
        ];

        if ($isAdmin) {
            $rules['status'] = ['required', Rule::in(['Pending', 'In Progress', 'Completed', 'Rejected'])];
            $rules['admin_notes'] = ['nullable', 'string', 'max:2000'];
        }

        $validated = $request->validate($rules);

        if ($request->hasFile('photo')) {
            $this->deleteStoredPhotoIfCustom($report->photo_path);
            $report->photo_path = $request->file('photo')->store('reports', 'public');
        }

        $report->latitude = (float) $validated['latitude'];
        $report->longitude = (float) $validated['longitude'];
        $report->address = $validated['address'] ?? null;
        $report->description = $validated['description'];
        $report->waste_type = $validated['waste_type'];

        if (array_key_exists('confidence_score', $validated)) {
            $report->confidence_score = $validated['confidence_score'] !== null
                ? (float) $validated['confidence_score']
                : null;
        }

        if ($isAdmin) {
            $report->status = $validated['status'];
            $report->admin_notes = $validated['admin_notes'] ?? null;
        }

        $report->save();

        return redirect()->route('reports.show', $report)
            ->with('success', 'Data laporan sampah #' . $report->id . ' berhasil diperbarui.');
    }

    public function updateStatus(Request $request, WasteReport $report): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['Pending', 'In Progress', 'Completed', 'Rejected'])],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $report->update([
            'status' => $validated['status'],
            'admin_notes' => $validated['admin_notes'] ?? null,
        ]);

        return back()->with(
            'success',
            'Status laporan #' . $report->id . ' berhasil diubah menjadi "' . $report->status . '".'
        );
    }

    public function destroy(Request $request, WasteReport $report): RedirectResponse
    {
        $this->authorizeReportAccess($request, $report);

        if (! $report->canBeDeletedBy($request->user())) {
            return back()->with(
                'error',
                'Laporan sampah #' . $report->id . ' tidak dapat dihapus karena sudah diproses oleh admin (status: ' . $report->status . ').'
            );
        }

        $reportId = $report->id;
        $this->deleteStoredPhotoIfCustom($report->photo_path);
        $report->delete();

        return redirect()->route('reports.index')
            ->with('success', 'Laporan sampah #' . $reportId . ' telah dihapus.');
    }

    public function detectAi(Request $request): JsonResponse
    {
        $fileKey = $request->hasFile('photo') ? 'photo' : 'file';

        $request->validate([
            $fileKey => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        /** @var UploadedFile $photo */
        $photo = $request->file($fileKey);
        $result = $this->callMlService($photo);

        if (! $result['ok']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 503);
        }

        return response()->json([
            'success' => true,
            'message' => 'Klasifikasi AI berhasil dijalankan.',
            'data' => [
                'waste_type' => $result['waste_type'],
                'confidence_score' => $result['confidence_score'],
                'all_probabilities' => $result['all_probabilities'],
            ],
            'waste_type' => $result['waste_type'],
            'confidence_score' => $result['confidence_score'],
        ]);
    }

    private function authorizeReportAccess(Request $request, WasteReport $report): void
    {
        $user = $request->user();
        if (! $user->isAdmin() && (int) $report->user_id !== (int) $user->id) {
            abort(403, 'Anda tidak memiliki izin untuk mengakses laporan ini.');
        }
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
                    'message' => 'Layanan FastAPI mengembalikan kode status error (' . $response->status() . ').',
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
                'message' => 'Tidak dapat terhubung ke layanan AI FastAPI di ' . $mlUrl . '.',
            ];
        }
    }
}
