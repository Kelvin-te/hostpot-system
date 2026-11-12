<aside class="fixed inset-y-0 left-0 z-40 w-64 bg-slate-900 flex flex-col">

    {{-- Brand --}}
    <div class="flex h-16 items-center gap-3 border-b border-white/10 px-5">
        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-600">
            <x-icon name="globe" class="w-5 h-5 text-white" />
        </div>
        <span class="text-lg font-semibold text-white">{{ config('app.name') }}</span>
    </div>

    {{-- Nav --}}
    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
        <x-sidebar-item :href="route('dashboard')" icon="home" :active="request()->routeIs('dashboard')">
            {{ __('Dashboard') }}
        </x-sidebar-item>

        <div class="px-3 pt-4 pb-1 text-[11px] font-semibold uppercase tracking-wider text-slate-500">{{ __('Main') }}</div>
        <x-sidebar-item :href="route('packages.index')" icon="package" :active="request()->routeIs('packages.*')">
            {{ __('Packages') }}
        </x-sidebar-item>
        <x-sidebar-item :href="route('sessions.index')" icon="globe" :active="request()->routeIs('sessions.*')">
            {{ __('Sessions') }}
        </x-sidebar-item>
        <x-sidebar-item :href="route('incidents.index')" icon="alert" :active="request()->routeIs('incidents.*')">
            {{ __('Incidents') }}
        </x-sidebar-item>
        <x-sidebar-item :href="route('reports.index')" icon="chart" :active="request()->routeIs('reports.*')">
            {{ __('Reports') }}
        </x-sidebar-item>

        @if(!auth()->user()->isAdmin())
            <div class="px-3 pt-4 pb-1 text-[11px] font-semibold uppercase tracking-wider text-slate-500">{{ __('My Account') }}</div>
            <x-sidebar-item :href="route('user.dashboard')" icon="home" :active="request()->routeIs('user.dashboard')">
                {{ __('My Dashboard') }}
            </x-sidebar-item>
            <x-sidebar-item :href="route('user.sessions')" icon="clock" :active="request()->routeIs('user.sessions')">
                {{ __('My Sessions') }}
            </x-sidebar-item>
            <x-sidebar-item :href="route('user.purchases')" icon="receipt" :active="request()->routeIs('user.purchases')">
                {{ __('My Purchases') }}
            </x-sidebar-item>
            <x-sidebar-item :href="route('user.recharge')" icon="card" :active="request()->routeIs('user.recharge')">
                {{ __('Buy Data') }}
            </x-sidebar-item>
            <x-sidebar-item :href="route('user.wallet')" icon="wallet" :active="request()->routeIs('user.wallet')">
                {{ __('My Wallet') }}
            </x-sidebar-item>
            <x-sidebar-item :href="route('user.settings')" icon="settings" :active="request()->routeIs('user.settings')">
                {{ __('Account Settings') }}
            </x-sidebar-item>
        @endif

        @if(auth()->user()->isAdmin())
            <div class="px-3 pt-4 pb-1 text-[11px] font-semibold uppercase tracking-wider text-slate-500">{{ __('Management') }}</div>
            <x-sidebar-item :href="route('customers.index')" icon="users" :active="request()->routeIs('customers.*')">
                {{ __('Customers') }}
            </x-sidebar-item>
            <x-sidebar-item :href="route('router.index')" icon="router" :active="request()->routeIs('router.*')">
                {{ __('Routers') }}
            </x-sidebar-item>
            <x-sidebar-item :href="route('static-ips.index')" icon="ip" :active="request()->routeIs('static-ips.*')">
                {{ __('Static IPs') }}
            </x-sidebar-item>
            <x-sidebar-item :href="route('vouchers.index')" icon="ticket" :active="request()->routeIs('vouchers.*')">
                {{ __('Vouchers') }}
            </x-sidebar-item>
            <x-sidebar-item :href="route('resellers.index')" icon="store" :active="request()->routeIs('resellers.*')">
                {{ __('Resellers') }}
            </x-sidebar-item>
            <x-sidebar-item :href="route('staff.index')" icon="shield" :active="request()->routeIs('staff.*')">
                {{ __('Staff') }}
            </x-sidebar-item>
        @endif

        <div class="px-3 pt-4 pb-1 text-[11px] font-semibold uppercase tracking-wider text-slate-500">{{ __('Financial') }}</div>
        <x-sidebar-item :href="route('billing.index')" icon="receipt" :active="request()->routeIs('billing.*')">
            {{ __('Billing') }}
        </x-sidebar-item>
        <x-sidebar-item :href="route('payment.index')" icon="card" :active="request()->routeIs('payment.*')">
            {{ __('Payment') }}
        </x-sidebar-item>

        <div class="px-3 pt-4 pb-1 text-[11px] font-semibold uppercase tracking-wider text-slate-500">{{ __('Support') }}</div>
        <x-sidebar-item :href="route('ticket.index')" icon="ticket" :active="request()->routeIs('ticket.*')">
            {{ __('Tickets') }}
        </x-sidebar-item>

        @if(auth()->user()->isAdmin())
            <div class="px-3 pt-4 pb-1 text-[11px] font-semibold uppercase tracking-wider text-slate-500">{{ __('System') }}</div>
            <x-sidebar-item :href="route('settings.edit')" icon="settings" :active="request()->routeIs('settings.*')">
                {{ __('Settings') }}
            </x-sidebar-item>
        @endif
    </nav>

    {{-- User footer --}}
    <div class="border-t border-white/10 p-4">
        <div class="flex items-center gap-3">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-indigo-500/20 text-sm font-semibold text-indigo-300">
                {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
            </div>
            <div class="min-w-0 flex-1">
                <div class="truncate text-sm font-medium text-white">{{ auth()->user()->name }}</div>
                <div class="truncate text-xs text-slate-400">{{ auth()->user()->isAdmin() ? __('Administrator') : __('Staff') }}</div>
            </div>
        </div>
    </div>
</aside>
