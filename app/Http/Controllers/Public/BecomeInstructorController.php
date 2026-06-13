<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BecomeInstructorController extends Controller
{
    public function index()
    {
        $alreadyApplied = false;
        if (auth()->check()) {
            $alreadyApplied = auth()->user()->instructorApplication()->exists();
        }
        return view('become-instructor', compact('alreadyApplied'));
    }
}
