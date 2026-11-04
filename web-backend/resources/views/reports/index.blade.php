@extends('layouts.app')

@section('title', 'Daftar Laporan Sampah - Smart Waste Management')
@section('page_title', auth()->user()->isAdmin() ? 'Manajemen Seluruh Laporan Sampah' : 'Riwayat Laporan Sampah Saya')
@section('page_subtitle', 'Cari, filter kategori atau status, dan kelola data laporan temuan sampah.')

@section('header_actions')
    <a
        href="{{ route('reports.create') }}"
        class="inline-flex items-center gap-2 px-4 min-h-[44px] rounded-lg bg-forest-700 hover:bg-forest-800 text-white text-sm font-semibold transition"
    >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        <span>Tambah Laporan</span>
    </a>
@endsection

@section('content')
    @php
        $statusStyles = [
            'Pending' => 'bg-[#FEF3C7] text-[#78350F] border-[#F59E0B]',
            'In Progress' => 'bg-[#DBEAFE] text-[#1E3A8A] border-[#3B82F6]',
            'Completed' => 'bg-[#DCFCE7] text-[#14532D] border-[#22C55E]',
            'Rejected' => 'bg-[#FEE2E2] text-[#7F1D1D] border-[#EF4444]',
        ];
        $currentStatus = $filters['status'];
        $statuses = [
            '' => 'Semua Status',
            'Pending' => 'Pending',
            'In Progress' => 'In Progress',
            'Completed' => 'Completed',
            'Rejected' => 'Rejected',
        ];
    @endphp

    <!-- Quick Status Tabs -->
    <div class="flex items-center gap-2 overflow-x-auto pb-1 mb-4">
        @foreach($statuses as $stKey => $stLabel)
            @php
                $isActive = ($currentStatus === $stKey);
                $queryData = array_merge(request()->query(), ['status' => $stKey]);
                if ($stKey === '') unset($queryData['status']);
                $tabUrl = route('reports.index', $queryData);
            @endphp
            <a
                href="{{ $tabUrl }}"
                class="px-3.5 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition {{ $isActive ? 'bg-forest-700 text-white shadow-xs' : 'bg-white text-ink-muted border border-[#D5E0D8] hover:bg-forest-50 hover:text-ink' }}"
            >
                {{ $stLabel }}
            </a>
        @endforeach
    </div>

    <!-- Filter & Search Bar -->
    <section aria-label="Pencarian dan Filter Laporan" class="bg-white rounded-xl border border-[#D5E0D8] p-4 sm:p-5">
        <form method="GET" action="{{ route('reports.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">
            <div class="sm:col-span-2 lg:col-span-5">
                <label for="search" class="block text-xs font-semibold text-ink mb-1">
                    Cari Laporan
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-ink-muted">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                        </svg>
                    </span>
                    <input
                        id="search"
                        type="text"
                        name="search"
                        value="{{ $filters['search'] }}"
                        placeholder="Ketik deskripsi, alamat, atau nama pelapor..."
                        class="w-full pl-9 pr-3.5 min-h-[44px] rounded-lg border border-[#B8C9BD] bg-white text-sm text-ink focus:border-forest-600 focus:ring-1 focus:ring-forest-600"
                    >
                </div>
            </div>

            <div class="lg:col-span-3">
                <label for="waste_type" class="block text-xs font-semibold text-ink mb-1">Kategori Sampah (AI)</label>
                <select
                    id="waste_type"
                    name="waste_type"
                    class="w-full px-3.5 min-h-[44px] rounded-lg border border-[#B8C9BD] bg-white text-sm text-ink focus:border-forest-600 focus:ring-1 focus:ring-forest-600"
                >
                    <option value="">Semua Kategori</option>
                    @foreach(['Organic Waste', 'Plastic Waste', 'Paper Waste'] as $type)
                        <option value="{{ $type }}" @selected($filters['waste_type'] === $type)>{{ $type }}</option>
                    @endforeach
                </select>
            </div>

            <div class="lg:col-span-2">
                <label for="status" class="block text-xs font-semibold text-ink mb-1">Status Laporan</label>
                <select
                    id="status"
                    name="status"
                    class="w-full px-3.5 min-h-[44px] rounded-lg border border-[#B8C9BD] bg-white text-sm text-ink focus:border-forest-600 focus:ring-1 focus:ring-forest-600"
                >
                    <option value="">Semua Status</option>
                    @foreach(['Pending', 'In Progress', 'Completed', 'Rejected'] as $st)
                        <option value="{{ $st }}" @selected($filters['status'] === $st)>{{ $st }}</option>
                    @endforeach
                </select>
            </div>

            <div class="sm:col-span-2 lg:col-span-2 flex items-center gap-2">
                <button
                    type="submit"
                    class="flex-1 min-h-[44px] px-4 rounded-lg bg-forest-700 hover:bg-forest-800 text-white text-sm font-semibold transition shadow-xs"
                >
                    Terapkan
                </button>
                @if($filters['search'] !== '' || $filters['waste_type'] !== '' || $filters['status'] !== '')
                    <a
                        href="{{ route('reports.index') }}"
                        class="min-h-[44px] px-3 inline-flex items-center justify-center rounded-lg border border-[#B8C9BD] text-xs font-semibold text-ink hover:bg-forest-50 transition"
                        title="Reset Filter"
                    >
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </section>

    <!-- Reports List -->
    <section aria-label="Tabel Data Laporan Sampah" class="mt-6 bg-white rounded-xl border border-[#D5E0D8] overflow-hidden">
        @if($reports->isEmpty())
            <div class="p-10 text-center">
                <div class="text-base font-bold text-ink">Tidak ada data laporan sampah yang ditemukan</div>
                <p class="text-sm text-ink-muted mt-1 max-w-md mx-auto">
                    @if($filters['search'] !== '' || $filters['waste_type'] !== '' || $filters['status'] !== '')
                        Tidak ada laporan yang cocok dengan filter pencarian Anda. Silakan ubah kata kunci atau reset filter.
                    @else
                        Belum ada laporan temuan sampah yang tersimpan. Klik tombol "Tambah Laporan" untuk mengirimkan laporan baru.
                    @endif
                </p>
                <div class="mt-4 flex justify-center gap-3">
                    @if($filters['search'] !== '' || $filters['waste_type'] !== '' || $filters['status'] !== '')
                        <a href="{{ route('reports.index') }}" class="inline-flex items-center px-4 min-h-[44px] rounded-lg border border-[#B8C9BD] text-sm font-semibold text-ink hover:bg-forest-50">
                            Tampilkan Semua Laporan
                        </a>
                    @endif
                    <a href="{{ route('reports.create') }}" class="inline-flex items-center px-4 min-h-[44px] rounded-lg bg-forest-700 text-white text-sm font-semibold hover:bg-forest-800">
                        Buat Laporan Baru
                    </a>
                </div>
            </div>
        @else
            <!-- Desktop Table View -->
            <div class="hidden lg:block overflow-x-auto">
                <table class="w-full text-left border-collapse table-fixed">
                    <thead>
                        <tr class="bg-forest-50/80 border-b border-[#D5E0D8] text-[11px] font-bold uppercase tracking-wider text-ink-muted">
                            <th class="py-3.5 pl-6 pr-4 {{ auth()->user()->isAdmin() ? 'w-[32%]' : 'w-[42%]' }}">Laporan & Waktu</th>
                            @if(auth()->user()->isAdmin())
                                <th class="py-3.5 px-3 w-[18%]">Pelapor</th>
                            @endif
                            <th class="py-3.5 px-3 {{ auth()->user()->isAdmin() ? 'w-[14%]' : 'w-[16%]' }}">Kategori AI</th>
                            <th class="py-3.5 px-3 {{ auth()->user()->isAdmin() ? 'w-[20%]' : 'w-[24%]' }}">Lokasi & Koordinat</th>
                            <th class="py-3.5 pl-3 pr-6 {{ auth()->user()->isAdmin() ? 'w-[16%]' : 'w-[18%]' }} text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#D5E0D8] text-sm">
                        @foreach($reports as $report)
                            <tr class="hover:bg-forest-50/50 transition align-middle">
                                <!-- Laporan & Waktu -->
                                <td class="py-3.5 pl-6 pr-4">
                                    <div class="flex items-start gap-3">
                                        <a href="{{ route('reports.show', $report) }}" class="shrink-0 group block" title="Lihat detail foto laporan #{{ $report->id }}">
                                            <img
                                                src="{{ $report->photo_url }}"
                                                alt="Foto laporan #{{ $report->id }}"
                                                class="w-[70px] h-[70px] rounded-lg object-cover border border-[#D5E0D8] group-hover:border-forest-600 transition bg-forest-50 shadow-xs"
                                            >
                                        </a>
                                        <div class="min-w-0 flex-1">
                                            <a href="{{ route('reports.show', $report) }}" class="font-semibold text-xs text-ink hover:text-forest-700 transition block truncate" title="{{ $report->description }}">
                                                {{ $report->description }}
                                            </a>

                                            <!-- Status Laporan & Catatan Admin -->
                                            <div class="mt-1.5 flex items-center flex-wrap gap-1.5">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-semibold border {{ $statusStyles[$report->status] ?? '' }}">
                                                    {{ $report->status }}
                                                </span>

                                                @if($report->admin_notes)
                                                    <span class="inline-flex items-center gap-1 text-[11px] text-ink-muted bg-forest-50 border border-[#D5E0D8] px-2 py-0.5 rounded max-w-[190px] truncate" title="Catatan Admin: {{ $report->admin_notes }}">
                                                        <svg class="w-3 h-3 text-forest-700 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z"/>
                                                        </svg>
                                                        <span class="truncate">{{ $report->admin_notes }}</span>
                                                    </span>
                                                @endif
                                            </div>

                                            <!-- ID & Waktu Laporan -->
                                            <div class="flex items-center gap-1.5 mt-1.5">
                                                <span class="font-mono text-[11px] font-bold text-forest-800">
                                                    #{{ $report->id }}
                                                </span>
                                                <span class="text-ink-muted text-[11px] leading-none">/</span>
                                                <span class="text-[11px] text-ink-muted font-medium">
                                                    {{ $report->created_at?->format('d M Y, H:i') }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Pelapor -->
                                @if(auth()->user()->isAdmin())
                                    <td class="py-3.5 px-3">
                                        <div class="font-semibold text-xs text-ink truncate" title="{{ $report->user?->name ?? 'User' }}">
                                            {{ $report->user?->name ?? 'User' }}
                                        </div>
                                        <div class="text-[11px] font-mono text-ink-muted mt-0.5 truncate" title="{{ $report->user?->email }}">
                                            {{ $report->user?->email }}
                                        </div>
                                    </td>
                                @endif

                                <!-- Kategori AI -->
                                <td class="py-3.5 px-3">
                                    <div class="font-semibold text-xs text-ink truncate">{{ $report->waste_type }}</div>
                                    @if($report->confidence_score !== null)
                                        <div class="mt-1">
                                            <span class="inline-flex items-center gap-1 font-mono text-[11px] px-1.5 py-0.5 rounded bg-forest-50 border border-[#D5E0D8] text-forest-800 font-medium" title="Tingkat Keyakinan Klasifikasi AI">
                                                <svg class="w-3 h-3 text-forest-700 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                <span>{{ number_format($report->confidence_score * 100, 1) }}%</span>
                                            </span>
                                        </div>
                                    @endif
                                </td>

                                <!-- Lokasi & Koordinat -->
                                <td class="py-3.5 px-3">
                                    <div class="text-xs font-semibold text-ink truncate" title="{{ $report->address ?: 'Alamat tidak diisi' }}">
                                        {{ $report->address ?: 'Alamat tidak diisi' }}
                                    </div>
                                    <div class="font-mono text-[11px] text-ink-muted mt-1 flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 text-forest-700 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                        <span class="truncate select-all" title="Koordinat GPS (Latitude, Longitude)">{{ number_format($report->latitude, 5) }}, {{ number_format($report->longitude, 5) }}</span>
                                    </div>
                                </td>

                                <!-- Aksi -->
                                <td class="py-3.5 pl-3 pr-6 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center justify-center gap-1.5 flex-nowrap">
                                        @if(auth()->user()->isAdmin())
                                            <div class="relative group/btn inline-flex">
                                                <button
                                                    type="button"
                                                    onclick="openStatusModal({{ $report->id }}, @js($report->status), @js($report->admin_notes ?? ''))"
                                                    class="w-7 h-7 rounded-md border border-forest-200 bg-forest-50/70 hover:bg-forest-100 text-forest-800 flex items-center justify-center transition shadow-xs focus:outline-none focus:ring-2 focus:ring-forest-600 focus:ring-offset-1"
                                                    aria-label="Ubah Status"
                                                    title="Ubah Status Laporan"
                                                >
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/>
                                                    </svg>
                                                </button>
                                                <div class="pointer-events-none absolute bottom-full right-0 mb-1.5 opacity-0 invisible group-hover/btn:opacity-100 group-hover/btn:visible group-focus-within/btn:opacity-100 group-focus-within/btn:visible transition-all duration-150 z-30 flex flex-col items-center">
                                                    <span class="px-2 py-0.5 text-[11px] font-semibold text-white bg-forest-950 rounded shadow-md whitespace-nowrap">
                                                        Ubah Status
                                                    </span>
                                                    <span class="w-1.5 h-1.5 bg-forest-950 rotate-45 -mt-1"></span>
                                                </div>
                                            </div>
                                        @endif

                                        <div class="relative group/btn inline-flex">
                                            <a
                                                href="{{ route('reports.show', $report) }}"
                                                class="w-7 h-7 rounded-md border border-[#B8C9BD] text-ink bg-white hover:bg-forest-50 hover:border-forest-400 flex items-center justify-center transition shadow-xs focus:outline-none focus:ring-2 focus:ring-forest-600 focus:ring-offset-1"
                                                aria-label="Detail"
                                                title="Lihat Detail Laporan"
                                            >
                                                <svg class="w-3.5 h-3.5 text-ink-muted group-hover/btn:text-ink transition" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                </svg>
                                            </a>
                                            <div class="pointer-events-none absolute bottom-full right-0 mb-1.5 opacity-0 invisible group-hover/btn:opacity-100 group-hover/btn:visible group-focus-within/btn:opacity-100 group-focus-within/btn:visible transition-all duration-150 z-30 flex flex-col items-center">
                                                <span class="px-2 py-0.5 text-[11px] font-semibold text-white bg-forest-950 rounded shadow-md whitespace-nowrap">
                                                    Detail
                                                </span>
                                                <span class="w-1.5 h-1.5 bg-forest-950 rotate-45 -mt-1"></span>
                                            </div>
                                        </div>

                                        @if($report->canBeEditedBy())
                                            <div class="relative group/btn inline-flex">
                                                <a
                                                    href="{{ route('reports.edit', $report) }}"
                                                    class="w-7 h-7 rounded-md border border-[#B8C9BD] text-ink bg-white hover:bg-forest-50 hover:border-forest-400 flex items-center justify-center transition shadow-xs focus:outline-none focus:ring-2 focus:ring-forest-600 focus:ring-offset-1"
                                                    aria-label="Edit"
                                                    title="Edit Laporan"
                                                >
                                                    <svg class="w-3.5 h-3.5 text-ink-muted group-hover/btn:text-ink transition" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125"/>
                                                    </svg>
                                                </a>
                                                <div class="pointer-events-none absolute bottom-full right-0 mb-1.5 opacity-0 invisible group-hover/btn:opacity-100 group-hover/btn:visible group-focus-within/btn:opacity-100 group-focus-within/btn:visible transition-all duration-150 z-30 flex flex-col items-center">
                                                    <span class="px-2 py-0.5 text-[11px] font-semibold text-white bg-forest-950 rounded shadow-md whitespace-nowrap">
                                                        Edit
                                                    </span>
                                                    <span class="w-1.5 h-1.5 bg-forest-950 rotate-45 -mt-1"></span>
                                                </div>
                                            </div>
                                        @endif

                                        @if($report->canBeDeleted())
                                            <div class="relative group/btn inline-flex">
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
                                                        class="w-7 h-7 rounded-md border border-red-200 bg-white text-[#7F1D1D] hover:bg-red-50 hover:border-red-300 flex items-center justify-center transition shadow-xs focus:outline-none focus:ring-2 focus:ring-red-400 focus:ring-offset-1"
                                                        aria-label="Hapus"
                                                        title="Hapus Laporan"
                                                    >
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                                                        </svg>
                                                    </button>
                                                </form>
                                                <div class="pointer-events-none absolute bottom-full right-0 mb-1.5 opacity-0 invisible group-hover/btn:opacity-100 group-hover/btn:visible group-focus-within/btn:opacity-100 group-focus-within/btn:visible transition-all duration-150 z-30 flex flex-col items-center">
                                                    <span class="px-2 py-0.5 text-[11px] font-semibold text-white bg-forest-950 rounded shadow-md whitespace-nowrap">
                                                        Hapus
                                                    </span>
                                                    <span class="w-1.5 h-1.5 bg-forest-950 rotate-45 -mt-1"></span>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Mobile & Tablet Stacked Cards View -->
            <div class="lg:hidden divide-y divide-[#D5E0D8]">
                @foreach($reports as $report)
                    <div class="p-4 space-y-3">
                        <div class="flex items-start gap-3">
                            <a href="{{ route('reports.show', $report) }}" class="shrink-0" title="Lihat detail foto laporan #{{ $report->id }}">
                                <img
                                    src="{{ $report->photo_url }}"
                                    alt="Foto laporan #{{ $report->id }}"
                                    class="w-16 h-16 rounded-lg object-cover border border-[#D5E0D8] bg-forest-50 shadow-xs"
                                >
                            </a>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-mono text-xs font-bold text-forest-800">
                                        #{{ $report->id }}
                                    </span>
                                    <span class="px-2 py-0.5 rounded text-xs font-semibold border {{ $statusStyles[$report->status] ?? '' }}">
                                        {{ $report->status }}
                                    </span>
                                </div>
                                <a href="{{ route('reports.show', $report) }}" class="text-xs font-bold text-ink mt-1.5 block hover:text-forest-700 truncate" title="{{ $report->description }}">
                                    {{ $report->description }}
                                </a>
                                <div class="text-xs text-ink-muted mt-1 flex items-center gap-1">
                                    <span class="font-medium text-ink">{{ $report->waste_type }}</span>
                                    @if($report->confidence_score !== null)
                                        <span class="font-mono text-[10px] text-forest-800 bg-forest-100/60 px-1 py-0.2 rounded font-medium">({{ number_format($report->confidence_score * 100, 1) }}%)</span>
                                    @endif
                                </div>
                                <div class="text-[11px] text-ink-muted mt-1 flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5 text-forest-700 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    <span class="truncate">{{ $report->address ?: ($report->latitude . ', ' . $report->longitude) }}</span>
                                </div>
                                @if(auth()->user()->isAdmin())
                                    <div class="text-[11px] text-ink-muted mt-1">
                                        Pelapor: <span class="font-medium text-ink">{{ $report->user?->name }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        @if($report->admin_notes)
                            <div class="text-xs bg-forest-50 border border-[#D5E0D8] rounded-lg p-2.5 text-ink">
                                <strong>Catatan Admin:</strong> {{ $report->admin_notes }}
                            </div>
                        @endif

                        <div class="flex flex-wrap items-center gap-2 pt-1 border-t border-[#D5E0D8]/60">
                            @if(auth()->user()->isAdmin())
                                <button
                                    type="button"
                                    onclick="openStatusModal({{ $report->id }}, @js($report->status), @js($report->admin_notes ?? ''))"
                                    class="px-3.5 min-h-[44px] rounded-lg bg-forest-700 hover:bg-forest-800 text-white text-xs font-semibold shadow-xs transition"
                                >
                                    Ubah Status
                                </button>
                            @endif
                            <a href="{{ route('reports.show', $report) }}" class="inline-flex items-center px-3.5 min-h-[44px] rounded-lg border border-[#B8C9BD] text-xs font-semibold text-ink hover:bg-forest-50 transition">
                                Detail
                            </a>
                            @if($report->canBeEditedBy())
                                <a href="{{ route('reports.edit', $report) }}" class="inline-flex items-center px-3.5 min-h-[44px] rounded-lg border border-[#B8C9BD] text-xs font-semibold text-ink hover:bg-forest-50 transition">
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
                                    <button type="submit" class="px-3.5 min-h-[44px] rounded-lg border border-red-300 text-xs font-semibold text-[#7F1D1D] hover:bg-red-50 transition">
                                        Hapus
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            @if($reports->hasPages())
                <div class="px-4 py-3.5 border-t border-[#D5E0D8] bg-forest-50/50">
                    {{ $reports->links() }}
                </div>
            @endif
        @endif
    </section>

    @if(auth()->user()->isAdmin())
        <!-- Accessible Modal for Quick Status Update -->
        <div
            id="statusModal"
            role="dialog"
            aria-modal="true"
            aria-labelledby="statusModalTitle"
            class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-black/60"
        >
            <div class="bg-white rounded-xl border border-[#D5E0D8] w-full max-w-md p-6 shadow-lg">
                <div class="flex items-center justify-between pb-3 border-b border-[#D5E0D8]">
                    <h3 id="statusModalTitle" class="text-base font-bold text-ink">Perbarui Status Laporan</h3>
                    <button
                        type="button"
                        onclick="closeStatusModal()"
                        class="min-h-[44px] min-w-[44px] inline-flex items-center justify-center rounded-lg text-ink-muted hover:bg-forest-50"
                        aria-label="Tutup dialog"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form id="statusModalForm" method="POST" action="" class="mt-4 space-y-4">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label for="modal_status" class="block text-sm font-semibold text-ink mb-1.5">Status Penanganan</label>
                        <select
                            id="modal_status"
                            name="status"
                            class="w-full px-3.5 min-h-[44px] rounded-lg border border-[#B8C9BD] bg-white text-sm text-ink font-semibold"
                        >
                            <option value="Pending">Pending</option>
                            <option value="In Progress">In Progress</option>
                            <option value="Completed">Completed</option>
                            <option value="Rejected">Rejected</option>
                        </select>
                    </div>

                    <div>
                        <label for="modal_admin_notes" class="block text-sm font-semibold text-ink mb-1.5">Catatan Admin (Opsional)</label>
                        <textarea
                            id="modal_admin_notes"
                            name="admin_notes"
                            rows="3"
                            placeholder="Tuliskan keterangan tindak lanjut petugas atau alasan penolakan..."
                            class="w-full p-3.5 rounded-lg border border-[#B8C9BD] bg-white text-sm text-ink"
                        ></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button
                            type="button"
                            onclick="closeStatusModal()"
                            class="px-4 min-h-[44px] rounded-lg border border-[#B8C9BD] text-sm font-semibold text-ink hover:bg-forest-50"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            class="px-5 min-h-[44px] rounded-lg bg-forest-700 hover:bg-forest-800 text-white text-sm font-semibold transition"
                        >
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        @push('scripts')
        <script>
            const statusModal = document.getElementById('statusModal');
            const statusModalForm = document.getElementById('statusModalForm');
            const statusModalTitle = document.getElementById('statusModalTitle');
            const modalStatusSelect = document.getElementById('modal_status');
            const modalNotesInput = document.getElementById('modal_admin_notes');

            const baseReportsUrl = @js(url('/reports'));

            function openStatusModal(reportId, currentStatus, currentNotes) {
                statusModalForm.action = `${baseReportsUrl}/${reportId}/status`;
                statusModalTitle.textContent = `Perbarui Status Laporan #${reportId}`;
                modalStatusSelect.value = currentStatus;
                modalNotesInput.value = currentNotes || '';
                statusModal.classList.remove('hidden');
                statusModal.classList.add('flex');
                modalStatusSelect.focus();
            }

            function closeStatusModal() {
                statusModal.classList.add('hidden');
                statusModal.classList.remove('flex');
            }

            statusModal?.addEventListener('click', function (e) {
                if (e.target === statusModal) closeStatusModal();
            });

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && !statusModal.classList.contains('hidden')) {
                    closeStatusModal();
                }
            });
        </script>
        @endpush
    @endif
@endsection
