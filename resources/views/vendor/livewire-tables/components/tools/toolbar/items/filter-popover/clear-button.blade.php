@aware(['isTailwind','isBootstrap4','isBootstrap5', 'localisationPath'])
<button type="button" wire:click.prevent="setFilterDefaults" x-on:click="filterPopoverOpen = false" @class([
        'w-full inline-flex items-center justify-center px-3 py-2 border border-slate-300 shadow-sm text-sm leading-4 font-medium rounded-lg text-slate-700 bg-white hover:bg-slate-50 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:hover:border-slate-500 dark:hover:bg-slate-600' => $isTailwind,
        'dropdown-item btn text-center' => $isBootstrap4,
        'dropdown-item text-center' => $isBootstrap5,
    ])>
    {{ __($localisationPath.'Clear') }}
</button>