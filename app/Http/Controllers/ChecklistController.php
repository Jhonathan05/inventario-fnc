<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChecklistRequest;
use App\Models\Checklist;
use App\Models\Equipo;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ChecklistController extends Controller
{
    public function index(): View
    {
        $checklists = Checklist::with('equipo')->latest()->paginate(20);
        return view('checklists.index', compact('checklists'));
    }

    public function create(): View
    {
        $equipos = Equipo::orderBy('nombre_equipo')->get(['id', 'nombre_equipo', 'serial']);
        return view('checklists.create', compact('equipos'));
    }

    public function store(ChecklistRequest $request): RedirectResponse
    {
        Checklist::create($request->validated());

        return redirect()->route('checklists.index')
            ->with('success', 'Checklist registrado correctamente.');
    }

    public function show(Checklist $checklist): View
    {
        $checklist->load('equipo');
        return view('checklists.show', compact('checklist'));
    }

    public function edit(Checklist $checklist): View
    {
        $equipos = Equipo::orderBy('nombre_equipo')->get(['id', 'nombre_equipo', 'serial']);
        return view('checklists.edit', compact('checklist', 'equipos'));
    }

    public function update(ChecklistRequest $request, Checklist $checklist): RedirectResponse
    {
        $checklist->update($request->validated());

        return redirect()->route('checklists.index')
            ->with('success', 'Checklist actualizado correctamente.');
    }

    public function destroy(Checklist $checklist): RedirectResponse
    {
        $checklist->delete();

        return redirect()->route('checklists.index')
            ->with('success', 'Checklist eliminado correctamente.');
    }
}
