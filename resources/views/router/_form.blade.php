@php
$isEdit = isset($router) && $router;
@endphp

<div>
    <x-input-label for="location" :value="__('Location')" class="mt-4"></x-input-label>
    <x-text-input id="location" name="location" type="text" class="mt-1 block w-full" :value="old('location', $isEdit ? $router->location : '')"></x-text-input>
    <x-input-error class="mt-2" :messages="$errors->get('location')"></x-input-error>
</div>
<div>
    <x-input-label for="ip" :value="__('Router IP')" class="mt-4"></x-input-label>
    <x-text-input id="ip" name="ip" type="text" class="mt-1 block w-full" :value="old('ip', $isEdit ? $router->ip : '')" required></x-text-input>
    <x-input-error class="mt-2" :messages="$errors->get('ip')"></x-input-error>
</div>
<div>
    <x-input-label for="username" :value="__('Router username')" class="mt-4"></x-input-label>
    <x-text-input id="username" name="username" type="text" class="mt-1 block w-full" :value="old('username', $isEdit ? $router->username : '')" required></x-text-input>
    <x-input-error class="mt-2" :messages="$errors->get('username')"></x-input-error>
</div>
<div>
    <x-input-label for="password" :value="__('Router password')" class="mt-4"></x-input-label>
    <x-text-input id="password" name="password" type="text" class="mt-1 block w-full" :value="old('password', $isEdit ? $router->password : '')" required></x-text-input>
    <x-input-error class="mt-2" :messages="$errors->get('password')"></x-input-error>
</div>
<div>
    <x-input-label for="api_port" :value="__('API Port')" class="mt-4"></x-input-label>
    <x-text-input id="api_port" name="api_port" type="number" class="mt-1 block w-full" :value="old('api_port', $isEdit ? ($router->api_port ?? 8728) : 8728)" required></x-text-input>
    <p class="mt-1 text-xs text-gray-500">{{ __('Default plain API port is 8728, API-SSL is 8729') }}</p>
    <x-input-error class="mt-2" :messages="$errors->get('api_port')"></x-input-error>
</div>
