<div>
    <x-input-label for="router_name" :value="__('Select router')" class="mt-4"></x-input-label>
    <select wire:model="routerId" name="router_name" id="router_name" class="mt-1 block w-full rounded-md border border-gray-300">
        <option value="">{{ __('Select router') }}</option>
        @foreach ($routers as $router)
            <option value="{{ $router->id }}">{{ $router->name }}</option>
        @endforeach
    </select>
    <x-input-label for="package_name" :value="__('Select package')" class="mt-4"></x-input-label>
    <select name="package_name" id="package_name" class="mt-1 block w-full rounded-md border border-gray-300">
        <option value="">{{ __('Select package') }}</option>
        @foreach ($packages as $package)
            <option value="{{ $package->id }}">{{ $package->name }}</option>
        @endforeach
    </select>
</div>
