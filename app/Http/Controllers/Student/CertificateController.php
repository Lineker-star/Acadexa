<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Services\CertificateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CertificateController extends Controller
{
    public function __construct(private CertificateService $service) {}

    public function index(Request $request)
    {
        $certificates = $request->user()->certificates()
            ->with(['course.translations', 'course.instructor'])
            ->latest('issued_at')
            ->paginate(12);

        return view('student.certificates.index', compact('certificates'));
    }

    public function download(Request $request, Certificate $certificate)
    {
        abort_if($certificate->user_id !== $request->user()->id, 403);

        if (! $certificate->pdf_path || ! Storage::disk('public')->exists('certificates/' . $certificate->pdf_path)) {
            $this->service->generatePdf($certificate);
            $certificate->refresh();
        }

        return Storage::disk('public')->download(
            'certificates/' . $certificate->pdf_path,
            'ACADEXA_Certificate_' . $certificate->certificate_code . '.pdf'
        );
    }
}
