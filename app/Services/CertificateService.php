<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\Enrollment;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class CertificateService
{
    /**
     * Version of the PDF design. Raise it when the template changes: the certificates issued
     * before are then rebuilt the next time they are downloaded.
     */
    public const TEMPLATE_VERSION = 2;

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

        $this->generatePdf($certificate);

        return $certificate;
    }

    /** File name of an up-to-date PDF: built again when it is missing or was made with an older design. */
    public function currentPdf(Certificate $certificate): string
    {
        $filename = $this->filename($certificate);
        if ($certificate->pdf_path !== $filename || ! Storage::disk('public')->exists('certificates/' . $filename)) {
            $this->generatePdf($certificate);
        }

        return $filename;
    }

    private function filename(Certificate $certificate): string
    {
        return 'cert_' . $certificate->certificate_code . '_v' . self::TEMPLATE_VERSION . '.pdf';
    }

    /** Languages the PDF fonts can render correctly. */
    private const PDF_LOCALES = ['en', 'fr', 'es', 'pt'];

    public function generatePdf(Certificate $certificate): string
    {
        $certificate->load(['user', 'course.translations', 'course.instructor']);

        $sigName  = Setting::get('cert_sig_name', 'Prof. Emmanuel ZANG');
        $sigTitle = Setting::get('cert_sig_title', 'Directeur, Institut de Formation Professionnelle ZTF');

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

            $filename = $this->filename($certificate);

            Storage::disk('public')->put('certificates/' . $filename, $pdf->output());
        } finally {
            app()->setLocale($previousLocale);
            \Illuminate\Support\Carbon::setLocale($previousLocale);
        }

        // The file made with an earlier design is no longer needed.
        if ($certificate->pdf_path && $certificate->pdf_path !== $filename) {
            Storage::disk('public')->delete('certificates/' . $certificate->pdf_path);
        }
        $certificate->update(['pdf_path' => $filename]);

        return $filename;
    }
}
