
@aware(['isTailwind', 'isBootstrap'])

<div @class([
            'd-inline-flex h-100 align-items-center ' => $isBootstrap,
        ])>
    <div
        wire:click="clearSearch"

        @class([
                'btn btn-outline-secondary d-inline-flex h-100 align-items-center' => $isBootstrap,
                'inline-flex h-full items-center px-3 text-slate-500 bg-slate-50 rounded-r-md border border-l-0 border-slate-300 cursor-pointer sm:text-sm dark:bg-slate-700 dark:text-white dark:border-slate-600 dark:hover:bg-slate-600' => $isTailwind,
            ])
    >
        @if($isTailwind)
        <x-heroicon-m-x-mark class='w-4 h-4' />
        @else
        <x-heroicon-m-x-mark class="laravel-livewire-tables-btn-smaller" />
        @endif
    </div>
</div>
