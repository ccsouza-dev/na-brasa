<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        return view('dashboard');
    }

    public function stock()
    {
        return view('stock'); // Need to create this view later
    }

    public function financial()
    {
        return view('financial'); // Need to create this view later
    }

    public function mobile()
    {
        return view('mobile');
    }

    public function caderneta()
    {
        return view('caderneta');
    }
}