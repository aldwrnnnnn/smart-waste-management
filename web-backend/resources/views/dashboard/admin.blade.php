@extends('layouts.app')

@section('title', 'Dashboard Admin - Smart Waste Management')
@section('page_title', 'Dashboard Operasional Administrator')
@section('page_subtitle', 'Pantau antrean laporan warga, statistik klasifikasi AI, dan progres pengangkutan sampah.')

@section('header_actions')
    <a
        href="{{ route('reports.create') }}"
        class="inline-flex items-center gap-2 px-4 min-h-[44px] rounded-lg bg-forest-700 hover:bg-forest-800 text-white text-sm font-semibold transition"
    >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        <span>Buat Laporan Baru</span>
    </a>
@endsection

@section('content')
    <!-- Summary Metric Cards (5 Status Cards) -->
    <section aria-label="Ringkasan Jumlah Laporan" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5 sm:gap-4">
        <a href="{{ route('reports.index') }}" class="group bg-white rounded-xl border border-[#D5E0D8] p-4 hover:border-forest-700 transition flex flex-col justify-between h-full">
            <div>
                <div class="text-xs font-semibold uppercase tracking-wider text-ink-muted">Total Laporan</div>
                <div class="mt-2 text-3xl font-bold font-mono text-ink tracking-tight">{{ $stats['total'] }}</div>
            </div>
            <div class="mt-3 text-xs text-ink-muted">
                Rata-rata skor AI: <span class="font-mono font-semibold text-ink ml-1">{{ $avgConfidence }}%</span>
            </div>
        </a>

        <a href="{{ route('reports.index', ['status' => 'Pending']) }}" class="group bg-white rounded-xl border border-[#D5E0D8] p-4 hover:border-amber-600 transition flex flex-col justify-between h-full">
            <div>
                <div class="text-xs font-semibold uppercase tracking-wider text-[#78350F]">Pending</div>
                <div class="mt-2 text-3xl font-bold font-mono text-ink tracking-tight">{{ $stats['pending'] }}</div>
            </div>
            <div class="mt-3 text-xs text-ink-muted">Perlu verifikasi petugas</div>
        </a>

        <a href="{{ route('reports.index', ['status' => 'In Progress']) }}" class="group bg-white rounded-xl border border-[#D5E0D8] p-4 hover:border-blue-600 transition flex flex-col justify-between h-full">
            <div>
                <div class="text-xs font-semibold uppercase tracking-wider text-[#1E3A8A]">In Progress</div>
                <div class="mt-2 text-3xl font-bold font-mono text-ink tracking-tight">{{ $stats['in_progress'] }}</div>
            </div>
            <div class="mt-3 text-xs text-ink-muted">Sedang ditangani</div>
        </a>

        <a href="{{ route('reports.index', ['status' => 'Completed']) }}" class="group bg-white rounded-xl border border-[#D5E0D8] p-4 hover:border-emerald-600 transition flex flex-col justify-between h-full">
            <div>
                <div class="text-xs font-semibold uppercase tracking-wider text-[#14532D]">Completed</div>
                <div class="mt-2 text-3xl font-bold font-mono text-ink tracking-tight">{{ $stats['completed'] }}</div>
            </div>
            <div class="mt-3 text-xs text-ink-muted">Telah diangkut & bersih</div>
        </a>

        <a href="{{ route('reports.index', ['status' => 'Rejected']) }}" class="group bg-white rounded-xl border border-[#D5E0D8] p-4 hover:border-red-600 transition flex flex-col justify-between h-full">
            <div>
                <div class="text-xs font-semibold uppercase tracking-wider text-[#7F1D1D]">Rejected</div>
                <div class="mt-2 text-3xl font-bold font-mono text-ink tracking-tight">{{ $stats['rejected'] }}</div>
            </div>
            <div class="mt-3 text-xs text-ink-muted">Duplikat / luar cakupan</div>
        </a>
    </section>

    <!-- Charts Section -->
    <section aria-label="Visualisasi Grafik Laporan" class="mt-6 grid grid-cols-1 lg:grid-cols-5 gap-4">
        <!-- Bar Chart: Waste Category Comparison -->
        <div class="lg:col-span-3 bg-white rounded-xl border border-[#D5E0D8] p-5 sm:p-6">
            <div class="pb-4 border-b border-[#D5E0D8]">
                <h2 class="text-base font-bold text-ink">Komposisi Laporan Berdasarkan Kategori Sampah</h2>
                <p class="text-xs text-ink-muted mt-0.5">Perbandingan hasil klasifikasi AI untuk Organic, Plastic, dan Paper Waste</p>
                <div class="mt-3 flex flex-wrap items-center gap-2 text-xs font-mono">
                    <span class="px-2.5 py-1 rounded bg-forest-50 border border-[#D5E0D8] text-ink">Organic: {{ $stats['organic'] }}</span>
                    <span class="px-2.5 py-1 rounded bg-forest-50 border border-[#D5E0D8] text-ink">Plastic: {{ $stats['plastic'] }}</span>
                    <span class="px-2.5 py-1 rounded bg-forest-50 border border-[#D5E0D8] text-ink">Paper: {{ $stats['paper'] }}</span>
                </div>
            </div>
            <div class="mt-4 relative h-72">
                <canvas id="wasteCategoryChart" aria-label="Grafik batang jumlah laporan per kategori sampah" role="img"></canvas>
            </div>
        </div>

        <!-- Doughnut Chart: Status Distribution -->
        <div class="lg:col-span-2 bg-white rounded-xl border border-[#D5E0D8] p-5 sm:p-6">
            <div class="pb-4 border-b border-[#D5E0D8]">
                <h2 class="text-base font-bold text-ink">Distribusi Status Penanganan Laporan</h2>
                <p class="text-xs text-ink-muted mt-0.5">Proporsi penyelesaian laporan dari total {{ $stats['total'] }} laporan masuk</p>
            </div>
            <div class="mt-4 relative h-72 flex items-center justify-center">
                <canvas id="statusDistributionChart" aria-label="Grafik donat distribusi status laporan" role="img"></canvas>
            </div>
        </div>
    </section>

    <!-- Fast Triage Queue (Pending & In Progress) -->
    <section aria-label="Antrean Tindak Lanjut Cepat" class="mt-6 bg-white rounded-xl border border-[#D5E0D8] overflow-hidden">
        <div class="p-5 sm:px-6 border-b border-[#D5E0D8] flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-base font-bold text-ink">Antrean Tindak Lanjut Cepat (Pending & In Progress)</h2>
                <p class="text-xs text-ink-muted mt-0.5">Ubah status penanganan dan tambahkan catatan petugas secara langsung.</p>
            </div>
            <a href="{{ route('reports.index') }}" class="text-sm font-semibold text-forest-700 hover:underline min-h-[44px] inline-flex items-center">
                Lihat Semua Laporan ({{ $stats['total'] }})
            </a>
        </div>

        @if($pendingQueue->isEmpty())
            <div class="p-8 text-center">
                <div class="text-sm font-semibold text-ink">Tidak ada antrean laporan yang menunggu penanganan</div>
                <p class="text-xs text-ink-muted mt-1">Seluruh laporan telah berstatus Completed atau Rejected.</p>
            </div>
        @else
            <div class="divide-y divide-[#D5E0D8]">
                @foreach($pendingQueue as $item)
                    <div class="p-4 sm:px-6 flex flex-col gap-3">
                        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                            <div class="flex items-start gap-3.5">
                                <img
                                    src="{{ $item->photo_url }}"
                                    alt="Foto laporan #{{ $item->id }}"
                                    class="w-16 h-16 rounded-lg object-cover border border-[#D5E0D8] shrink-0 bg-forest-50"
                                >
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <a href="{{ route('reports.show', $item) }}" class="font-bold text-sm text-ink hover:text-forest-700">
                                            Laporan #{{ $item->id }} - {{ $item->waste_type }}
                                        </a>
                                        @if($item->confidence_score)
                                            <span class="font-mono text-xs px-2 py-0.5 rounded bg-forest-50 border border-[#D5E0D8] text-ink">
                                                AI {{ number_format($item->confidence_score * 100, 1) }}%
                                            </span>
                                        @endif
                                    </div>
                                    <p class="text-xs text-ink mt-1 line-clamp-2">{{ $item->description }}</p>
                                    <div class="text-xs text-ink-muted mt-1">
                                        Pelapor: <span class="font-medium text-ink">{{ $item->user?->name ?? 'User' }}</span>
                                        &bull; Lokasi: {{ $item->address ?: ($item->latitude . ', ' . $item->longitude) }}
                                    </div>
                                </div>
                            </div>

                            <a
                                href="{{ route('reports.show', $item) }}"
                                class="shrink-0 inline-flex items-center justify-center px-3 min-h-[44px] rounded-lg border border-[#B8C9BD] text-xs font-semibold text-ink hover:bg-forest-50"
                            >
                                Detail
                            </a>
                        </div>

                        <!-- Inline Status Update Form -->
                        <form method="POST" action="{{ route('reports.update-status', $item) }}" class="bg-forest-50 rounded-lg p-3 border border-[#D5E0D8] grid grid-cols-1 sm:grid-cols-12 gap-2.5 items-center">
                            @csrf
                            @method('PATCH')
                            <div class="sm:col-span-3">
                                <label for="status-{{ $item->id }}" class="sr-only">Status Laporan #{{ $item->id }}</label>
                                <select
                                    id="status-{{ $item->id }}"
                                    name="status"
                                    class="w-full px-3 min-h-[44px] rounded-lg border border-[#B8C9BD] bg-white text-xs font-semibold text-ink"
                                >
                                    @foreach(['Pending', 'In Progress', 'Completed', 'Rejected'] as $st)
                                        <option value="{{ $st }}" @selected($item->status === $st)>{{ $st }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="sm:col-span-6">
                                <label for="notes-{{ $item->id }}" class="sr-only">Catatan Admin Laporan #{{ $item->id }}</label>
                                <input
                                    id="notes-{{ $item->id }}"
                                    type="text"
                                    name="admin_notes"
                                    value="{{ $item->admin_notes }}"
                                    placeholder="Tambahkan catatan tindak lanjut petugas..."
                                    class="w-full px-3 min-h-[44px] rounded-lg border border-[#B8C9BD] bg-white text-xs text-ink"
                                >
                            </div>
                            <div class="sm:col-span-3">
                                <button
                                    type="submit"
                                    class="w-full min-h-[44px] px-3 rounded-lg bg-forest-700 hover:bg-forest-800 text-white text-xs font-semibold transition"
                                >
                                    Simpan Status
                                </button>
                            </div>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const categoryCtx = document.getElementById('wasteCategoryChart');
        if (categoryCtx) {
            new Chart(categoryCtx, {
                type: 'bar',
                data: {
                    labels: ['Organic Waste', 'Plastic Waste', 'Paper Waste'],
                    datasets: [{
                        label: 'Jumlah Laporan',
                        data: [{{ $stats['organic'] }}, {{ $stats['plastic'] }}, {{ $stats['paper'] }}],
                        backgroundColor: ['#15803D', '#0369A1', '#B45309'],
                        borderColor: ['#14532D', '#075985', '#78350F'],
                        borderWidth: 1.5,
                        borderRadius: 6,
                        maxBarThickness: 64
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0, color: '#43544A' },
                            grid: { color: '#E3ECE5' }
                        },
                        x: {
                            ticks: { color: '#111C16', font: { weight: '600' } },
                            grid: { display: false }
                        }
                    }
                }
            });
        }

        const statusCtx = document.getElementById('statusDistributionChart');
        if (statusCtx) {
            new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Pending', 'In Progress', 'Completed', 'Rejected'],
                    datasets: [{
                        data: [
                            {{ $stats['pending'] }},
                            {{ $stats['in_progress'] }},
                            {{ $stats['completed'] }},
                            {{ $stats['rejected'] }}
                        ],
                        backgroundColor: ['#D97706', '#2563EB', '#15803D', '#DC2626'],
                        borderColor: '#FFFFFF',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '62%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: '#111C16',
                                padding: 14,
                                usePointStyle: true
                            }
                        }
                    }
                }
            });
        }
    });
</script>
@endpush
