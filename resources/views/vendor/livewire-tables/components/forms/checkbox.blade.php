@aware(['tableName','primaryKey', 'isTailwind', 'isBootstrap', 'isBootstrap4', 'isBootstrap5'])
@props(['checkboxAttributes'])
<input x-cloak
    {{
        $attributes->merge($checkboxAttributes)->class([
            'border-slate-300 text-indigo-600 focus:border-indigo-300 focus:ring-indigo-200 dark:bg-slate-900 dark:text-white dark:border-slate-600 dark:hover:bg-slate-600 dark:focus:bg-slate-600' => ($isTailwind) && ($checkboxAttributes['default-colors'] ?? ($checkboxAttributes['default'] ?? true)),
            'rounded shadow-sm transition duration-150 ease-in-out focus:ring focus:ring-opacity-50' => ($isTailwind) && ($checkboxAttributes['default-styling'] ?? ($checkboxAttributes['default'] ?? true)),
            'form-check-input' => ($isBootstrap5) && ($checkboxAttributes['default'] ?? true),
        ])->except(['default','default-styling','default-colors'])
    }}
/>