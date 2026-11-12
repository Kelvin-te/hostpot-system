<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use Illuminate\Http\Request;

class IncidentController extends Controller
{
    public function index(Request $request)
    {
        $query = Incident::with('router')->latest('opened_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $incidents = $query->paginate(25);

        return view('incidents.index', compact('incidents'));
    }

    public function acknowledge(Incident $incident)
    {
        if ($incident->status === 'open') {
            $incident->update(['status' => 'acknowledged', 'acknowledged_at' => now()]);
        }

        return back()->with('success', 'Incident acknowledged.');
    }

    public function resolve(Incident $incident)
    {
        $incident->update(['status' => 'resolved', 'resolved_at' => now()]);

        return back()->with('success', 'Incident resolved.');
    }
}
