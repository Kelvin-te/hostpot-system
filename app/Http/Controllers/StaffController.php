<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StaffController extends Controller
{
    public function index()
    {
        $staff = Staff::orderBy('name')->paginate(20);

        return view('staff.index', compact('staff'));
    }

    public function create()
    {
        return view('staff.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('staff', 'email')],
            'phone' => ['required', 'string', 'max:20', Rule::unique('staff', 'phone')],
            'role' => ['required', 'in:admin,staff'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        Staff::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'role' => $data['role'],
            'password' => $data['password'],
            'is_active' => true,
        ]);

        return redirect()->route('staff.index')->with('success', 'Staff member created.');
    }

    public function edit(Staff $staff)
    {
        return view('staff.edit', compact('staff'));
    }

    public function update(Request $request, Staff $staff)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('staff', 'email')->ignore($staff->id)],
            'phone' => ['required', 'string', 'max:20', Rule::unique('staff', 'phone')->ignore($staff->id)],
            'role' => ['required', 'in:admin,staff'],
            'is_active' => ['boolean'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $staff->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'role' => $data['role'],
            'is_active' => $request->boolean('is_active', false),
        ]);

        if (! empty($data['password'])) {
            $staff->password = $data['password'];
        }

        $staff->save();

        return redirect()->route('staff.index')->with('success', 'Staff member updated.');
    }

    public function destroy(Staff $staff)
    {
        $staff->delete();

        return redirect()->route('staff.index')->with('success', 'Staff member deleted.');
    }
}
