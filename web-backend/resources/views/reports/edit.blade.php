@extends('layouts.app')

@section('title', 'Edit Laporan #' . $report->id . ' - Smart Waste Management')
@section('page_title', 'Edit Laporan Sampah #' . $report->id)
@section('page_subtitle', 'Perbarui foto, kategori sampah, titik koordinat GPS, atau deskripsi laporan.')

@section('header_actions')
    <a
        href="{{ route('reports.show', $report) }}"
        class="inline-flex items-center px-4 min-h-[44px] rounded-lg border border-[#B8C9BD] bg-white hover:bg-forest-50 text-ink text-sm font-semibold transition"
    >
        Batal & Kembali
    </a>
@endsection

@section('content')
<div class="max-w-3xl bg-white rounded-xl border border-[#D5E0D8] p-5 sm:p-6">
    <form method="POST" action="{{ route('reports.update', $report) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <!-- Current Photo & Optional Replacement -->
        <div class="p-4 sm:p-5 rounded-xl bg-forest-50 border border-[#D5E0D8] space-y-4">
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                <img
                    id="photoPreview"
                    src="{{ $report->photo_url }}"
                    alt="Foto laporan #{{ $report->id }}"
                    class="w-24 h-24 rounded-lg object-cover border border-[#B8C9BD] bg-white shrink-0"
                >
                <div class="flex-1">
                    <label for="photo" class="block text-sm font-bold text-ink mb-1">
                        Ganti Foto Sampah (Opsional)
                    </label>
                    <p class="text-xs text-ink-muted mb-2">
                        Biarkan kosong jika tidak ingin mengganti foto. Jika memilih foto baru, Anda dapat menjalankan ulang deteksi AI.
                    </p>
                    <input
                        id="photo"
                        name="photo"
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        class="block w-full text-sm text-ink file:mr-4 file:py-2 file:px-3.5 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-forest-700 file:text-white hover:file:bg-forest-800 cursor-pointer"
                    >
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3 pt-2 border-t border-[#D5E0D8]">
                <button
                    type="button"
                    id="btnDetectAi"
                    onclick="runAiDetection()"
                    class="inline-flex items-center gap-2 px-3.5 min-h-[44px] rounded-lg bg-forest-900 hover:bg-forest-800 text-white text-xs font-semibold transition"
                >
                    <span id="btnDetectText">Deteksi Ulang Foto Baru via AI</span>
                </button>
                <span id="aiStatusMessage" role="status" aria-live="polite" class="text-xs text-ink-muted">
                    Pilih file gambar baru jika ingin mendeteksi ulang dengan FastAPI.
                </span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                <div>
                    <label for="waste_type" class="block text-sm font-semibold text-ink mb-1.5">Kategori Jenis Sampah</label>
                    <select
                        id="waste_type"
                        name="waste_type"
                        required
                        class="w-full px-3.5 min-h-[44px] rounded-lg border border-[#B8C9BD] bg-white text-sm font-semibold text-ink"
                    >
                        @foreach(['Organic Waste', 'Plastic Waste', 'Paper Waste'] as $type)
                            <option value="{{ $type }}" @selected(old('waste_type', $report->waste_type) === $type)>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="confidence_score" class="block text-sm font-semibold text-ink mb-1.5">Confidence Score (0.0 - 1.0)</label>
                    <input
                        id="confidence_score"
                        name="confidence_score"
                        type="number"
                        step="any"
                        min="0"
                        max="1"
                        readonly
                        value="{{ old('confidence_score', $report->confidence_score) }}"
                        class="w-full px-3.5 min-h-[44px] rounded-lg border border-[#D5E0D8] bg-white/80 font-mono text-sm text-ink-muted"
                    >
                </div>
            </div>
        </div>

        <!-- Coordinates & Address -->
        <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="latitude" class="block text-sm font-semibold text-ink mb-1.5">Latitude</label>
                <input
                    id="latitude"
                    name="latitude"
                    type="number"
                    step="any"
                    required
                    value="{{ old('latitude', $report->latitude) }}"
                    class="w-full px-3.5 min-h-[44px] rounded-lg border border-[#B8C9BD] bg-white font-mono text-sm text-ink"
                >
            </div>
            <div>
                <label for="longitude" class="block text-sm font-semibold text-ink mb-1.5">Longitude</label>
                <input
                    id="longitude"
                    name="longitude"
                    type="number"
                    step="any"
                    required
                    value="{{ old('longitude', $report->longitude) }}"
                    class="w-full px-3.5 min-h-[44px] rounded-lg border border-[#B8C9BD] bg-white font-mono text-sm text-ink"
                >
            </div>
        </div>

        <div class="mt-4">
            <label for="address" class="block text-sm font-semibold text-ink mb-1.5">Alamat Lengkap Lokasi</label>
            <input
                id="address"
                name="address"
                type="text"
                value="{{ old('address', $report->address) }}"
                class="w-full px-3.5 min-h-[44px] rounded-lg border border-[#B8C9BD] bg-white text-sm text-ink"
            >
        </div>

        <div class="mt-4">
            <label for="description" class="block text-sm font-semibold text-ink mb-1.5">Deskripsi Kondisi Sampah</label>
            <textarea
                id="description"
                name="description"
                rows="4"
                required
                class="w-full p-3.5 rounded-lg border border-[#B8C9BD] bg-white text-sm text-ink"
            >{{ old('description', $report->description) }}</textarea>
        </div>

        @if(auth()->user()->isAdmin())
            <div class="mt-6 p-4 sm:p-5 rounded-xl bg-forest-50 border border-[#D5E0D8] space-y-4">
                <div class="text-sm font-bold text-ink">Pengaturan Status & Catatan Administrator</div>
                <div>
                    <label for="status" class="block text-xs font-semibold text-ink mb-1">Status Laporan</label>
                    <select
                        id="status"
                        name="status"
                        class="w-full px-3.5 min-h-[44px] rounded-lg border border-[#B8C9BD] bg-white text-sm font-semibold text-ink"
                    >
                        @foreach(['Pending', 'In Progress', 'Completed', 'Rejected'] as $st)
                            <option value="{{ $st }}" @selected(old('status', $report->status) === $st)>{{ $st }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="admin_notes" class="block text-xs font-semibold text-ink mb-1">Catatan Admin</label>
                    <textarea
                        id="admin_notes"
                        name="admin_notes"
                        rows="3"
                        class="w-full p-3 rounded-lg border border-[#B8C9BD] bg-white text-sm text-ink"
                    >{{ old('admin_notes', $report->admin_notes) }}</textarea>
                </div>
            </div>
        @endif

        <div class="mt-6 pt-4 border-t border-[#D5E0D8] flex items-center justify-end gap-3">
            <a
                href="{{ route('reports.show', $report) }}"
                class="px-4 min-h-[44px] inline-flex items-center justify-center rounded-lg border border-[#B8C9BD] text-sm font-semibold text-ink hover:bg-forest-50"
            >
                Batal
            </a>
            <button
                type="submit"
                class="px-6 min-h-[44px] rounded-lg bg-forest-700 hover:bg-forest-800 text-white text-sm font-semibold transition"
            >
                Simpan Perubahan
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    const photoInput = document.getElementById('photo');
    const photoPreview = document.getElementById('photoPreview');
    const aiStatusMessage = document.getElementById('aiStatusMessage');
    const wasteTypeSelect = document.getElementById('waste_type');
    const confidenceInput = document.getElementById('confidence_score');
    const btnDetectAi = document.getElementById('btnDetectAi');
    const btnDetectText = document.getElementById('btnDetectText');

    let lastAiDetectedType = @js(old('confidence_score', $report->confidence_score) !== null ? old('waste_type', $report->waste_type) : null);
    let lastAiConfidence = @js(old('confidence_score', $report->confidence_score));

    photoInput?.addEventListener('change', function () {
        const file = this.files[0];
        lastAiDetectedType = null;
        lastAiConfidence = null;
        confidenceInput.value = '';
        if (!file) return;
        const reader = new FileReader();
        reader.onload = function (e) {
            photoPreview.src = e.target.result;
        };
        reader.readAsDataURL(file);
    });

    wasteTypeSelect?.addEventListener('change', function () {
        if (!lastAiDetectedType || this.value !== lastAiDetectedType) {
            confidenceInput.value = '';
        } else if (lastAiConfidence !== null && lastAiConfidence !== '') {
            confidenceInput.value = lastAiConfidence;
        }
    });

    async function runAiDetection() {
        const file = photoInput?.files[0];
        if (!file) {
            aiStatusMessage.textContent = 'Pilih file foto baru terlebih dahulu pada input di atas.';
            aiStatusMessage.className = 'text-xs font-semibold text-[#7F1D1D]';
            return;
        }

        btnDetectAi.disabled = true;
        btnDetectText.textContent = 'Menganalisis...';
        aiStatusMessage.textContent = 'Mengirim gambar ke FastAPI...';
        aiStatusMessage.className = 'text-xs text-ink-muted';

        const laravelProxyUrl = '{{ route("reports.detect-ai") }}';

        try {
            const formData = new FormData();
            formData.append('photo', file);

            const response = await fetch(laravelProxyUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: formData
            });

            const result = await response.json();
            if (!response.ok || (!result.success && !result.waste_type)) {
                throw new Error(result.message || 'Gagal menjalankan deteksi AI.');
            }

            const payload = result.data || result;
            const detectedType = payload.waste_type || result.waste_type;
            const detectedScore = Number(payload.confidence_score ?? result.confidence_score ?? 0);

            lastAiDetectedType = detectedType;
            lastAiConfidence = detectedScore;
            wasteTypeSelect.value = detectedType;
            confidenceInput.value = detectedScore;
            aiStatusMessage.textContent = `Terdeteksi: ${detectedType} (${(detectedScore * 100).toFixed(2)}%)`;
            aiStatusMessage.className = 'text-xs font-semibold text-[#14532D]';
        } catch (err) {
            aiStatusMessage.textContent = err.message;
            aiStatusMessage.className = 'text-xs font-semibold text-[#7F1D1D]';
        } finally {
            btnDetectAi.disabled = false;
            btnDetectText.textContent = 'Deteksi Ulang Foto Baru via AI';
        }
    }
</script>
@endpush
