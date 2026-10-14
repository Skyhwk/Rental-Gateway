<?php

namespace App\Http\Controllers;

class ManualBookController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        return view('manual-book.index');
    }
}
