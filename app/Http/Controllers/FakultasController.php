<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class FakultasController extends Controller
{
    public function index()
    {
        return Inertia::render('Fakultas/Dashboard');
    }
}
