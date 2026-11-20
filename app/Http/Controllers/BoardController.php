<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class BoardController extends Controller
{
    public function home(): View
    {
        return view('home');
    }
}
