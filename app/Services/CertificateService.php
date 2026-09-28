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

    /** Languages the PDF fonts can render correctly. */
    private const PDF_LOCALES = ['en', 'fr', 'es', 'pt'];

    public function generatePdf(Certificate $certificate): string
    {
        $certificate->load(['user', 'course.translations', 'course.instructor']);

        $sigName  = Setting::get('cert_sig_name', 'Prof. Emmanuel ZANG');
        $sigTitle = Setting::get('cert_sig_title', 'Director, ZTF University Institute');

        // The certificate is written in the learner's language. DomPDF's bundled fonts cannot draw
        // Chinese nor join Arabic letters, so those learners get the English version.
        $previousLocale = app()->getLocale();
        $locale = in_array($certificate->user->preferredLocale(), self::PDF_LOCALES, true)
            ? $certificate->user->preferredLocale()
            : 'en';
        app()->setLocale($locale);
        \Illuminate\Support\Carbon::setLocale($locale);

        try {
            $pdf = Pdf::loadView('certificates.pdf', [
                'certificate' => $certificate,
                'sig_name'    => $sigName,
                'sig_title'   => $sigTitle,
            ])->setPaper('a4', 'landscape');

            $filename = 'cert_' . $certificate->certificate_code . '.pdf';
            $path     = 'certificates/' . $filename;

            Storage::disk('public')->put($path, $pdf->output());
        } finally {
            app()->setLocale($previousLocale);
            \Illuminate\Support\Carbon::setLocale($previousLocale);
        }

        return $filename;
    }
}
