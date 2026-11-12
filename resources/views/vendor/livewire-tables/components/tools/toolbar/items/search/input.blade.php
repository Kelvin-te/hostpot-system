@aware(['isTailwind', 'isBootstrap'])
<input
    wire:model{{ $this->getSearchOptions() }}="search"
    placeholder="{{ $this->getSearchPlaceholder() }}"
    type="text"
    {{ 
        $attributes->merge($this->getSearchFieldAttributes())
        ->class([
            'rounded-lg shadow-sm transition duration-150 ease-in-out sm:text-sm sm:leading-5 rounded-none rounded-l-md focus:ring-0 focus:border-slate-300' => $isTailwind && $this->hasSearch() && (($this->getSearchFieldAttributes()['default'] ?? true) || ($this->getSearchFieldAttributes()['default-styling'] ?? true)),
            'rounded-lg shadow-sm transition duration-150 ease-in-out sm:text-sm sm:leading-5 rounded-lg focus:ring focus:ring-opacity-50' => $isTailwind && !$this->hasSearch()  && (($this->getSearchFieldAttributes()['default'] ?? true) || ($this->getSearchFieldAttributes()['default-styling'] ?? true)),
            'border-slate-300 dark:bg-slate-700 dark:text-white dark:border-slate-600 focus:border-slate-300' => $isTailwind && $this->hasSearch()  && (($this->getSearchFieldAttributes()['default'] ?? true) || ($this->getSearchFieldAttributes()['default-colors'] ?? true)),
            'border-slate-300 dark:bg-slate-700 dark:text-white dark:border-slate-600 focus:border-indigo-300 focus:ring-indigo-200' => $isTailwind && !$this->hasSearch()  && (($this->getSearchFieldAttributes()['default'] ?? true) || ($this->getSearchFieldAttributes()['default-colors'] ?? true)),
            'block w-full' => !$this->hasSearchIcon,
            'pl-8 pr-4' => $this->hasSearchIcon,
            'form-control' => $isBootstrap && ($this->getSearchFieldAttributes()['default'] ?? true),
        ])
        ->except(['default','default-styling','default-colors']) 
    }}

/>