<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\Enrollment;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class CertificateService
{
    public function issue(Enrollment $enrollment): Certificate
    {
        $existing = Certificate::where('user_id', $enrollment->user_id)
            ->where('course_id', $enrollment->course_id)
            ->first();

        if ($existing) {
            return $existing;
        }

        $code = Certificate::generateCode();

        $certificate = Certificate::create([
            'user_id'          => $enrollment->user_id,
            'course_id'        => $enrollment->course_id,
            'certificate_code' => $code,
            'issued_at'        => now(),
        ]);

        // Generate PDF
        $pdfPath = $this->generatePdf($certificate);
        $certificate->update(['pdf_path' => $pdfPath]);

        return $certificate;
    }

    public function generatePdf(Certificate $certificate): string
    {
        $certificate->load(['user', 'course.translations', 'course.instructor']);

        $sigName  = Setting::get('cert_sig_name', 'Prof. Emmanuel ZANG');
        $sigTitle = Setting::get('cert_sig_title', 'Director, ZTF University Institute');

        $pdf = Pdf::loadView('certificates.pdf', [
            'certificate' => $certificate,
            'sig_name'    => $sigName,
            'sig_title'   => $sigTitle,
        ])->setPaper('a4', 'landscape');

        $filename = 'cert_' . $certificate->certificate_code . '.pdf';
        $path     = 'certificates/' . $filename;

        Storage::disk('public')->put($path, $pdf->output());

        return $filename;
    }
}
