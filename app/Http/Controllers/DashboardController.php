<?php

namespace App\Http\Controllers;

use App\Services\Dashboard\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboard)
    {
    }

    /**
     * Panel de control. Toda la agregación vive en DashboardService; aquí sólo
     * se resuelve el usuario, la ventana de vencimientos y la vista.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        abort_if($user === null, 403);

        $ventana = $request->integer('ventana', DashboardService::VENTANA_DEFAULT);

        return view('dashboard', $this->dashboard->build($user, $ventana));
    }
}
