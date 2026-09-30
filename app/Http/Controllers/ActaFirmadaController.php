<?php

namespace App\Http\Controllers;

use App\Models\ActaFirmada;
use App\Models\ActaFirmadaVersion;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Services\PdfCompressor;
use ZipArchive;

class ActaFirmadaController extends Controller
{
    public function index(Request $request)
    {
        $query = ActaFirmada::with('user');

        if ($request->filled('numero_acta')) {
            $query->where('numero_acta', 'like', '%' . $request->numero_acta . '%');
        }
        if ($request->filled('fecha_documento')) {
            $query->where('fecha_documento', $request->fecha_documento);
        }

        $actas = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('actas_firmadas.index', compact('actas'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'numero_acta'     => 'required|string',
            'fecha_documento' => 'required|date',
            'archivo_pdf'     => 'required|mimes:pdf|max:10240',
        ]);

        $uploadedFile = $request->file('archivo_pdf');
        $originalPath = $uploadedFile->path();
        
        // Attempt compression
        $tempTarget = storage_path('app/temp_compressed_' . uniqid() . '.pdf');
        $finalPath = PdfCompressor::compress($originalPath, $tempTarget);
        
        // Store the final file (compressed or original)
        $path = Storage::disk('local')->putFile('actas_firmadas', new \Illuminate\Http\File($finalPath));
        
        // Cleanup temp file if compression was used
        if ($finalPath === $tempTarget && file_exists($tempTarget)) {
            @unlink($tempTarget);
        }

        $acta = ActaFirmada::create([
            'numero_acta'     => $request->numero_acta,
            'tipo_acta'       => 'Entrega',
            'fecha_documento' => $request->fecha_documento,
            'observaciones'   => $request->observaciones,
            'archivo_pdf'     => $path,
            'user_id'         => Auth::id()
        ]);

        AuditLog::create([
            'user_id'   => Auth::id(),
            'user_name' => Auth::user()->name,
            'action'    => 'UPLOAD_ACTA_FIRMADA',
            'details'   => json_encode(['numero_acta' => $acta->numero_acta])
        ]);

        return redirect()->route('actas-firmadas.index')->with('success', 'Acta firmada cargada exitosamente.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'archivo_pdf'   => 'required|mimes:pdf|max:10240',
            'motivo_cambio' => 'required|string',
        ]);

        $acta = ActaFirmada::findOrFail($id);

        // Calculate current version number
        $currentVersion = $acta->versions()->count() + 1;

        // Save current state to versions
        ActaFirmadaVersion::create([
            'acta_firmada_id' => $acta->id,
            'archivo_pdf'     => $acta->archivo_pdf,
            'version'         => $currentVersion,
            'motivo_cambio'   => $request->motivo_cambio,
            'user_id'         => Auth::id()
        ]);

        // Store new PDF
        $newPath = $request->file('archivo_pdf')->store('actas_firmadas', 'local');

        $acta->update([
            'archivo_pdf' => $newPath
        ]);

        AuditLog::create([
            'user_id'   => Auth::id(),
            'user_name' => Auth::user()->name,
            'action'    => 'REPLACE_ACTA_FIRMADA',
            'details'   => json_encode(['numero_acta' => $acta->numero_acta, 'version_anterior' => $currentVersion])
        ]);

        return redirect()->route('actas-firmadas.index')->with('success', 'Acta firmada reemplazada. Se ha guardado el historial de versiones.');
    }

    public function download($id)
    {
        $acta = ActaFirmada::findOrFail($id);
        
        if (!Storage::disk('local')->exists($acta->archivo_pdf)) {
            return back()->with('error', 'El archivo no existe en el disco.');
        }

        AuditLog::create([
            'user_id'   => Auth::id(),
            'user_name' => Auth::user()->name,
            'action'    => 'DOWNLOAD_ACTA_FIRMADA',
            'details'   => json_encode(['numero_acta' => $acta->numero_acta])
        ]);

        return Storage::disk('local')->download($acta->archivo_pdf, $acta->numero_acta . '.pdf');
    }

    public function showFile($id)
    {
        $acta = ActaFirmada::findOrFail($id);
        
        if (!Storage::disk('local')->exists($acta->archivo_pdf)) {
            return back()->with('error', 'El archivo no existe en el disco.');
        }

        AuditLog::create([
            'user_id'   => Auth::id(),
            'user_name' => Auth::user()->name,
            'action'    => 'VIEW_ACTA_FIRMADA',
            'details'   => json_encode(['numero_acta' => $acta->numero_acta])
        ]);

        return Storage::disk('local')->response($acta->archivo_pdf);
    }

    public function downloadVersion($id)
    {
        $version = ActaFirmadaVersion::findOrFail($id);
        
        if (!Storage::disk('local')->exists($version->archivo_pdf)) {
            return back()->with('error', 'El archivo de esta versión no existe en el disco.');
        }

        AuditLog::create([
            'user_id'   => Auth::id(),
            'user_name' => Auth::user()->name,
            'action'    => 'DOWNLOAD_ACTA_FIRMADA_VERSION',
            'details'   => json_encode(['numero_acta' => $version->actaFirmada->numero_acta, 'version' => $version->version])
        ]);

        return Storage::disk('local')->download($version->archivo_pdf, $version->actaFirmada->numero_acta . '_v' . $version->version . '.pdf');
    }

    public function history($id)
    {
        $acta = ActaFirmada::with(['versions.user'])->findOrFail($id);
        return view('actas_firmadas.history', compact('acta'));
    }

    public function destroy($id)
    {
        $acta = ActaFirmada::findOrFail($id);
        $numero_acta = $acta->numero_acta;

        $acta->delete();

        AuditLog::create([
            'user_id'   => Auth::id(),
            'user_name' => Auth::user()->name,
            'action'    => 'DELETE_ACTA_FIRMADA',
            'details'   => json_encode(['numero_acta' => $numero_acta])
        ]);

        return redirect()->route('actas-firmadas.index')->with('success', 'Acta firmada eliminada exitosamente.');
    }
}
