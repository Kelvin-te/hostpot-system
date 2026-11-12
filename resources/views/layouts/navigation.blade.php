<nav class="sticky top-0 z-30 flex h-16 items-center gap-4 border-b border-slate-200 bg-white px-4 sm:px-6 lg:px-8">
    {{-- Mobile sidebar toggle --}}
    <button @click="sidebarOpen = true" class="rounded-md p-2 text-slate-500 hover:bg-slate-100 lg:hidden">
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
    </button>

    <div class="flex-1">
        @isset($header)
            <h1 class="text-lg font-semibold text-slate-900">{{ $headerText ?? '' }}</h1>
        @endisset
    </div>

    {{-- User menu --}}
    <div class="relative" x-data="{ open: false }">
        <button @click="open = !open" @click.away="open = false" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">
            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-100 text-sm font-semibold text-indigo-700">
                {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
            </span>
            <span class="hidden sm:inline">{{ auth()->user()->name }}</span>
            <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
        </button>

        <div x-show="open" x-cloak x-transition.origin.top.right class="absolute right-0 mt-2 w-48 rounded-lg border border-slate-200 bg-white py-1 shadow-lg">
            <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">{{ __('Profile') }}</a>
            <form method="POST" action="{{ Auth::guard('staff')->check() ? route('staff.logout') : route('logout') }}">
                @csrf
                <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50">{{ __('Log Out') }}</button>
            </form>
        </div>
    </div>
</nav>
