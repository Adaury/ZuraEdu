<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class IntegracionesController extends Controller
{
    public function index()
    {
        return view('admin.integraciones.index');
    }
}
