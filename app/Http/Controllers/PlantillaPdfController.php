<?php

namespace App\Http\Controllers;

use App\Http\Requests\PlantillaPdfRequest;
use App\Models\PlantillaPdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PlantillaPdfController extends Controller
{
    public function index(): View
    {
        $plantillas = PlantillaPdf::with('creadaPor')->latest()->get();
        return view('plantillas_pdf.index', compact('plantillas'));
    }

    public function create(): View
    {
        $tipos      = PlantillaPdf::TIPOS;
        $variables  = PlantillaPdf::VARIABLES_DISPONIBLES;
        return view('plantillas_pdf.create', compact('tipos', 'variables'));
    }

    public function store(PlantillaPdfRequest $request): RedirectResponse
    {
        $datos = $request->validated();
        $datos['user_id'] = auth()->id();

        // Si se activa, desactivar las del mismo tipo
        if (!empty($datos['activa'])) {
            PlantillaPdf::where('tipo', $datos['tipo'])->update(['activa' => false]);
        }

        PlantillaPdf::create($datos);

        return redirect()
            ->route('plantillas-pdf.index')
            ->with('success', 'Plantilla creada correctamente.');
    }

    public function edit(PlantillaPdf $plantillasPdf): View
    {
        $tipos     = PlantillaPdf::TIPOS;
        $variables = PlantillaPdf::VARIABLES_DISPONIBLES;
        return view('plantillas_pdf.edit', compact('plantillasPdf', 'tipos', 'variables'));
    }

    public function update(PlantillaPdfRequest $request, PlantillaPdf $plantillasPdf): RedirectResponse
    {
        $datos = $request->validated();

        // Si se activa, desactivar las del mismo tipo (excepto esta misma)
        if (!empty($datos['activa'])) {
            PlantillaPdf::where('tipo', $datos['tipo'])
                ->where('id', '!=', $plantillasPdf->id)
                ->update(['activa' => false]);
        }

        $plantillasPdf->update($datos);

        return redirect()
            ->route('plantillas-pdf.index')
            ->with('success', 'Plantilla actualizada correctamente.');
    }

    public function destroy(PlantillaPdf $plantillasPdf): RedirectResponse
    {
        $plantillasPdf->delete();
        return redirect()
            ->route('plantillas-pdf.index')
            ->with('success', 'Plantilla eliminada.');
    }

    public function show(PlantillaPdf $plantillasPdf): View
    {
        $variables = PlantillaPdf::VARIABLES_DISPONIBLES;
        return view('plantillas_pdf.show', compact('plantillasPdf', 'variables'));
    }
}
