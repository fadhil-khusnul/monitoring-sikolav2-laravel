<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class ProdiController extends Controller
{
    public function index()
    {
        return Inertia::render('Prodi/Dashboard');
    }
}
