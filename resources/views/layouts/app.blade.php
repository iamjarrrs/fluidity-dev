<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Fluidity - Smart Study Guidance')</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo.svg') }}">

    <!-- Tailwind CSS (Vite) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="h-full flex flex-col font-sans text-slate-800 antialiased">

    <!-- Top Navigation Bar -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-30">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-3 flex flex-wrap items-center gap-x-6 gap-y-3">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2" aria-label="Fluidity, ke beranda">
                @include('partials.logo', ['size' => 30])
                <span class="text-lg font-bold tracking-tight text-slate-900">Fluidity</span>
            </a>

            @auth
            @php
                $navLinks = [
                    ['route' => 'dashboard', 'label' => 'Beranda'],
                    ['route' => 'schedule.preview', 'label' => 'Jadwal'],
                    ['route' => 'courses.create', 'label' => 'Tambah mata kuliah'],
                ];
                $authUser = Auth::user();
                $initials = strtoupper(implode('', array_map(fn ($w) => mb_substr($w, 0, 1), array_slice(preg_split('/\s+/', trim($authUser->name)), 0, 2))));
            @endphp
            {{-- Slide tabs: penanda ikut hover/fokus, lalu kembali ke halaman aktif saat kursor keluar --}}
            <nav class="order-3 w-full sm:order-none sm:w-auto overflow-x-auto text-sm font-medium" aria-label="Navigasi utama"
                x-data="{
                    pos: { left: 0, width: 0, opacity: 0 },
                    on: null,
                    ready: false,
                    move(el) { const li = el?.closest('li'); this.on = li; this.pos = li ? { left: li.offsetLeft, width: li.offsetWidth, opacity: 1 } : { ...this.pos, opacity: 0 } },
                    active() { return this.$refs.list.querySelector('[aria-current=page]') },
                }"
                x-init="move(active()); $nextTick(() => ready = true)">
                <ul x-ref="list" class="relative flex w-fit rounded-full border border-slate-200 bg-white p-1" @mouseleave="move(active())" @focusout="move(active())">
                    <li aria-hidden="true" class="absolute inset-y-1 left-0 rounded-full bg-primary-600"
                        :class="ready && 'transition-[transform,width,opacity] duration-300 ease-[cubic-bezier(0.22,1,0.36,1)]'"
                        :style="`transform: translateX(${pos.left}px); width: ${pos.width}px; opacity: ${pos.opacity}`"></li>
                    @foreach($navLinks as $link)
                        @php $isActive = request()->routeIs($link['route']); @endphp
                        <li class="relative z-10">
                            <a href="{{ route($link['route']) }}" @mouseenter="move($el)" @focus="move($el)"
                               {{-- Teks putih mengikuti posisi penanda teal (kontras 5,5:1), bukan status aktif --}}
                               :class="on === $el.parentElement ? 'text-white' : 'text-slate-600 hover:text-slate-900'"
                               class="block whitespace-nowrap rounded-full px-4 py-1.5 transition-colors duration-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2"
                               @if($isActive) aria-current="page" @endif>{{ $link['label'] }}</a>
                        </li>
                    @endforeach
                </ul>
            </nav>

            <div class="ml-auto flex items-center gap-3">
                @if(! empty($authUser->google_access_token) || ! empty($authUser->google_refresh_token))
                    <span class="hidden md:inline-flex items-center gap-1.5 rounded-full border border-slate-200 px-3 py-1 text-xs text-slate-600">
                        <span class="h-2 w-2 rounded-full bg-emerald-600" aria-hidden="true"></span>
                        Google Calendar terhubung
                    </span>
                @else
                    <a href="{{ route('auth.google') }}" class="hidden md:inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs text-amber-800 hover:bg-amber-100">
                        <span class="h-2 w-2 rounded-full bg-amber-500" aria-hidden="true"></span>
                        Hubungkan Google Calendar
                    </a>
                @endif
                <span class="grid h-9 w-9 place-items-center rounded-full bg-amber-100 text-amber-800 text-sm font-semibold" title="{{ $authUser->name }} ({{ $authUser->email }})">{{ $initials }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-xs font-medium text-slate-600 hover:text-red-700 border border-slate-200 hover:border-red-300 rounded-lg px-3 py-1.5 transition">
                        Keluar
                    </button>
                </form>
            </div>
            @endauth
        </div>
    </header>

    <!-- Flash Messages -->
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 mt-4 w-full">
        @if(session('success'))
            <div class="p-4 mb-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm">
                {{ session('success') }}
            </div>
        @endif
        @if(session('info'))
            <div class="p-4 mb-4 rounded-xl bg-primary-50 border border-primary-200 text-primary-800 text-sm">
                {{ session('info') }}
            </div>
        @endif
        @if(isset($errors) && $errors->any())
            <div class="p-4 mb-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    <!-- Main Page Content -->
    <main class="flex-1 max-w-6xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 py-4 text-center text-xs text-slate-500">
        &copy; 2026 Tim WADW • GDGoC ITB STACK 2026 (Case 1: Smarter Campus and Learning)
    </footer>

</body>
</html>
