@extends('layouts.app')

@section('title', 'Dashboard Pelapor - Smart Waste Management')
@section('page_title', 'Dashboard Laporan Saya')
@section('page_subtitle', 'Pantau status tindak lanjut laporan sampah yang telah Anda kirimkan.')

@section('header_actions')
    <a
        href="{{ route('reports.create') }}"
        class="inline-flex items-center gap-2 px-4 min-h-[44px] rounded-lg bg-forest-700 hover:bg-forest-800 text-white text-sm font-semibold transition"
    >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        <span>Laporkan Temuan Sampah</span>
    </a>
@endsection

@section('content')
    <!-- Summary Metric Cards -->
    <section aria-label="Ringkasan Status Laporan Saya" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5 sm:gap-4">
        <a href="{{ route('reports.index') }}" class="group bg-white rounded-xl border border-[#D5E0D8] p-4 hover:border-forest-700 transition flex flex-col justify-between h-full">
            <div>
                <div class="text-xs font-semibold uppercase tracking-wider text-ink-muted">Total Laporan</div>
                <div class="mt-2 text-3xl font-bold font-mono text-ink tracking-tight">{{ $stats['total'] }}</div>
            </div>
            <div class="mt-3 text-xs text-forest-700 font-medium group-hover:underline flex items-center justify-between">
                <span>Lihat seluruh riwayat</span>
                <span class="text-forest-600 transition-transform group-hover:translate-x-0.5" aria-hidden="true">&rarr;</span>
            </div>
        </a>

        <a href="{{ route('reports.index', ['status' => 'Pending']) }}" class="group bg-white rounded-xl border border-[#D5E0D8] p-4 hover:border-amber-600 transition flex flex-col justify-between h-full">
            <div>
                <div class="text-xs font-semibold uppercase tracking-wider text-[#78350F]">Pending</div>
                <div class="mt-2 text-3xl font-bold font-mono text-ink tracking-tight">{{ $stats['pending'] }}</div>
            </div>
            <div class="mt-3 text-xs text-ink-muted">Menunggu verifikasi</div>
        </a>

        <a href="{{ route('reports.index', ['status' => 'In Progress']) }}" class="group bg-white rounded-xl border border-[#D5E0D8] p-4 hover:border-blue-600 transition flex flex-col justify-between h-full">
            <div>
                <div class="text-xs font-semibold uppercase tracking-wider text-[#1E3A8A]">In Progress</div>
                <div class="mt-2 text-3xl font-bold font-mono text-ink tracking-tight">{{ $stats['in_progress'] }}</div>
            </div>
            <div class="mt-3 text-xs text-ink-muted">Petugas menuju lokasi</div>
        </a>

        <a href="{{ route('reports.index', ['status' => 'Completed']) }}" class="group bg-white rounded-xl border border-[#D5E0D8] p-4 hover:border-emerald-600 transition flex flex-col justify-between h-full">
            <div>
                <div class="text-xs font-semibold uppercase tracking-wider text-[#14532D]">Completed</div>
                <div class="mt-2 text-3xl font-bold font-mono text-ink tracking-tight">{{ $stats['completed'] }}</div>
            </div>
            <div class="mt-3 text-xs text-ink-muted">Sampah selesai diangkut</div>
        </a>

        <a href="{{ route('reports.index', ['status' => 'Rejected']) }}" class="group bg-white rounded-xl border border-[#D5E0D8] p-4 hover:border-red-600 transition flex flex-col justify-between h-full">
            <div>
                <div class="text-xs font-semibold uppercase tracking-wider text-[#7F1D1D]">Rejected</div>
                <div class="mt-2 text-3xl font-bold font-mono text-ink tracking-tight">{{ $stats['rejected'] }}</div>
            </div>
            <div class="mt-3 text-xs text-ink-muted">Periksa catatan admin</div>
        </a>
    </section>

    <!-- Charts Section -->
    <section aria-label="Grafik Statistik Laporan Saya" class="mt-6 grid grid-cols-1 lg:grid-cols-5 gap-4">
        <div class="lg:col-span-3 bg-white rounded-xl border border-[#D5E0D8] p-5 sm:p-6">
            <div class="pb-4 border-b border-[#D5E0D8]">
                <h2 class="text-base font-bold text-ink">Perbandingan Laporan Saya Berdasarkan Kategori Sampah</h2>
                <p class="text-xs text-ink-muted mt-0.5">Jumlah laporan berdasarkan klasifikasi Organic, Plastic, dan Paper Waste</p>
                <div class="mt-3 flex flex-wrap items-center gap-2 text-xs font-mono">
                    <span class="px-2.5 py-1 rounded bg-forest-50 border border-[#D5E0D8] text-ink">Organic: {{ $stats['organic'] }}</span>
                    <span class="px-2.5 py-1 rounded bg-forest-50 border border-[#D5E0D8] text-ink">Plastic: {{ $stats['plastic'] }}</span>
                    <span class="px-2.5 py-1 rounded bg-forest-50 border border-[#D5E0D8] text-ink">Paper: {{ $stats['paper'] }}</span>
                </div>
            </div>
            <div class="mt-4 relative h-64">
                <canvas id="userCategoryChart" aria-label="Grafik kategori sampah laporan saya" role="img"></canvas>
            </div>
        </div>

        <div class="lg:col-span-2 bg-white rounded-xl border border-[#D5E0D8] p-5 sm:p-6">
            <div class="pb-4 border-b border-[#D5E0D8]">
                <h2 class="text-base font-bold text-ink">Distribusi Status Laporan Saya</h2>
                <p class="text-xs text-ink-muted mt-0.5">Proporsi status dari {{ $stats['total'] }} laporan yang Anda kirimkan</p>
            </div>
            <div class="mt-4 relative h-64 flex items-center justify-center">
                <canvas id="userStatusChart" aria-label="Grafik distribusi status laporan saya" role="img"></canvas>
            </div>
        </div>
    </section>

    <!-- Recent Reports List -->
    <section aria-label="Riwayat Laporan Terbaru Saya" class="mt-6 bg-white rounded-xl border border-[#D5E0D8] overflow-hidden">
        <div class="p-5 sm:px-6 border-b border-[#D5E0D8] flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-base font-bold text-ink">Laporan Terbaru Anda</h2>
                <p class="text-xs text-ink-muted mt-0.5">Daftar laporan terakhir beserta catatan tindak lanjut dari petugas</p>
            </div>
            <a href="{{ route('reports.index') }}" class="text-sm font-semibold text-forest-700 hover:underline min-h-[44px] inline-flex items-center">
                Lihat Semua & Filter
            </a>
        </div>

        @if($recentReports->isEmpty())
            <div class="p-8 text-center">
                <div class="text-sm font-semibold text-ink">Anda belum mengirimkan laporan temuan sampah</div>
                <p class="text-xs text-ink-muted mt-1 max-w-md mx-auto">
                    Ambil foto tumpukan sampah di sekitar Anda, gunakan deteksi AI otomatis, dan kirimkan titik koordinatnya agar segera ditindaklanjuti.
                </p>
                <a
                    href="{{ route('reports.create') }}"
                    class="mt-4 inline-flex items-center gap-2 px-4 min-h-[44px] rounded-lg bg-forest-700 hover:bg-forest-800 text-white text-sm font-semibold transition"
                >
                    Buat Laporan Pertama Anda
                </a>
            </div>
        @else
            <div class="divide-y divide-[#D5E0D8]">
                @foreach($recentReports as $report)
                    @php
                        $statusStyles = [
                            'Pending' => 'bg-[#FEF3C7] text-[#78350F] border-[#F59E0B]',
                            'In Progress' => 'bg-[#DBEAFE] text-[#1E3A8A] border-[#3B82F6]',
                            'Completed' => 'bg-[#DCFCE7] text-[#14532D] border-[#22C55E]',
                            'Rejected' => 'bg-[#FEE2E2] text-[#7F1D1D] border-[#EF4444]',
                        ];
                    @endphp
                    <div class="p-4 sm:px-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex items-start gap-3.5">
                            <img
                                src="{{ $report->photo_url }}"
                                alt="Foto laporan #{{ $report->id }}"
                                class="w-16 h-16 rounded-lg object-cover border border-[#D5E0D8] shrink-0 bg-forest-50"
                            >
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <a href="{{ route('reports.show', $report) }}" class="font-bold text-sm text-ink hover:text-forest-700">
                                        #{{ $report->id }} - {{ $report->waste_type }}
                                    </a>
                                    <span class="px-2 py-0.5 rounded text-xs font-semibold border {{ $statusStyles[$report->status] ?? 'bg-gray-100 text-gray-800' }}">
                                        {{ $report->status }}
                                    </span>
                                    @if($report->confidence_score)
                                        <span class="font-mono text-xs text-ink-muted">
                                            (AI {{ number_format($report->confidence_score * 100, 1) }}%)
                                        </span>
                                    @endif
                                </div>
                                <p class="text-xs text-ink mt-1">{{ $report->description }}</p>
                                @if($report->admin_notes)
                                    <div class="mt-1.5 text-xs bg-forest-50 border border-[#D5E0D8] rounded px-2.5 py-1 text-ink">
                                        <span class="font-semibold">Catatan Admin:</span> {{ $report->admin_notes }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 shrink-0">
                            <a
                                href="{{ route('reports.show', $report) }}"
                                class="inline-flex items-center justify-center px-3.5 min-h-[44px] rounded-lg border border-[#B8C9BD] text-xs font-semibold text-ink hover:bg-forest-50"
                            >
                                Detail
                            </a>
                            @if($report->canBeEditedBy())
                                <a
                                    href="{{ route('reports.edit', $report) }}"
                                    class="inline-flex items-center justify-center px-3.5 min-h-[44px] rounded-lg border border-[#B8C9BD] text-xs font-semibold text-ink hover:bg-forest-50"
                                >
                                    Edit
                                </a>
                            @endif
                            @if($report->canBeDeleted())
                                <form
                                    method="POST"
                                    action="{{ route('reports.destroy', $report) }}"
                                    data-confirm="Hapus laporan sampah #{{ $report->id }} ini secara permanen?"
                                    data-confirm-title="Hapus Laporan Sampah"
                                    class="inline-block"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button
                                        type="submit"
                                        class="inline-flex items-center justify-center px-3.5 min-h-[44px] rounded-lg border border-red-300 text-xs font-semibold text-[#7F1D1D] hover:bg-[#FEE2E2] transition"
                                    >
                                        Hapus
                                    </button>
                                </form>
                            @endif
                        </div>
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
        const catCtx = document.getElementById('userCategoryChart');
        if (catCtx) {
            new Chart(catCtx, {
                type: 'bar',
                data: {
                    labels: ['Organic Waste', 'Plastic Waste', 'Paper Waste'],
                    datasets: [{
                        label: 'Laporan Saya',
                        data: [{{ $stats['organic'] }}, {{ $stats['plastic'] }}, {{ $stats['paper'] }}],
                        backgroundColor: ['#15803D', '#0369A1', '#B45309'],
                        borderRadius: 6,
                        maxBarThickness: 56
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, ticks: { precision: 0, color: '#43544A' } },
                        x: { ticks: { color: '#111C16', font: { weight: '600' } }, grid: { display: false } }
                    }
                }
            });
        }

        const stCtx = document.getElementById('userStatusChart');
        if (stCtx) {
            new Chart(stCtx, {
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
                    cutout: '60%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { color: '#111C16', padding: 12, usePointStyle: true }
                        }
                    }
                }
            });
        }
    });
</script>
@endpush
