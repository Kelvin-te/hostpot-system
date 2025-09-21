<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRouterRequest;
use App\Http\Requests\UpdateRouterRequest;
use Illuminate\Http\Request;
use App\Models\Router;
use App\Classes\Mikrotik;

class RouterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (!auth()->user()->isAdmin()) {
            redirect('/');
        }
        
        $routers = Router::orderBy("name","asc")->get();
        return view("router.index", compact("routers"));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        if (!auth()->user()->isAdmin()) {
            return redirect('/');
        }

        return view('router.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:routers',
            'location' => 'required',
            'ip'=> 'required|ip',
            'username'=> 'required',
            'password'=> 'required',
        ]);

        $router = new Router();
        $router->fill($validated);
        $router->save();

        return redirect('router')->with('success', __('Router successfully added'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Router $router)
    {
        if (!auth()->user()->isAdmin()) {
            return redirect('/');
        }
        
        // Initialize data arrays
        $connectionStatus = null;
        $systemInfo = null;
        $interfaces = null;
        
        try {
            $mikrotik = new Mikrotik();
            
            // Test connection and get status
            $connectionResult = $mikrotik->testConnection($router);
            $connectionStatus = [
                'success' => $connectionResult['success'],
                'message' => $connectionResult['message'] ?? 'Unknown status',
                'diagnostics' => $connectionResult['diagnostics'] ?? null
            ];
            
            // If connection is successful, get system info and interfaces
            if ($connectionResult['success']) {
                // Get system information
                $systemResult = $mikrotik->getSystemInfo($router);
                if ($systemResult['success']) {
                    $systemInfo = $systemResult['data'];
                }
                
                // Get interface information
                $interfaceResult = $mikrotik->getInterfaces($router);
                if ($interfaceResult['success']) {
                    $interfaces = $interfaceResult['data'];
                }
            }
            
        } catch (\Exception $e) {
            $connectionStatus = [
                'success' => false,
                'message' => 'Error loading router data: ' . $e->getMessage(),
                'diagnostics' => null
            ];
        }
        
        return view('router.show', compact('router', 'connectionStatus', 'systemInfo', 'interfaces'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Router $router)
    {
        if (!auth()->user()->isAdmin()) {
            return redirect('/');
        }
        return view('router.edit', compact('router'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Router $router)
    {
        $validated = $request->validate([
            'location'=> 'nullable|string',
            'ip'=> 'required|ip',
            'username'=> 'required',
            'password'=> 'required',
        ]);

        $router->location = $validated['location'] ? $request->location : $router->location;
        $router->ip = $validated['ip'] ? $request->ip : $router->ip;
        $router->username = $validated['username'] ? $request->username : $router->username;
        $router->password = $validated['password'] ? $request->password : $router->password;
        $router->save();

        return redirect('router')->with('success', __('Router updated successfully'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Router $router)
    {
        if (!auth()->user()->isAdmin()) {
            return redirect('/');
        }
        
        $router->delete();
        return redirect('router')->with('success', __('Router deleted successfully'));
    }

    /**
     * Test connection to a specific router
     */
    public function testConnection(Router $router)
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        try {
            $mikrotik = new Mikrotik();
            $result = $mikrotik->testConnection($router);
            
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get system information from router
     */
    public function getSystemInfo(Router $router)
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        try {
            $mikrotik = new Mikrotik();
            $result = $mikrotik->getSystemInfo($router);
            
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get system info: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get interface information from router
     */
    public function getInterfaces(Router $router)
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        try {
            $mikrotik = new Mikrotik();
            $result = $mikrotik->getInterfaces($router);
            
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get interfaces: ' . $e->getMessage()
            ]);
        }
    }
}
