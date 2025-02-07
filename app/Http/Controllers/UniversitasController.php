<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class UniversitasController extends Controller
{
    public function index()
    {
        return Inertia::render('Universitas/Dashboard');
    }
}
