<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Certificate;

class CertificateVerifyController extends Controller
{
    public function verify(string $code)
    {
        $certificate = Certificate::where('certificate_code', $code)
            ->with(['user', 'course.translations', 'course.instructor'])
            ->first();

        return view('verify-certificate', compact('certificate', 'code'));
    }
}
