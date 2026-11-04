@extends('layouts.app')

@section('title', 'Detail Laporan #' . $report->id . ' - Smart Waste Management')
@section('page_title', 'Detail Laporan Sampah #' . $report->id)
@section('page_subtitle', 'Informasi lengkap bukti foto, hasil klasifikasi AI, titik koordinat, dan status penanganan.')

@section('header_actions')
    <div class="flex flex-wrap items-center gap-2">
        <a
            href="{{ route('reports.index') }}"
            class="inline-flex items-center px-3.5 min-h-[44px] rounded-lg border border-[#B8C9BD] bg-white hover:bg-forest-50 text-ink text-xs sm:text-sm font-semibold transition"
        >
            Kembali
        </a>
        @if($report->canBeEditedBy())
            <a
                href="{{ route('reports.edit', $report) }}"
                class="inline-flex items-center px-4 min-h-[44px] rounded-lg bg-forest-700 hover:bg-forest-800 text-white text-xs sm:text-sm font-semibold transition"
            >
                Edit Laporan
            </a>
        @endif
    </div>
@endsection

@section('content')
    @php
        $statusStyles = [
            'Pending' => 'bg-[#FEF3C7] text-[#78350F] border-[#F59E0B]',
            'In Progress' => 'bg-[#DBEAFE] text-[#1E3A8A] border-[#3B82F6]',
            'Completed' => 'bg-[#DCFCE7] text-[#14532D] border-[#22C55E]',
            'Rejected' => 'bg-[#FEE2E2] text-[#7F1D1D] border-[#EF4444]',
        ];
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Left Column: Photo & Report Information -->
        <div class="lg:col-span-7 space-y-6">
            <div class="bg-white rounded-xl border border-[#D5E0D8] overflow-hidden">
                <div class="bg-forest-950 flex items-center justify-center max-h-96 overflow-hidden">
                    <img
                        src="{{ $report->photo_url }}"
                        alt="Foto bukti laporan sampah #{{ $report->id }}"
                        class="w-full max-h-96 object-contain"
                    >
                </div>

                <div class="p-5 sm:p-6 space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-2 pb-4 border-b border-[#D5E0D8]">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="px-3 py-1 rounded-md text-xs font-bold bg-forest-50 border border-[#B8C9BD] text-ink">
                                {{ $report->waste_type }}
                            </span>
                            @if($report->confidence_score !== null)
                                <span class="font-mono text-xs px-2.5 py-1 rounded-md bg-forest-50 border border-[#D5E0D8] text-ink">
                                    Confidence AI: {{ number_format($report->confidence_score * 100, 2) }}%
                                </span>
                            @endif
                        </div>

                        <span class="px-3 py-1 rounded-md text-xs font-bold border {{ $statusStyles[$report->status] ?? '' }}">
                            Status: {{ $report->status }}
                        </span>
                    </div>

                    <div>
                        <h2 class="text-sm font-bold text-ink">Deskripsi Temuan Sampah</h2>
                        <p class="mt-1.5 text-sm text-ink leading-relaxed whitespace-pre-line">{{ $report->description }}</p>
                    </div>

                    @if($report->admin_notes)
                        <div class="p-4 rounded-xl bg-forest-50 border border-[#D5E0D8]">
                            <div class="text-xs font-bold uppercase tracking-wider text-forest-800">Catatan Tindak Lanjut Admin</div>
                            <p class="mt-1 text-sm text-ink">{{ $report->admin_notes }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Right Column: Metadata, GPS Map Link, and Admin Status Action -->
        <div class="lg:col-span-5 space-y-6">
            @if(auth()->user()->isAdmin())
                <div class="bg-white rounded-xl border border-[#D5E0D8] p-5 sm:p-6">
                    <h2 class="text-base font-bold text-ink">Tindak Lanjut Status (Khusus Admin)</h2>
                    <p class="text-xs text-ink-muted mt-0.5">
                        Perbarui status penanganan laporan dan berikan catatan kepada pelapor.
                    </p>

                    <form method="POST" action="{{ route('reports.update-status', $report) }}" class="mt-4 space-y-4">
                        @csrf
                        @method('PATCH')

                        <div>
                            <label for="status" class="block text-xs font-semibold text-ink mb-1">Status Penanganan</label>
                            <select
                                id="status"
                                name="status"
                                class="w-full px-3.5 min-h-[44px] rounded-lg border border-[#B8C9BD] bg-white text-sm font-semibold text-ink"
                            >
                                @foreach(['Pending', 'In Progress', 'Completed', 'Rejected'] as $st)
                                    <option value="{{ $st }}" @selected($report->status === $st)>{{ $st }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="admin_notes" class="block text-xs font-semibold text-ink mb-1">Catatan Petugas / Admin</label>
                            <textarea
                                id="admin_notes"
                                name="admin_notes"
                                rows="3"
                                placeholder="Contoh: Armada kebersihan telah mengangkut sampah pada pukul 10:15 WIB."
                                class="w-full p-3 rounded-lg border border-[#B8C9BD] bg-white text-sm text-ink"
                            >{{ old('admin_notes', $report->admin_notes) }}</textarea>
                        </div>

                        <button
                            type="submit"
                            class="w-full min-h-[44px] px-4 rounded-lg bg-forest-700 hover:bg-forest-800 text-white text-sm font-semibold transition"
                        >
                            Simpan Status & Catatan
                        </button>
                    </form>
                </div>
            @endif

            <!-- Location & Reporter Info -->
            <div class="bg-white rounded-xl border border-[#D5E0D8] p-5 sm:p-6 space-y-4">
                <h2 class="text-base font-bold text-ink">Informasi Lokasi & Pelapor</h2>

                <dl class="space-y-3 text-sm border-t border-[#D5E0D8] pt-3">
                    <div>
                        <dt class="text-xs text-ink-muted font-medium">Alamat Lokasi</dt>
                        <dd class="text-ink font-medium mt-0.5">{{ $report->address ?: 'Tidak dicantumkan' }}</dd>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="p-3 rounded-lg bg-forest-50 border border-[#D5E0D8]">
                            <dt class="text-xs text-ink-muted">Latitude</dt>
                            <dd class="font-mono text-xs font-semibold text-ink mt-0.5">{{ number_format($report->latitude, 8) }}</dd>
                        </div>
                        <div class="p-3 rounded-lg bg-forest-50 border border-[#D5E0D8]">
                            <dt class="text-xs text-ink-muted">Longitude</dt>
                            <dd class="font-mono text-xs font-semibold text-ink mt-0.5">{{ number_format($report->longitude, 8) }}</dd>
                        </div>
                    </div>

                    <div>
                        <a
                            href="https://www.google.com/maps?q={{ $report->latitude }},{{ $report->longitude }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="w-full inline-flex items-center justify-center gap-2 px-4 min-h-[44px] rounded-lg border border-forest-700 text-forest-700 hover:bg-forest-50 text-xs font-semibold transition"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span>Buka Titik Koordinat di Google Maps</span>
                        </a>
                    </div>

                    <div class="pt-2 border-t border-[#D5E0D8]">
                        <dt class="text-xs text-ink-muted font-medium">Dilaporkan Oleh</dt>
                        <dd class="text-ink font-semibold mt-0.5">{{ $report->user?->name ?? 'User' }} ({{ $report->user?->email }})</dd>
                    </div>

                    <div class="grid grid-cols-2 gap-2 text-xs text-ink-muted">
                        <div>
                            <span class="block">Waktu Lapor:</span>
                            <strong class="text-ink">{{ $report->created_at?->format('d M Y, H:i') }}</strong>
                        </div>
                        <div>
                            <span class="block">Terakhir Diperbarui:</span>
                            <strong class="text-ink">{{ $report->updated_at?->format('d M Y, H:i') }}</strong>
                        </div>
                    </div>
                </dl>

                @if($report->canBeDeleted())
                    <div class="pt-3 border-t border-[#D5E0D8] flex items-center justify-between">
                        <form
                            method="POST"
                            action="{{ route('reports.destroy', $report) }}"
                            data-confirm="Apakah Anda yakin ingin menghapus laporan #{{ $report->id }} ini secara permanen?"
                            data-confirm-title="Hapus Laporan Sampah"
                            class="w-full"
                        >
                            @csrf
                            @method('DELETE')
                            <button
                                type="submit"
                                class="w-full min-h-[44px] px-4 rounded-lg border border-red-300 text-[#7F1D1D] hover:bg-[#FEE2E2] text-xs font-semibold transition"
                            >
                                Hapus Laporan Ini
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
