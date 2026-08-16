<div class="w-56 min-h-screen bg-white hidden md:block left-0 top-16 bottom-0 overflow-y-auto border-r border-gray-200">
    <nav class="py-6">
        <div x-data="{ open: false }">
            <div class="flex items-center justify-center mb-6 pt-2 pb-6">
                <x-application-logo class="w-full max-w-[160px] h-auto" />
            </div>

            <!-- Main -->
            <x-sidebar-item :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                <x-slot name="icon">🏠</x-slot>
                {{ __('Dashboard') }}
            </x-sidebar-item>
            <x-sidebar-item :href="route('packages.index')" :active="request()->routeIs('packages.index')">
                <x-slot name="icon">📦</x-slot>
                {{ __('Packages') }}
            </x-sidebar-item>
            <x-sidebar-item :href="route('sessions.index')" :active="request()->routeIs('sessions.*')">
                <x-slot name="icon">🌐</x-slot>
                {{ __('Sessions') }}
            </x-sidebar-item>
            <x-sidebar-item :href="route('reports.index')" :active="request()->routeIs('reports.*')">
                <x-slot name="icon">📊</x-slot>
                {{ __('Reports') }}
            </x-sidebar-item>

            <!-- User Portal Section -->
            @if(!auth()->user()->isAdmin())
                <div class="px-4 py-2 mt-4">
                    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">My Account</div>
                </div>
                <x-sidebar-item :href="route('user.dashboard')" :active="request()->routeIs('user.dashboard')">
                    <x-slot name="icon">🏠</x-slot>
                    {{ __('My Dashboard') }}
                </x-sidebar-item>
                <x-sidebar-item :href="route('user.sessions')" :active="request()->routeIs('user.sessions')">
                    <x-slot name="icon">🌐</x-slot>
                    {{ __('My Sessions') }}
                </x-sidebar-item>
                <x-sidebar-item :href="route('user.purchases')" :active="request()->routeIs('user.purchases')">
                    <x-slot name="icon">📜</x-slot>
                    {{ __('My Purchases') }}
                </x-sidebar-item>
                <x-sidebar-item :href="route('user.recharge')" :active="request()->routeIs('user.recharge')">
                    <x-slot name="icon">💳</x-slot>
                    {{ __('Buy Data') }}
                </x-sidebar-item>
                <x-sidebar-item :href="route('user.settings')" :active="request()->routeIs('user.settings')">
                    <x-slot name="icon">⚙️</x-slot>
                    {{ __('Account Settings') }}
                </x-sidebar-item>
            @endif

            <!-- Admin Section -->
            @if(auth()->user()->isAdmin())
                <div class="px-4 py-2 mt-4">
                    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Management</div>
                </div>
                <x-sidebar-item :href="route('users.index')" :active="request()->routeIs('users.index')">
                    <x-slot name="icon">👥</x-slot>
                    {{ __('Users') }}
                </x-sidebar-item>
                <x-sidebar-item :href="route('router.index')" :active="request()->routeIs('router.index')">
                    <x-slot name="icon">🖧</x-slot>
                    {{ __('Routers') }}
                </x-sidebar-item>
                <x-sidebar-item :href="route('vouchers.index')" :active="request()->routeIs('vouchers.index')">
                    <x-slot name="icon">🎟️</x-slot>
                    {{ __('Vouchers') }}
                </x-sidebar-item>
            @endif

            <!-- Financial -->
            <div class="px-4 py-2 mt-4">
                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Financial</div>
            </div>
            <x-sidebar-item :href="route('billing.index')" :active="request()->routeIs('billing.index')">
                <x-slot name="icon">🧾</x-slot>
                {{ __('Billing') }}
            </x-sidebar-item>
            <x-sidebar-item :href="route('payment.index')" :active="request()->routeIs('payment.index')">
                <x-slot name="icon">💳</x-slot>
                {{ __('Payment') }}
            </x-sidebar-item>

            <!-- Support -->
            <div class="px-4 py-2 mt-4">
                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Support</div>
            </div>
            <x-sidebar-item :href="route('ticket.index')" :active="request()->routeIs('ticket.index')">
                <x-slot name="icon">🎫</x-slot>
                {{ __('Ticket') }}
            </x-sidebar-item>

            <!-- System -->
            @if(auth()->user()->isAdmin())
                <div class="px-4 py-2 mt-4">
                    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">System</div>
                </div>
                <x-sidebar-item :href="route('settings.edit')" :active="request()->routeIs('settings.edit')">
                    <x-slot name="icon">⚙️</x-slot>
                    {{ __('Settings') }}
                </x-sidebar-item>
            @endif
        </div>
    </nav>
</div>
