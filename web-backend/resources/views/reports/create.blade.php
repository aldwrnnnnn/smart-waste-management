@extends('layouts.app')

@section('title', 'Buat Laporan Sampah Baru - Smart Waste Management')
@section('page_title', 'Buat Laporan Temuan Sampah')
@section('page_subtitle', 'Unggah foto sampah, deteksi otomatis dengan AI, dan lengkapi titik koordinat lokasi.')

@section('header_actions')
    <a
        href="{{ route('reports.index') }}"
        class="inline-flex items-center gap-2 px-4 min-h-[44px] rounded-lg border border-[#B8C9BD] bg-white hover:bg-forest-50 text-ink text-sm font-semibold transition"
    >
        Kembali ke Daftar
    </a>
@endsection

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
    <!-- Left Column: Form -->
    <div class="lg:col-span-8 bg-white rounded-xl border border-[#D5E0D8] p-5 sm:p-6">
        <form method="POST" action="{{ route('reports.store') }}" enctype="multipart/form-data">
            @csrf

            <!-- Photo Upload & AI Detection Box -->
            <div class="p-4 sm:p-5 rounded-xl bg-forest-50 border border-[#D5E0D8] space-y-4">
                <div>
                    <label for="photo" class="block text-sm font-bold text-ink mb-1">
                        1. Unggah Foto Sampah <span class="text-[#7F1D1D]">*</span>
                    </label>
                    <p class="text-xs text-ink-muted mb-3">
                        Setelah memilih foto, klik tombol Deteksi AI untuk mengisi kategori secara otomatis.
                    </p>
                    <input
                        id="photo"
                        name="photo"
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        required
                        class="block w-full text-sm text-ink file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-forest-700 file:text-white hover:file:bg-forest-800 cursor-pointer"
                    >
                </div>

                <!-- Image Preview & AI Trigger Button -->
                <div id="previewContainer" class="hidden pt-2 border-t border-[#D5E0D8]">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                        <img
                            id="photoPreview"
                            src=""
                            alt="Pratinjau foto sampah"
                            class="w-28 h-28 rounded-lg object-cover border border-[#B8C9BD] bg-white shrink-0"
                        >
                        <div class="flex-1 space-y-2">
                            <button
                                type="button"
                                id="btnDetectAi"
                                onclick="runAiDetection()"
                                class="inline-flex items-center gap-2 px-4 min-h-[44px] rounded-lg bg-forest-900 hover:bg-forest-800 text-white text-sm font-semibold transition"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                <span id="btnDetectText">Deteksi Kategori Otomatis via AI</span>
                            </button>

                            <div id="aiStatusMessage" role="status" aria-live="polite" class="text-xs text-ink-muted">
                                Klik tombol di atas untuk mengirim gambar ke endpoint <code class="font-mono">POST /predict</code> pada FastAPI.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- AI Classification Output -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                    <div>
                        <label for="waste_type" class="block text-sm font-semibold text-ink mb-1.5">
                            Kategori Jenis Sampah
                        </label>
                        <select
                            id="waste_type"
                            name="waste_type"
                            class="w-full px-3.5 min-h-[44px] rounded-lg border border-[#B8C9BD] bg-white text-sm font-semibold text-ink"
                        >
                            <option value="">Deteksi Otomatis AI</option>
                            @foreach(['Organic Waste', 'Plastic Waste', 'Paper Waste'] as $type)
                                <option value="{{ $type }}" @selected(old('waste_type') === $type)>{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="confidence_score_display" class="block text-sm font-semibold text-ink mb-1.5">
                            Tingkat Keyakinan AI 
                        </label>
                        <input
                            id="confidence_score_display"
                            type="text"
                            readonly
                            value="{{ old('confidence_score') ? number_format((float) old('confidence_score') * 100, 2) . '%' : 'Menunggu deteksi AI' }}"
                            class="w-full px-3.5 min-h-[44px] rounded-lg border border-[#D5E0D8] bg-white/80 font-mono text-sm text-ink-muted"
                        >
                        <input type="hidden" id="confidence_score" name="confidence_score" value="{{ old('confidence_score') }}">
                    </div>
                </div>
            </div>

            <!-- GPS Coordinates & Address -->
            <div class="mt-6 space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <span class="text-sm font-bold text-ink">2. Titik Koordinat GPS & Lokasi <span class="text-[#7F1D1D]">*</span></span>
                    <button
                        type="button"
                        id="btnGetGps"
                        onclick="getCurrentGpsLocation()"
                        class="inline-flex items-center gap-1.5 px-3.5 min-h-[44px] rounded-lg border border-forest-700 text-forest-700 hover:bg-forest-50 text-xs font-semibold transition"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span>Ambil Lokasi GPS Saat Ini</span>
                    </button>
                </div>

                <div id="gpsStatusMsg" role="status" aria-live="polite" class="text-xs text-ink-muted hidden"></div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="latitude" class="block text-sm font-semibold text-ink mb-1.5">Latitude (Garis Lintang)</label>
                        <input
                            id="latitude"
                            name="latitude"
                            type="number"
                            step="any"
                            required
                            value="{{ old('latitude', '-6.20000000') }}"
                            placeholder="-6.20000000"
                            class="w-full px-3.5 min-h-[44px] rounded-lg border border-[#B8C9BD] bg-white font-mono text-sm text-ink"
                        >
                    </div>
                    <div>
                        <label for="longitude" class="block text-sm font-semibold text-ink mb-1.5">Longitude (Garis Bujur)</label>
                        <input
                            id="longitude"
                            name="longitude"
                            type="number"
                            step="any"
                            required
                            value="{{ old('longitude', '106.81666600') }}"
                            placeholder="106.81666600"
                            class="w-full px-3.5 min-h-[44px] rounded-lg border border-[#B8C9BD] bg-white font-mono text-sm text-ink"
                        >
                    </div>
                </div>

                <div>
                    <label for="address" class="block text-sm font-semibold text-ink mb-1.5">Alamat Lengkap / Patokan Lokasi (Opsional)</label>
                    <input
                        id="address"
                        name="address"
                        type="text"
                        value="{{ old('address') }}"
                        placeholder="Contoh: Jl. Jenderal Sudirman No. 10, dekat halte bus"
                        class="w-full px-3.5 min-h-[44px] rounded-lg border border-[#B8C9BD] bg-white text-sm text-ink"
                    >
                </div>
            </div>

            <!-- Description -->
            <div class="mt-6">
                <label for="description" class="block text-sm font-bold text-ink mb-1.5">
                    3. Deskripsi Kondisi Sampah <span class="text-[#7F1D1D]">*</span>
                </label>
                <textarea
                    id="description"
                    name="description"
                    rows="4"
                    required
                    placeholder="Jelaskan kondisi tumpukan sampah, perkiraan volume, atau dampak terhadap lingkungan sekitar..."
                    class="w-full p-3.5 rounded-lg border border-[#B8C9BD] bg-white text-sm text-ink"
                >{{ old('description') }}</textarea>
            </div>

            <div class="mt-6 pt-4 border-t border-[#D5E0D8] flex flex-wrap items-center justify-end gap-3">
                <a
                    href="{{ route('reports.index') }}"
                    class="px-4 min-h-[44px] inline-flex items-center justify-center rounded-lg border border-[#B8C9BD] text-sm font-semibold text-ink hover:bg-forest-50"
                >
                    Batal
                </a>
                <button
                    type="submit"
                    class="px-6 min-h-[44px] rounded-lg bg-forest-700 hover:bg-forest-800 text-white text-sm font-semibold transition"
                >
                    Kirim & Simpan Laporan
                </button>
            </div>
        </form>
    </div>

    <!-- Right Column: AI Probabilities Breakdown & Guide -->
    <div class="lg:col-span-4 space-y-6">
        <div class="bg-white rounded-xl border border-[#D5E0D8] p-5">
            <h2 class="text-base font-bold text-ink">Rincian Probabilitas AI</h2>
            
            <div id="probBarsContainer" class="mt-4 space-y-3">
                @foreach(['Organic Waste', 'Plastic Waste', 'Paper Waste'] as $cls)
                    <div>
                        <div class="flex justify-between text-xs font-medium text-ink mb-1">
                            <span>{{ $cls }}</span>
                            <span class="font-mono" data-prob-label="{{ $cls }}">0.0%</span>
                        </div>
                        <div class="w-full h-2.5 bg-forest-50 rounded-full overflow-hidden border border-[#D5E0D8]">
                            <div class="h-full bg-forest-700 transition-all duration-300" style="width: 0%" data-prob-bar="{{ $cls }}"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="bg-white rounded-xl border border-[#D5E0D8] p-5">
            <div class="flex items-center justify-between pb-3 border-b border-[#D5E0D8]">
                <h2 class="text-base font-bold text-ink">Alur Pemrosesan</h2>
            </div>

            <ol class="mt-4 space-y-3.5">
                <!-- Tahap 1 -->
                <li class="flex items-start gap-3">
                    <span class="w-6 h-6 rounded-full bg-forest-100 border border-forest-200 text-forest-800 font-bold text-xs flex items-center justify-center shrink-0 mt-0.5" aria-hidden="true">
                        1
                    </span>
                    <div class="flex-1 min-w-0 text-xs">
                        <div class="font-bold text-ink">Unggah Bukti Foto</div>
                        <p class="text-ink-muted mt-0.5 leading-relaxed">
                            Pilih foto temuan sampah dari kamera atau galeri perangkat Anda (maksimal 5 MB).
                        </p>
                    </div>
                </li>

                <!-- Tahap 2 -->
                <li class="flex items-start gap-3">
                    <span class="w-6 h-6 rounded-full bg-forest-100 border border-forest-200 text-forest-800 font-bold text-xs flex items-center justify-center shrink-0 mt-0.5" aria-hidden="true">
                        2
                    </span>
                    <div class="flex-1 min-w-0 text-xs">
                        <div class="font-bold text-ink">Klasifikasi Otomatis AI</div>
                        <p class="text-ink-muted mt-0.5 leading-relaxed">
                            Gunakan tombol <strong class="text-ink font-semibold">Deteksi AI</strong> untuk prediksi instan, atau biarkan sistem mengklasifikasikan secara otomatis saat disimpan.
                        </p>
                    </div>
                </li>

                <!-- Tahap 3 -->
                <li class="flex items-start gap-3">
                    <span class="w-6 h-6 rounded-full bg-forest-100 border border-forest-200 text-forest-800 font-bold text-xs flex items-center justify-center shrink-0 mt-0.5" aria-hidden="true">
                        3
                    </span>
                    <div class="flex-1 min-w-0 text-xs">
                        <div class="font-bold text-ink">Verifikasi & Penanganan</div>
                        <p class="text-ink-muted mt-0.5 leading-relaxed">
                            Laporan baru akan berstatus <strong class="text-ink font-semibold">Pending</strong> hingga diverifikasi oleh Administrator untuk penugasan petugas.
                        </p>
                    </div>
                </li>
            </ol>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const photoInput = document.getElementById('photo');
    const previewContainer = document.getElementById('previewContainer');
    const photoPreview = document.getElementById('photoPreview');
    const aiStatusMessage = document.getElementById('aiStatusMessage');
    const wasteTypeSelect = document.getElementById('waste_type');
    const confidenceDisplay = document.getElementById('confidence_score_display');
    const confidenceHidden = document.getElementById('confidence_score');
    const btnDetectAi = document.getElementById('btnDetectAi');
    const btnDetectText = document.getElementById('btnDetectText');

    let lastAiDetectedType = @js(old('confidence_score') ? old('waste_type') : null);
    let lastAiConfidence = @js(old('confidence_score') ? (float) old('confidence_score') : null);

    photoInput?.addEventListener('change', function () {
        const file = this.files[0];
        lastAiDetectedType = null;
        lastAiConfidence = null;
        confidenceHidden.value = '';
        confidenceDisplay.value = 'Menunggu deteksi AI';
        document.querySelectorAll('[data-prob-label]').forEach(el => { el.textContent = '0.0%'; });
        document.querySelectorAll('[data-prob-bar]').forEach(el => { el.style.width = '0%'; });
        if (!file) {
            previewContainer.classList.add('hidden');
            return;
        }
        const reader = new FileReader();
        reader.onload = function (e) {
            photoPreview.src = e.target.result;
            previewContainer.classList.remove('hidden');
        };
        reader.readAsDataURL(file);
    });

    wasteTypeSelect?.addEventListener('change', function () {
        if (!lastAiDetectedType || this.value !== lastAiDetectedType) {
            confidenceHidden.value = '';
            confidenceDisplay.value = this.value
                ? 'Kategori dipilih manual'
                : 'Menunggu deteksi AI';
        } else if (lastAiConfidence !== null) {
            confidenceHidden.value = lastAiConfidence;
            confidenceDisplay.value = `${(lastAiConfidence * 100).toFixed(2)}% (${lastAiDetectedType})`;
        }
    });

    async function runAiDetection() {
        const file = photoInput?.files[0];
        if (!file) {
            aiStatusMessage.textContent = 'Pilih file foto terlebih dahulu sebelum menjalankan deteksi AI.';
            aiStatusMessage.className = 'text-xs font-medium text-[#7F1D1D]';
            return;
        }

        btnDetectAi.disabled = true;
        btnDetectText.textContent = 'Menganalisis citra di FastAPI...';
        aiStatusMessage.textContent = 'Sedang mengirim gambar ke layanan inferensi MobileNetV2';
        aiStatusMessage.className = 'text-xs text-ink-muted';

        const laravelProxyUrl = '{{ route("reports.detect-ai", [], false) }}';

        try {
            const proxyForm = new FormData();
            proxyForm.append('photo', file);

            const response = await fetch(laravelProxyUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: proxyForm
            });

            const result = await response.json();
            if (!response.ok || (!result.success && !result.waste_type)) {
                throw new Error(result.message || 'Gagal melakukan deteksi AI.');
            }

            const payload = result.data || result;
            const waste_type = payload.waste_type || result.waste_type;
            const confidence_score = Number(payload.confidence_score ?? result.confidence_score ?? 0);
            const all_probabilities = payload.all_probabilities || result.all_probabilities || null;

            lastAiDetectedType = waste_type;
            lastAiConfidence = confidence_score;
            wasteTypeSelect.value = waste_type;
            confidenceHidden.value = confidence_score;
            confidenceDisplay.value = `${(confidence_score * 100).toFixed(2)}% (${waste_type})`;

            aiStatusMessage.textContent = `Berhasil terdeteksi sebagai ${waste_type} dengan tingkat keyakinan ${(confidence_score * 100).toFixed(2)}%.`;
            aiStatusMessage.className = 'text-xs font-semibold text-[#14532D]';

            if (all_probabilities) {
                Object.entries(all_probabilities).forEach(([label, prob]) => {
                    const pct = (Number(prob) * 100).toFixed(1);
                    const lblEl = document.querySelector(`[data-prob-label="${label}"]`);
                    const barEl = document.querySelector(`[data-prob-bar="${label}"]`);
                    if (lblEl) lblEl.textContent = `${pct}%`;
                    if (barEl) barEl.style.width = `${pct}%`;
                });
            }
        } catch (err) {
            aiStatusMessage.textContent = err.message + ' Anda tetap dapat memilih kategori sampah secara manual.';
            aiStatusMessage.className = 'text-xs font-semibold text-[#7F1D1D]';
        } finally {
            btnDetectAi.disabled = false;
            btnDetectText.textContent = 'Deteksi Kategori Otomatis via AI (FastAPI)';
        }
    }

    function getCurrentGpsLocation() {
        const msgEl = document.getElementById('gpsStatusMsg');
        msgEl.classList.remove('hidden');

        if (!navigator.geolocation) {
            msgEl.textContent = 'Browser Anda tidak mendukung fitur Geolocation.';
            msgEl.className = 'text-xs font-medium text-[#7F1D1D]';
            return;
        }

        msgEl.textContent = 'Mengambil koordinat GPS dari perangkat Anda...';
        msgEl.className = 'text-xs text-ink-muted';

        navigator.geolocation.getCurrentPosition(
            function (pos) {
                document.getElementById('latitude').value = pos.coords.latitude.toFixed(8);
                document.getElementById('longitude').value = pos.coords.longitude.toFixed(8);
                msgEl.textContent = 'Koordinat GPS berhasil diperbarui dari lokasi perangkat Anda.';
                msgEl.className = 'text-xs font-semibold text-[#14532D]';
            },
            function () {
                msgEl.textContent = 'Izin lokasi ditolak atau tidak tersedia. Silakan isi koordinat secara manual.';
                msgEl.className = 'text-xs font-medium text-[#78350F]';
            },
            { enableHighAccuracy: true, timeout: 8000 }
        );
    }
</script>
@endpush
