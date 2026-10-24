<?php

namespace App\Http\Controllers;

use App\Models\Territorio;
use App\Models\TipoRecurso;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Módulo Catálogos (Lote B3) — edición de las listas del CMDB sin código.
 *
 * Si el formato del CMDB cambia, el analista actualiza aquí los valores y
 * todos los selects del sistema (actas, correcciones, formularios,
 * importador) los leen en vivo. Los valores EN USO por activos no se
 * borran: se desactivan.
 *
 * Listas: estados · razones · diccionarios (14 grupos) · territorios ·
 * tipos de recurso.
 */
class CatalogoController extends Controller
{
    /** Definición de cada lista editable. */
    private function definiciones(): array
    {
        return [
            'estados' => [
                'titulo'  => 'Estados operativos',
                'tabla'   => 'catalogo_estados',
                'campos'  => ['codigo' => ['Código', 'required|max:40'], 'nombre' => ['Nombre', 'required|max:60'], 'descripcion' => ['Descripción', 'nullable|max:500']],
                'enUso'   => fn ($fila) => DB::table('equipos')->whereNull('deleted_at')->where('estado_operativo', $fila->codigo)->exists(),
            ],
            'razones' => [
                'titulo'  => 'Razones del estado',
                'tabla'   => 'catalogo_razones',
                'campos'  => ['codigo' => ['Código', 'required|max:40'], 'nombre' => ['Nombre', 'required|max:60'], 'descripcion' => ['Descripción', 'nullable|max:500']],
                'enUso'   => fn ($fila) => DB::table('equipos')->whereNull('deleted_at')->where('razon_estado', $fila->codigo)->exists(),
            ],
            'diccionarios' => [
                'titulo'  => 'Listas de valores (diccionarios)',
                'tabla'   => 'diccionarios',
                'campos'  => ['grupo' => ['Grupo', 'required|max:50'], 'valor' => ['Valor', 'required|max:250'], 'activo' => ['Activo', 'boolean']],
                'enUso'   => null, // referencia débil: sólo se desactiva
            ],
            'territorios' => [
                'titulo'  => 'Territorio (circunscripción · seccional · municipio)',
                'tabla'   => 'territorios',
                'campos'  => ['circunscripcion' => ['Circunscripción', 'required|max:80'], 'seccional' => ['Seccional', 'required|max:80'], 'municipio' => ['Municipio', 'required|max:120'], 'activo' => ['Activo', 'boolean']],
                'enUso'   => null,
            ],
            'tipos' => [
                'titulo'  => 'Tipos de recurso',
                'tabla'   => 'tipo_recursos',
                'campos'  => ['nombre' => ['Nombre', 'required|max:100'], 'es_computador' => ['Es computador', 'boolean'], 'es_catalogo_cmdb' => ['Catálogo CMDB', 'boolean'], 'activo' => ['Activo', 'boolean']],
                'enUso'   => fn ($fila) => DB::table('equipos')->whereNull('deleted_at')->where('tipo_recurso_id', $fila->id)->exists(),
            ],
        ];
    }

    public function index(Request $request): View
    {
        $lista = $this->listaValida($request);
        $def = $this->definiciones()[$lista];

        $query = DB::table($def['tabla']);
        $query = match ($lista) {
            'diccionarios' => $query->orderBy('grupo')->orderBy('valor'),
            'territorios'  => $query->orderBy('circunscripcion')->orderBy('seccional')->orderBy('municipio'),
            'tipos'        => $query->orderBy('nombre'),
            default        => $query->orderBy('id'),
        };

        if ($request->filled('q')) {
            $q = '%' . mb_strtolower($request->q) . '%';
            $query->where(function ($sub) use ($def, $q) {
                foreach (array_keys($def['campos']) as $campo) {
                    $sub->orWhereRaw("LOWER(COALESCE({$campo}, '')) LIKE ?", [$q]);
                }
            });
        }

        $filas = $query->limit(500)->get();

        return view('catalogos.index', [
            'lista'   => $lista,
            'def'     => $def,
            'filas'   => $filas,
            'listas'  => collect($this->definiciones())->map(fn ($d, $k) => ['key' => $k, 'titulo' => $d['titulo']])->values(),
            'conteos' => collect($this->definiciones())->map(fn ($d, $k) => DB::table($d['tabla'])->count()),
        ]);
    }

    public function store(Request $request, string $lista): RedirectResponse
    {
        $def = $this->definiciones()[$this->listaValida($request, $lista)];

        $reglas = collect($def['campos'])->map(fn ($c) => $c[1])->all();
        $datos = $request->validate($reglas);

        // Único por clave natural
        try {
            DB::table($def['tabla'])->insert(array_merge($datos, ['created_at' => now(), 'updated_at' => now()]));
        } catch (\Illuminate\Database\UniqueConstraintViolationException) {
            return back()->withErrors(['valor' => 'Ya existe un registro con esa clave (el valor debe ser único).']);
        }

        return back()->with('success', 'Valor agregado a la lista.');
    }

    public function update(Request $request, string $lista, int $id): RedirectResponse
    {
        $def = $this->definiciones()[$this->listaValida($request, $lista)];

        $reglas = collect($def['campos'])->map(fn ($c) => $c[1])->all();
        $datos = $request->validate($reglas);
        $datos['updated_at'] = now();

        // El código de estados/razones no se cambia si está en uso
        if (in_array($lista, ['estados', 'razones'], true) && isset($def['enUso'])) {
            $fila = DB::table($def['tabla'])->find($id);
            if ($fila && ($def['enUso'])($fila) && ($datos['codigo'] ?? null) !== $fila->codigo) {
                return back()->withErrors(['codigo' => 'El código está en uso por activos y no puede cambiarse (desactive el valor y cree uno nuevo).']);
            }
        }

        DB::table($def['tabla'])->where('id', $id)->update($datos);

        return back()->with('success', 'Valor actualizado.');
    }

    /** Activa/desactiva (nunca borra lo que está en uso). */
    public function toggle(Request $request, string $lista, int $id): RedirectResponse
    {
        $def = $this->definiciones()[$this->listaValida($request, $lista)];
        $fila = DB::table($def['tabla'])->find($id);

        if (! $fila) {
            return back()->withErrors(['valor' => 'El registro no existe.']);
        }

        $columnaActivo = in_array('activo', array_keys($def['campos']), true) ? 'activo' : null;

        // Tablas sin columna activo (estados/razones): sólo permitir "eliminar"
        // si el valor no está en uso; en caso contrario se rechaza.
        if ($columnaActivo === null) {
            if ($def['enUso'] && ($def['enUso'])($fila)) {
                return back()->withErrors(['valor' => 'No se puede retirar: hay activos usando este valor. Desactivar no aplica a este catálogo; cree el reemplazo primero.']);
            }
            DB::table($def['tabla'])->where('id', $id)->delete();
            return back()->with('success', 'Valor retirado del catálogo (no estaba en uso).');
        }

        DB::table($def['tabla'])->where('id', $id)->update([
            $columnaActivo => ! $fila->{$columnaActivo},
            'updated_at' => now(),
        ]);

        return back()->with('success', ($fila->{$columnaActivo} ? 'Valor desactivado' : 'Valor activado') . '.');
    }

    private function listaValida(Request $request, ?string $lista = null): string
    {
        // Case-insensitive: el sanitizer global CapitalizeFirstLetter
        // transforma el input a Title Case («estados» → «Estados»).
        $lista = mb_strtolower($lista ?? $request->input('lista', 'estados'));
        $definiciones = $this->definiciones();
        $clave = collect(array_keys($definiciones))
            ->first(fn ($k) => mb_strtolower($k) === $lista);

        abort_unless($clave !== null, 404);

        return $clave;
    }
}
