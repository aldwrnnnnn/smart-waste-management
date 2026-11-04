@extends('layouts.app')

@section('title', 'Manajemen Pengguna - Smart Waste Management')
@section('page_title', 'Manajemen Pengguna & Hak Akses')
@section('page_subtitle', 'Kelola akun Administrator dan Warga Pelapor beserta peran aksesnya.')

@section('header_actions')
    <button
        type="button"
        onclick="openCreateUserModal()"
        class="inline-flex items-center gap-2 px-4 min-h-[44px] rounded-lg bg-forest-700 hover:bg-forest-800 text-white text-sm font-semibold transition"
    >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        <span>Tambah Pengguna Baru</span>
    </button>
@endsection

@section('content')
    <!-- Summary Row -->
    <section aria-label="Ringkasan Akun Pengguna" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-[#D5E0D8] p-4">
            <div class="text-xs font-semibold uppercase tracking-wider text-ink-muted">Total Pengguna</div>
            <div class="mt-1.5 text-2xl font-bold font-mono text-ink">{{ $summary['total'] }}</div>
        </div>
        <div class="bg-white rounded-xl border border-[#D5E0D8] p-4">
            <div class="text-xs font-semibold uppercase tracking-wider text-[#14532D]">Administrator (Admin)</div>
            <div class="mt-1.5 text-2xl font-bold font-mono text-ink">{{ $summary['admins'] }}</div>
        </div>
        <div class="bg-white rounded-xl border border-[#D5E0D8] p-4">
            <div class="text-xs font-semibold uppercase tracking-wider text-[#1E3A8A]">Warga Pelapor (User)</div>
            <div class="mt-1.5 text-2xl font-bold font-mono text-ink">{{ $summary['reporters'] }}</div>
        </div>
    </section>

    <!-- Search & Role Filter -->
    <section aria-label="Filter Pengguna" class="mt-6 bg-white rounded-xl border border-[#D5E0D8] p-4 sm:p-5">
        <form method="GET" action="{{ route('admin.users.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
            <div class="sm:col-span-6">
                <label for="search" class="block text-xs font-semibold text-ink mb-1">Cari Nama atau Email Pengguna</label>
                <input
                    id="search"
                    type="text"
                    name="search"
                    value="{{ $filters['search'] }}"
                    placeholder="Ketik nama atau alamat email..."
                    class="w-full px-3.5 min-h-[44px] rounded-lg border border-[#B8C9BD] bg-white text-sm text-ink"
                >
            </div>

            <div class="sm:col-span-3">
                <label for="role" class="block text-xs font-semibold text-ink mb-1">Filter Role</label>
                <select
                    id="role"
                    name="role"
                    class="w-full px-3.5 min-h-[44px] rounded-lg border border-[#B8C9BD] bg-white text-sm text-ink"
                >
                    <option value="">Semua Role</option>
                    <option value="admin" @selected($filters['role'] === 'admin')>Admin</option>
                    <option value="user" @selected($filters['role'] === 'user')>User (Pelapor)</option>
                </select>
            </div>

            <div class="sm:col-span-3 flex items-center gap-2">
                <button
                    type="submit"
                    class="flex-1 min-h-[44px] px-4 rounded-lg bg-forest-700 hover:bg-forest-800 text-white text-sm font-semibold transition"
                >
                    Filter
                </button>
                @if($filters['search'] !== '' || $filters['role'] !== '')
                    <a
                        href="{{ route('admin.users.index') }}"
                        class="min-h-[44px] px-3 inline-flex items-center justify-center rounded-lg border border-[#B8C9BD] text-xs font-semibold text-ink hover:bg-forest-50"
                    >
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </section>

    <!-- Users List -->
    <section aria-label="Daftar Pengguna" class="mt-6 bg-white rounded-xl border border-[#D5E0D8] overflow-hidden">
        @if($users->isEmpty())
            <div class="p-8 text-center">
                <div class="text-base font-bold text-ink">Tidak ada pengguna yang cocok dengan pencarian</div>
                <p class="text-xs text-ink-muted mt-1">Silakan ubah kata kunci pencarian atau tambahkan pengguna baru.</p>
            </div>
        @else
            <!-- Desktop Table -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-forest-50 border-b border-[#D5E0D8] text-xs font-semibold uppercase tracking-wider text-ink-muted">
                            <th class="py-3.5 px-5">ID & Nama Lengkap</th>
                            <th class="py-3.5 px-5">Alamat Email</th>
                            <th class="py-3.5 px-5">Role Akses</th>
                            <th class="py-3.5 px-5">Jumlah Laporan</th>
                            <th class="py-3.5 px-5">Terdaftar</th>
                            <th class="py-3.5 px-5 text-right">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#D5E0D8] text-sm">
                        @foreach($users as $u)
                            <tr class="hover:bg-forest-50/60 transition">
                                <td class="py-3.5 px-5">
                                    <span class="font-mono text-xs text-ink-muted">#{{ $u->id }}</span>
                                    <div class="font-bold text-ink">{{ $u->name }}</div>
                                </td>
                                <td class="py-3.5 px-5 font-mono text-xs text-ink">{{ $u->email }}</td>
                                <td class="py-3.5 px-5">
                                    @if($u->role === 'admin')
                                        <span class="inline-flex px-2.5 py-0.5 rounded text-xs font-semibold bg-[#DCFCE7] text-[#14532D] border border-[#22C55E]">
                                            Admin
                                        </span>
                                    @else
                                        <span class="inline-flex px-2.5 py-0.5 rounded text-xs font-semibold bg-[#DBEAFE] text-[#1E3A8A] border border-[#3B82F6]">
                                            User Pelapor
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-5 font-mono text-sm font-semibold text-ink">
                                    {{ $u->waste_reports_count }} laporan
                                </td>
                                <td class="py-3.5 px-5 text-xs text-ink-muted">
                                    {{ $u->created_at?->format('d M Y') }}
                                </td>
                                <td class="py-3.5 px-5 text-right">
                                    <div class="inline-flex items-center justify-end gap-2">
                                        <button
                                            type="button"
                                            onclick="openEditUserModal({{ $u->id }}, @js($u->name), @js($u->email), @js($u->role))"
                                            class="px-3.5 min-h-[44px] rounded-lg border border-[#B8C9BD] text-xs font-semibold text-ink hover:bg-forest-50"
                                        >
                                            Edit / Role
                                        </button>

                                        @if(auth()->id() !== $u->id)
                                            <form
                                                method="POST"
                                                action="{{ route('admin.users.destroy', $u) }}"
                                                data-confirm="Hapus pengguna {{ $u->name }} beserta seluruh laporannya secara permanen?"
                                                data-confirm-title="Hapus Pengguna"
                                            >
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    type="submit"
                                                    class="px-3.5 min-h-[44px] rounded-lg border border-red-300 text-xs font-semibold text-[#7F1D1D] hover:bg-[#FEE2E2]"
                                                >
                                                    Hapus
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-xs text-ink-muted px-2">Akun Aktif</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Mobile Stacked Cards -->
            <div class="md:hidden divide-y divide-[#D5E0D8]">
                @foreach($users as $u)
                    <div class="p-4 space-y-2.5">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <div class="font-bold text-sm text-ink">{{ $u->name }}</div>
                                <div class="font-mono text-xs text-ink-muted">{{ $u->email }}</div>
                            </div>
                            <span class="px-2.5 py-0.5 rounded text-xs font-semibold border {{ $u->role === 'admin' ? 'bg-[#DCFCE7] text-[#14532D] border-[#22C55E]' : 'bg-[#DBEAFE] text-[#1E3A8A] border-[#3B82F6]' }}">
                                {{ strtoupper($u->role) }}
                            </span>
                        </div>

                        <div class="text-xs text-ink-muted">
                            Total Laporan: <strong class="text-ink font-mono">{{ $u->waste_reports_count }}</strong>
                        </div>

                        <div class="flex items-center gap-2 pt-1">
                            <button
                                type="button"
                                onclick="openEditUserModal({{ $u->id }}, @js($u->name), @js($u->email), @js($u->role))"
                                class="px-3.5 min-h-[44px] rounded-lg border border-[#B8C9BD] text-xs font-semibold text-ink"
                            >
                                Edit / Ubah Role
                            </button>
                            @if(auth()->id() !== $u->id)
                                <form
                                    method="POST"
                                    action="{{ route('admin.users.destroy', $u) }}"
                                    data-confirm="Hapus pengguna {{ $u->name }} beserta seluruh laporannya secara permanen?"
                                    data-confirm-title="Hapus Pengguna"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-3.5 min-h-[44px] rounded-lg border border-red-300 text-xs font-semibold text-[#7F1D1D]">
                                        Hapus
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            @if($users->hasPages())
                <div class="px-5 py-3.5 border-t border-[#D5E0D8] bg-forest-50/50">
                    {{ $users->links() }}
                </div>
            @endif
        @endif
    </section>

    <!-- Create User Modal -->
    <div
        id="createUserModal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="createUserModalTitle"
        class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-black/60"
    >
        <div class="bg-white rounded-xl border border-[#D5E0D8] w-full max-w-md p-6 shadow-lg">
            <div class="flex items-center justify-between pb-3 border-b border-[#D5E0D8]">
                <h3 id="createUserModalTitle" class="text-base font-bold text-ink">Tambah Pengguna Baru</h3>
                <button
                    type="button"
                    onclick="closeCreateUserModal()"
                    class="min-h-[44px] min-w-[44px] inline-flex items-center justify-center rounded-lg text-ink-muted hover:bg-forest-50"
                    aria-label="Tutup dialog"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form method="POST" action="{{ route('admin.users.store') }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label for="create_name" class="block text-sm font-semibold text-ink mb-1">Nama Lengkap</label>
                    <input
                        id="create_name"
                        name="name"
                        type="text"
                        required
                        placeholder="Nama lengkap pengguna"
                        class="w-full px-3.5 min-h-[44px] rounded-lg border border-[#B8C9BD] bg-white text-sm text-ink"
                    >
                </div>

                <div>
                    <label for="create_email" class="block text-sm font-semibold text-ink mb-1">Alamat Email</label>
                    <input
                        id="create_email"
                        name="email"
                        type="email"
                        required
                        placeholder="email@example.com"
                        class="w-full px-3.5 min-h-[44px] rounded-lg border border-[#B8C9BD] bg-white text-sm text-ink"
                    >
                </div>

                <div>
                    <label for="create_role" class="block text-sm font-semibold text-ink mb-1">Role Akses</label>
                    <select
                        id="create_role"
                        name="role"
                        class="w-full px-3.5 min-h-[44px] rounded-lg border border-[#B8C9BD] bg-white text-sm font-semibold text-ink"
                    >
                        <option value="user">User (Pelapor)</option>
                        <option value="admin">Administrator (Admin)</option>
                    </select>
                </div>

                <div>
                    <label for="create_password" class="block text-sm font-semibold text-ink mb-1">Kata Sandi Awal</label>
                    <input
                        id="create_password"
                        name="password"
                        type="password"
                        required
                        minlength="6"
                        placeholder="Minimal 6 karakter"
                        class="w-full px-3.5 min-h-[44px] rounded-lg border border-[#B8C9BD] bg-white text-sm text-ink"
                    >
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button
                        type="button"
                        onclick="closeCreateUserModal()"
                        class="px-4 min-h-[44px] rounded-lg border border-[#B8C9BD] text-sm font-semibold text-ink hover:bg-forest-50"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        class="px-5 min-h-[44px] rounded-lg bg-forest-700 hover:bg-forest-800 text-white text-sm font-semibold transition"
                    >
                        Simpan Pengguna
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div
        id="editUserModal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="editUserModalTitle"
        class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-black/60"
    >
        <div class="bg-white rounded-xl border border-[#D5E0D8] w-full max-w-md p-6 shadow-lg">
            <div class="flex items-center justify-between pb-3 border-b border-[#D5E0D8]">
                <h3 id="editUserModalTitle" class="text-base font-bold text-ink">Edit Data & Role Pengguna</h3>
                <button
                    type="button"
                    onclick="closeEditUserModal()"
                    class="min-h-[44px] min-w-[44px] inline-flex items-center justify-center rounded-lg text-ink-muted hover:bg-forest-50"
                    aria-label="Tutup dialog"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form id="editUserForm" method="POST" action="" class="mt-4 space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label for="edit_name" class="block text-sm font-semibold text-ink mb-1">Nama Lengkap</label>
                    <input
                        id="edit_name"
                        name="name"
                        type="text"
                        required
                        class="w-full px-3.5 min-h-[44px] rounded-lg border border-[#B8C9BD] bg-white text-sm text-ink"
                    >
                </div>

                <div>
                    <label for="edit_email" class="block text-sm font-semibold text-ink mb-1">Alamat Email</label>
                    <input
                        id="edit_email"
                        name="email"
                        type="email"
                        required
                        class="w-full px-3.5 min-h-[44px] rounded-lg border border-[#B8C9BD] bg-white text-sm text-ink"
                    >
                </div>

                <div>
                    <label for="edit_role" class="block text-sm font-semibold text-ink mb-1">Role Akses</label>
                    <select
                        id="edit_role"
                        name="role"
                        class="w-full px-3.5 min-h-[44px] rounded-lg border border-[#B8C9BD] bg-white text-sm font-semibold text-ink"
                    >
                        <option value="user">User (Pelapor)</option>
                        <option value="admin">Administrator (Admin)</option>
                    </select>
                </div>

                <div>
                    <label for="edit_password" class="block text-sm font-semibold text-ink mb-1">
                        Kata Sandi Baru (Kosongkan jika tidak diubah)
                    </label>
                    <input
                        id="edit_password"
                        name="password"
                        type="password"
                        minlength="6"
                        placeholder="Biarkan kosong jika tetap menggunakan sandi lama"
                        class="w-full px-3.5 min-h-[44px] rounded-lg border border-[#B8C9BD] bg-white text-sm text-ink"
                    >
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button
                        type="button"
                        onclick="closeEditUserModal()"
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
@endsection

@push('scripts')
<script>
    const createUserModal = document.getElementById('createUserModal');
    const editUserModal = document.getElementById('editUserModal');
    const editUserForm = document.getElementById('editUserForm');

    function openCreateUserModal() {
        createUserModal.classList.remove('hidden');
        createUserModal.classList.add('flex');
        document.getElementById('create_name')?.focus();
    }

    function closeCreateUserModal() {
        createUserModal.classList.add('hidden');
        createUserModal.classList.remove('flex');
    }

    const baseAdminUsersUrl = @js(url('/admin/users'));

    function openEditUserModal(id, name, email, role) {
        editUserForm.action = `${baseAdminUsersUrl}/${id}`;
        document.getElementById('edit_name').value = name;
        document.getElementById('edit_email').value = email;
        document.getElementById('edit_role').value = role;
        document.getElementById('edit_password').value = '';
        editUserModal.classList.remove('hidden');
        editUserModal.classList.add('flex');
        document.getElementById('edit_name')?.focus();
    }

    function closeEditUserModal() {
        editUserModal.classList.add('hidden');
        editUserModal.classList.remove('flex');
    }

    createUserModal?.addEventListener('click', function (e) {
        if (e.target === createUserModal) closeCreateUserModal();
    });

    editUserModal?.addEventListener('click', function (e) {
        if (e.target === editUserModal) closeEditUserModal();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeCreateUserModal();
            closeEditUserModal();
        }
    });
</script>
@endpush
