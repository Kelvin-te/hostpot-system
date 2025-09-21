<?php

namespace App\Http\Livewire;

use App\Models\Package;
use Livewire\Component;
use App\Models\Router;

class RouterPackagesDropdown extends Component
{
    public $routerId;
    public $packages = [];

    public function updatedRouterId($value)
    {
        // Load packages based on the selected router
        $router = Router::find($value);
        if ($router) {
            $this->packages = $router->packages;
        } else {
            $this->packages = [];
        }
    }

    public function render()
    {
        // Load routers for the dropdown
        $routers = Router::all();
        return view('livewire.router-packages-dropdown', [
            'routers' => $routers,
        ]);
    }
}
