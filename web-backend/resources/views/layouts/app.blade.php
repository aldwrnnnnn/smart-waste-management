<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Smart Waste Management System')</title>
    @if(file_exists(public_path('images/logo.png')))
        <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    @endif

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'system-ui', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'monospace'],
                    },
                    colors: {
                        forest: {
                            50: '#F4F7F4',
                            100: '#E3ECE5',
                            200: '#C5D9CA',
                            600: '#198746',
                            700: '#146C38',
                            800: '#0F532B',
                            900: '#0D261B',
                            950: '#071710',
                        },
                        ink: {
                            DEFAULT: '#111C16',
                            muted: '#43544A',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        html {
            scroll-behavior: smooth;
            scrollbar-width: thin;
            scrollbar-color: #B8C9BD transparent;
        }

        body {
            background-color: #F4F7F4;
            color: #111C16;
        }

        a:focus-visible, button:focus-visible, input:focus-visible, select:focus-visible, textarea:focus-visible {
            outline: 2px solid #146C38;
            outline-offset: 2px;
        }

        /* Modern Custom Scrollbar (WebKit / Chromium / Safari / Edge) */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background-color: #B8C9BD;
            border-radius: 9999px;
            border: 2px solid transparent;
            background-clip: padding-box;
            transition: background-color 0.2s ease;
        }

        ::-webkit-scrollbar-thumb:hover {
            background-color: #146C38;
        }

        ::-webkit-scrollbar-corner {
            background: transparent;
        }

        /* Dedicated Custom Scrollbar for Dark Sidebar Navigation */
        aside,
        aside * {
            scrollbar-width: thin;
            scrollbar-color: rgba(167, 196, 181, 0.25) transparent;
        }

        aside::-webkit-scrollbar,
        aside *::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        aside::-webkit-scrollbar-track,
        aside *::-webkit-scrollbar-track {
            background: transparent;
        }

        aside::-webkit-scrollbar-thumb,
        aside *::-webkit-scrollbar-thumb {
            background-color: rgba(167, 196, 181, 0.25);
            border-radius: 9999px;
            border: 1.5px solid transparent;
            background-clip: padding-box;
            transition: background-color 0.2s ease;
        }

        aside::-webkit-scrollbar-thumb:hover,
        aside *::-webkit-scrollbar-thumb:hover {
            background-color: rgba(167, 196, 181, 0.55);
        }
    </style>
    @stack('head')
</head>
<body class="min-h-full font-sans antialiased selection:bg-forest-700 selection:text-white">
    @auth
        <div class="min-h-screen flex flex-col lg:flex-row">
            <!-- Header Mobile & Tablet -->
            <header class="lg:hidden sticky top-0 z-30 bg-forest-900 text-white border-b border-white/10 px-4 py-3 flex items-center justify-between">
                <a href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('user.dashboard') }}" class="flex items-center gap-2.5 min-h-[44px]">
                    <span class="w-9 h-9 rounded-lg bg-forest-700 flex items-center justify-center font-bold text-white border border-white/20">
                        SW
                    </span>
                    <div>
                        <span class="font-bold text-base tracking-tight block leading-none">SmartWaste</span>
                        <span class="text-xs text-[#A7C4B5] block mt-0.5">
                            {{ auth()->user()->isAdmin() ? 'Panel Kontrol Admin' : 'Portal Pelapor Warga' }}
                        </span>
                    </div>
                </a>

                <button
                    type="button"
                    id="mobileMenuBtn"
                    aria-expanded="false"
                    aria-controls="mobileDrawer"
                    class="inline-flex items-center gap-2 px-3.5 min-h-[44px] rounded-lg bg-white/10 hover:bg-white/15 text-white text-sm font-medium border border-white/20 transition"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                    <span>Menu</span>
                </button>
            </header>

            <!-- Backdrop Mobile Drawer -->
            <div id="mobileBackdrop" class="fixed inset-0 z-40 bg-black/60 hidden lg:hidden" aria-hidden="true"></div>

            <!-- Sidebar Navigation -->
            <aside
                id="mobileDrawer"
                aria-label="Navigasi Utama"
                class="fixed inset-y-0 left-0 z-50 w-72 bg-forest-900 text-[#F4F7F4] flex flex-col justify-between transform -translate-x-full lg:translate-x-0 lg:sticky lg:top-0 lg:h-screen lg:w-64 lg:shrink-0 transition-transform duration-200 ease-in-out"
            >
                <div class="flex-1 flex flex-col min-h-0 overflow-y-auto">
                    <!-- Brand Header -->
                    <div class="p-5 border-b border-white/10 flex items-center justify-between shrink-0">
                        <a href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('user.dashboard') }}" class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-forest-700 flex items-center justify-center font-bold text-white text-lg border border-white/20 overflow-hidden shrink-0">
                                @if(file_exists(public_path('images/logo.png')))
                                    <img src="{{ asset('images/logo.png') }}" alt="Logo" class="w-full h-full object-cover">
                                @else
                                    SW
                                @endif
                            </div>
                            <div>
                                <div class="font-bold text-base tracking-tight text-white">SmartWaste</div>
                                <div class="text-xs text-[#A7C4B5]">AI Waste Management</div>
                            </div>
                        </a>
                        <button
                            type="button"
                            id="closeMobileMenuBtn"
                            class="lg:hidden min-h-[44px] min-w-[44px] inline-flex items-center justify-center rounded-lg text-[#A7C4B5] hover:text-white hover:bg-white/10"
                            aria-label="Tutup Menu"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Navigation Links -->
                    <nav class="p-3 space-y-1.5 flex-1">
                        @if(auth()->user()->isAdmin())
                            <a
                                href="{{ route('admin.dashboard') }}"
                                class="flex items-center gap-3 px-3.5 min-h-[44px] rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.dashboard') ? 'bg-forest-700 text-white font-semibold' : 'text-[#D5E0D8] hover:bg-white/10 hover:text-white' }}"
                            >
                                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/>
                                </svg>
                                <span>Dashboard Admin</span>
                            </a>
                        @else
                            <a
                                href="{{ route('user.dashboard') }}"
                                class="flex items-center gap-3 px-3.5 min-h-[44px] rounded-lg text-sm font-medium transition {{ request()->routeIs('user.dashboard') ? 'bg-forest-700 text-white font-semibold' : 'text-[#D5E0D8] hover:bg-white/10 hover:text-white' }}"
                            >
                                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/>
                                </svg>
                                <span>Dashboard Saya</span>
                            </a>
                        @endif

                        <a
                            href="{{ route('reports.index') }}"
                            class="flex items-center gap-3 px-3.5 min-h-[44px] rounded-lg text-sm font-medium transition {{ request()->routeIs('reports.index', 'reports.show', 'reports.edit') ? 'bg-forest-700 text-white font-semibold' : 'text-[#D5E0D8] hover:bg-white/10 hover:text-white' }}"
                        >
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <span>{{ auth()->user()->isAdmin() ? 'Semua Laporan Sampah' : 'Riwayat Laporan Saya' }}</span>
                        </a>

                        <a
                            href="{{ route('reports.create') }}"
                            class="flex items-center gap-3 px-3.5 min-h-[44px] rounded-lg text-sm font-medium transition {{ request()->routeIs('reports.create') ? 'bg-forest-700 text-white font-semibold' : 'text-[#D5E0D8] hover:bg-white/10 hover:text-white' }}"
                        >
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                            </svg>
                            <span>Buat Laporan Baru</span>
                        </a>

                        @if(auth()->user()->isAdmin())
                            <div class="pt-3 mt-3 border-t border-white/10">
                                <div class="px-3.5 pb-1.5 text-xs font-semibold uppercase tracking-wider text-[#A7C4B5]">
                                    Administrasi
                                </div>
                                <a
                                    href="{{ route('admin.users.index') }}"
                                    class="flex items-center gap-3 px-3.5 min-h-[44px] rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.users.*') ? 'bg-forest-700 text-white font-semibold' : 'text-[#D5E0D8] hover:bg-white/10 hover:text-white' }}"
                                >
                                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    <span>Manajemen Pengguna</span>
                                </a>
                            </div>
                        @endif
                    </nav>
                </div>

                <!-- Footer User Profile & Logout -->
                <div class="p-4 border-t border-white/10 bg-forest-950/60 shrink-0">
                    <div class="mb-3">
                        <div class="text-xs uppercase tracking-wider text-[#A7C4B5] font-semibold">Sesi Aktif</div>
                        <div class="text-sm font-semibold text-white truncate mt-0.5">{{ auth()->user()->name }}</div>
                        <div class="text-xs text-[#A7C4B5] truncate mt-0.5">{{ auth()->user()->email }}</div>
                        <div class="mt-1.5 inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[11px] font-medium {{ auth()->user()->isAdmin() ? 'bg-emerald-800/80 text-emerald-100 border border-emerald-500/40' : 'bg-slate-800 text-slate-200 border border-slate-600' }}">
                            <span>Role: {{ auth()->user()->isAdmin() ? 'Administrator' : 'User Pelapor' }}</span>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button
                            type="submit"
                            class="w-full flex items-center justify-center gap-2 px-4 min-h-[44px] rounded-lg bg-red-900/60 hover:bg-red-800 text-white text-sm font-semibold border border-red-400/30 transition"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                            <span>Keluar Sesi</span>
                        </button>
                    </form>
                </div>
            </aside>

            <!-- Main Content Area -->
            <div class="flex-1 flex flex-col min-w-0">
                <!-- Desktop Top Bar -->
                <header class="hidden lg:flex items-center justify-between px-8 py-4 bg-white border-b border-[#D5E0D8]">
                    <div>
                        <h1 class="text-xl font-bold text-ink">@yield('page_title', 'Dashboard')</h1>
                        @hasSection('page_subtitle')
                            <p class="text-sm text-ink-muted mt-0.5">@yield('page_subtitle')</p>
                        @endif
                    </div>
                    <div class="flex items-center gap-3">
                        @yield('header_actions')
                    </div>
                </header>

                <!-- Mobile Page Title Banner -->
                <div class="lg:hidden bg-white border-b border-[#D5E0D8] px-4 py-3.5 flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h1 class="text-lg font-bold text-ink">@yield('page_title', 'Dashboard')</h1>
                        @hasSection('page_subtitle')
                            <p class="text-xs text-ink-muted mt-0.5">@yield('page_subtitle')</p>
                        @endif
                    </div>
                    <div>
                        @yield('header_actions')
                    </div>
                </div>

                <!-- Flash Alerts -->
                <main class="flex-1 px-4 sm:px-6 lg:px-8 py-6 max-w-7xl w-full mx-auto">
                    @if(session('error'))
                        <div role="alert" class="mb-6 rounded-xl bg-[#FEE2E2] border border-[#EF4444] px-4 py-3.5 text-[#7F1D1D] flex items-start justify-between gap-3">
                            <div class="flex items-start gap-2.5">
                                <svg class="w-5 h-5 shrink-0 mt-0.5 text-[#7F1D1D]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span class="text-sm font-medium">{{ session('error') }}</span>
                            </div>
                        </div>
                    @endif

                    @if($errors->any())
                        <div role="alert" class="mb-6 rounded-xl bg-[#FEE2E2] border border-[#EF4444] px-4 py-3.5 text-[#7F1D1D]">
                            <div class="font-semibold text-sm mb-1">Periksa kembali isian formulir Anda:</div>
                            <ul class="list-disc list-inside text-sm space-y-0.5">
                                @foreach($errors->all() as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @yield('content')
                </main>

                <footer class="px-4 sm:px-6 lg:px-8 py-4 border-t border-[#D5E0D8] bg-white text-xs text-ink-muted flex flex-col sm:flex-row items-center justify-between gap-2">
                    <span>Smart Waste Management System - Copyright from I Gede Aldi Irawan.</span>
                    <span>Database: <code class="font-mono text-ink">smart_waste_db</code></span>
                </footer>
            </div>
        </div>

        <script>
            (function () {
                const menuBtn = document.getElementById('mobileMenuBtn');
                const closeBtn = document.getElementById('closeMobileMenuBtn');
                const drawer = document.getElementById('mobileDrawer');
                const backdrop = document.getElementById('mobileBackdrop');

                function openDrawer() {
                    if (!drawer) return;
                    drawer.classList.remove('-translate-x-full');
                    backdrop.classList.remove('hidden');
                    menuBtn?.setAttribute('aria-expanded', 'true');
                }

                function closeDrawer() {
                    if (!drawer) return;
                    drawer.classList.add('-translate-x-full');
                    backdrop.classList.add('hidden');
                    menuBtn?.setAttribute('aria-expanded', 'false');
                }

                menuBtn?.addEventListener('click', openDrawer);
                closeBtn?.addEventListener('click', closeDrawer);
                backdrop?.addEventListener('click', closeDrawer);

                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape') {
                        closeDrawer();
                    }
                });
            })();
        </script>
    @else
        @yield('content')
    @endauth

    <!-- Global System Confirmation Modal -->
    <div
        id="globalConfirmModal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="globalConfirmTitle"
        aria-describedby="globalConfirmMessage"
        class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-black/60 transition-opacity duration-150"
    >
        <div id="globalConfirmDialogCard" class="bg-white rounded-xl border border-[#D5E0D8] w-full max-w-md p-6 shadow-2xl relative transform transition-all duration-150 scale-95 opacity-0">
            <div class="flex items-start gap-4">
                <div id="globalConfirmIconContainer" class="w-12 h-12 rounded-full bg-[#FEE2E2] border border-[#FCA5A5] text-[#7F1D1D] flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <h3 id="globalConfirmTitle" class="text-base font-bold text-ink">
                        Konfirmasi Tindakan
                    </h3>
                    <p id="globalConfirmMessage" class="text-sm text-ink-muted mt-1.5 leading-relaxed">
                        Apakah Anda yakin ingin melanjutkan tindakan ini?
                    </p>
                    <p class="text-xs text-ink-muted/80 mt-1">
                        Tindakan ini tidak dapat dibatalkan.
                    </p>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end gap-2.5 pt-4 border-t border-[#D5E0D8]">
                <button
                    type="button"
                    id="globalConfirmCancelBtn"
                    class="px-4 min-h-[44px] rounded-lg border border-[#B8C9BD] text-ink font-semibold text-sm hover:bg-forest-50 transition focus:outline-none focus:ring-2 focus:ring-forest-600 focus:ring-offset-1"
                >
                    Batal
                </button>
                <button
                    type="button"
                    id="globalConfirmSubmitBtn"
                    class="px-5 min-h-[44px] rounded-lg bg-red-600 hover:bg-red-700 text-white font-semibold text-sm transition shadow-xs focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-1"
                >
                    Ya, Hapus
                </button>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const modal = document.getElementById('globalConfirmModal');
            const card = document.getElementById('globalConfirmDialogCard');
            const titleEl = document.getElementById('globalConfirmTitle');
            const messageEl = document.getElementById('globalConfirmMessage');
            const cancelBtn = document.getElementById('globalConfirmCancelBtn');
            const submitBtn = document.getElementById('globalConfirmSubmitBtn');
            const iconContainer = document.getElementById('globalConfirmIconContainer');

            let currentConfirmCallback = null;

            window.showConfirmDialog = function (options) {
                const {
                    title = 'Konfirmasi Tindakan',
                    message = 'Apakah Anda yakin ingin melanjutkan tindakan ini?',
                    confirmText = 'Ya, Hapus',
                    cancelText = 'Batal',
                    variant = 'danger',
                    onConfirm = () => {}
                } = options || {};

                titleEl.textContent = title;
                messageEl.textContent = message;
                submitBtn.textContent = confirmText;
                cancelBtn.textContent = cancelText;

                if (variant === 'danger') {
                    iconContainer.className = 'w-12 h-12 rounded-full bg-[#FEE2E2] border border-[#FCA5A5] text-[#7F1D1D] flex items-center justify-center shrink-0';
                    submitBtn.className = 'px-5 min-h-[44px] rounded-lg bg-red-600 hover:bg-red-700 text-white font-semibold text-sm transition shadow-xs focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-1';
                } else {
                    iconContainer.className = 'w-12 h-12 rounded-full bg-forest-100 border border-forest-200 text-forest-700 flex items-center justify-center shrink-0';
                    submitBtn.className = 'px-5 min-h-[44px] rounded-lg bg-forest-700 hover:bg-forest-800 text-white font-semibold text-sm transition shadow-xs focus:outline-none focus:ring-2 focus:ring-forest-600 focus:ring-offset-1';
                }

                currentConfirmCallback = onConfirm;

                modal.classList.remove('hidden');
                modal.classList.add('flex');

                requestAnimationFrame(() => {
                    card.classList.remove('scale-95', 'opacity-0');
                    card.classList.add('scale-100', 'opacity-100');
                });

                // Default focus to cancel for safety
                cancelBtn.focus();
            };

            function closeConfirmModal() {
                card.classList.remove('scale-100', 'opacity-100');
                card.classList.add('scale-95', 'opacity-0');
                setTimeout(() => {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                    currentConfirmCallback = null;
                }, 150);
            }

            cancelBtn?.addEventListener('click', closeConfirmModal);

            submitBtn?.addEventListener('click', function () {
                if (typeof currentConfirmCallback === 'function') {
                    const cb = currentConfirmCallback;
                    currentConfirmCallback = null;
                    cb();
                }
                closeConfirmModal();
            });

            modal?.addEventListener('click', function (e) {
                if (e.target === modal) {
                    closeConfirmModal();
                }
            });

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && modal && !modal.classList.contains('hidden')) {
                    closeConfirmModal();
                }
            });

            // Global interception for forms with data-confirm
            document.addEventListener('submit', function (e) {
                const form = e.target;
                if (!form || !form.hasAttribute('data-confirm')) return;

                e.preventDefault();
                const message = form.getAttribute('data-confirm');
                const title = form.getAttribute('data-confirm-title') || 'Konfirmasi Hapus Data';
                const confirmText = form.getAttribute('data-confirm-btn') || 'Ya, Hapus';

                window.showConfirmDialog({
                    title: title,
                    message: message,
                    confirmText: confirmText,
                    variant: 'danger',
                    onConfirm: function () {
                        form.removeAttribute('data-confirm');
                        form.submit();
                    }
                });
            });
        })();
    </script>

    <!-- CDN SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Script Flash Message SweetAlert2 -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            @if(session('success'))
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: {!! json_encode(session('success')) !!},
                    confirmButtonColor: '#1a3a5c',
                    confirmButtonText: 'Tutup',
                    timer: 4000,
                    timerProgressBar: true
                });
            @endif

            @if(session('error'))
                Swal.fire({
                    icon: 'error',
                    title: 'Terjadi Kesalahan',
                    text: {!! json_encode(session('error')) !!},
                    confirmButtonColor: '#cc0000',
                    confirmButtonText: 'Tutup'
                });
            @endif

            @if(session('warning'))
                Swal.fire({
                    icon: 'warning',
                    title: 'Perhatian',
                    text: {!! json_encode(session('warning')) !!},
                    confirmButtonColor: '#1a3a5c',
                    confirmButtonText: 'Mengerti'
                });
            @endif

            @if(session('info'))
                Swal.fire({
                    icon: 'info',
                    title: 'Informasi',
                    text: {!! json_encode(session('info')) !!},
                    confirmButtonColor: '#1a3a5c',
                    confirmButtonText: 'OK'
                });
            @endif
        });
    </script>

    @stack('scripts')
</body>
</html>
