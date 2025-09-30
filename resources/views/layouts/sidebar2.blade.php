<div class="w-64 min-h-screen bg-white hidden md:block">
    <nav class="mt-10">
        <div x-data="{ open: false }">

            <x-sidebar-item :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                <x-slot name="icon">🏠</x-slot>
                {{ __('Dashboard') }}
            </x-sidebar-item>
            <x-sidebar-item :href="route('packages.index')" :active="request()->routeIs('packages.index')">
                <x-slot name="icon">📦</x-slot>
                {{ __('Packages') }}
            </x-sidebar-item>

            @if(auth()->user()->isAdmin())
                <x-sidebar-item :href="route('users.index')" :active="request()->routeIs('users.index')">
                    <x-slot name="icon">👥</x-slot>
                    {{ __('Users') }}
                </x-sidebar-item>
                <x-sidebar-item :href="route('company.edit')" :active="request()->routeIs('company.edit')">
                    <x-slot name="icon">🏢</x-slot>
                    {{ __('ISP') }}
                </x-sidebar-item>
                <x-sidebar-item :href="route('router.index')" :active="request()->routeIs('router.index')">
                    <x-slot name="icon">🖧</x-slot>
                    {{ __('Router') }}
                </x-sidebar-item>
                <x-sidebar-item :href="route('vouchers.index')" :active="request()->routeIs('vouchers.index')">
                    <x-slot name="icon">🎟️</x-slot>
                    {{ __('Vouchers') }}
                </x-sidebar-item>
            @endif

            <x-sidebar-item :href="route('billing.index')" :active="request()->routeIs('billing.index')">
                <x-slot name="icon">🧾</x-slot>
                {{ __('Billing') }}
            </x-sidebar-item>
            <x-sidebar-item :href="route('payment.index')" :active="request()->routeIs('payment.index')">
                <x-slot name="icon">💳</x-slot>
                {{ __('Payment') }}
            </x-sidebar-item>
            <x-sidebar-item :href="route('ticket.index')" :active="request()->routeIs('ticket.index')">
                <x-slot name="icon">🎫</x-slot>
                {{ __('Ticket') }}
            </x-sidebar-item>
        </div>
    </nav>
</div>
