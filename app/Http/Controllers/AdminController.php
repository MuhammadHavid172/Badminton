<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class AdminController extends Controller
{
    /**
     * Dashboard operasional Admin (/admin).
     * Kerangka sementara Fase 3 sesuai spesifikasi.
     */
    public function index(): View
    {
        return view('admin.index');
    }
}
