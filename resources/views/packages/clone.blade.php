<x-app-layout>
    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if(session('success'))
                        <div class="alert alert-success text-green-600 mb-4">{{ session('success') }}</div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger text-red-600 mb-4">{{ session('error') }}</div>
                    @endif

                    <div class="flex justify-between items-center mb-6 border-b-2 border-slate-100 pb-4">
                        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Clone Packages Between Routers</h2>
                        <a href="{{ route('packages.index') }}" class="text-gray-600 hover:text-gray-800">Back</a>
                    </div>

                    <form method="POST" action="{{ route('packages.clone') }}" class="space-y-6">
                        @csrf

                        <div>
                            <x-input-label for="source_router_id" :value="__('Source Router')" />
                            <select id="source_router_id" name="source_router_id" class="mt-1 block w-full rounded-md border border-gray-300" required>
                                <option value="">Select router</option>
                                @foreach ($routers as $r)
                                    <option value="{{ $r->id }}" {{ (isset($sourceRouterId) && (int)$sourceRouterId === (int)$r->id) ? 'selected' : '' }}>{{ $r->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('source_router_id')" />
                        </div>

                        <div class="bg-gray-50 p-4 rounded-md space-y-4">
                            <div class="flex items-center">
                                <input id="clone_all" name="clone_all" type="checkbox" value="1" class="h-4 w-4 text-indigo-600 border-gray-300 rounded">
                                <label for="clone_all" class="ml-2 block text-sm text-gray-700">Clone to all other routers</label>
                            </div>

                            <div>
                                <x-input-label for="dest_router_id" :value="__('Destination Router (if not cloning to all)')" />
                                <select id="dest_router_id" name="dest_router_id" class="mt-1 block w-full rounded-md border border-gray-300">
                                    <option value="">Select destination router</option>
                                    @foreach ($routers as $r)
                                        <option value="{{ $r->id }}">{{ $r->name }}</option>
                                    @endforeach
                                </select>
                                <x-input-error class="mt-2" :messages="$errors->get('dest_router_id')" />
                            </div>

                            <div class="flex items-center">
                                <input id="overwrite" name="overwrite" type="checkbox" value="1" class="h-4 w-4 text-indigo-600 border-gray-300 rounded">
                                <label for="overwrite" class="ml-2 block text-sm text-gray-700">Overwrite existing packages on destination</label>
                            </div>
                        </div>

                        <div class="flex items-center gap-4 mt-6">
                            <x-primary-button>Clone Packages</x-primary-button>
                            <a href="{{ route('packages.index') }}" class="text-gray-600 hover:text-gray-800">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const cloneAll = document.getElementById('clone_all');
            const destSelect = document.getElementById('dest_router_id');
            function toggleDest() {
                const disabled = cloneAll.checked;
                destSelect.disabled = disabled;
                if (disabled) destSelect.value = '';
            }
            cloneAll.addEventListener('change', toggleDest);
            toggleDest();
        });
    </script>
    @endpush
</x-app-layout>
