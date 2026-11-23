<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class OwnerController extends Controller
{
    /**
     * Dashboard pemantauan Owner (/owner).
     * Kerangka sementara Fase 3 sesuai spesifikasi.
     */
    public function index(): View
    {
        return view('owner.index');
    }
}
