<!DOCTYPE html>
{{-- Certificate PDF (DomPDF, A4 landscape). Fully drawn in HTML/CSS from the "Certificate template"
     settings of the admin: no background image is needed. --}}
@php
    $color       = \App\Models\Setting::get('cert_bg_color', '#0A2A5E') ?: '#0A2A5E';
    $institution = \App\Models\Setting::get('cert_institution', 'ZTF University Institute');
    $subheading  = \App\Models\Setting::get('cert_subheading', 'ACADEXA Learning Management System');
    $description = \App\Models\Setting::get('cert_description');
    $logoPath    = \App\Models\Setting::get('cert_logo');
    $logoFile    = $logoPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($logoPath)
        ? \Illuminate\Support\Facades\Storage::disk('public')->path($logoPath)
        : public_path('images/logo.png');
    // DomPDF needs the GD extension to draw PNG images: without it, the certificate is issued without logo
    // rather than failing (and blocking the completion of the course).
    $logo = is_file($logoFile) && extension_loaded('gd')
        ? 'data:' . (mime_content_type($logoFile) ?: 'image/png') . ';base64,' . base64_encode(file_get_contents($logoFile))
        : null;
    $course = $certificate->course;
    $finalScore = $course->finalExam
        ? $course->finalExam->standardAttempts()->where('user_id', $certificate->user_id)->where('passed', true)->max('score')
        : null;
@endphp
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 0; }
    * { margin: 0; padding: 0; }
    body { font-family: 'DejaVu Sans', sans-serif; color: #1F2937; width: 297mm; height: 210mm; }
    .frame { position: absolute; top: 8mm; left: 8mm; right: 8mm; bottom: 8mm; border: 3mm solid {{ $color }}; }
    .inner { position: absolute; top: 12.5mm; left: 12.5mm; right: 12.5mm; bottom: 12.5mm; border: 0.6mm solid #C8A04A; }
    .content { position: absolute; top: 20mm; left: 25mm; right: 25mm; text-align: center; }
    .logo { height: 20mm; margin-bottom: 3mm; }
    .institution { font-size: 17pt; font-weight: bold; color: {{ $color }}; letter-spacing: 1px; }
    .subheading { font-size: 9pt; color: #6B7280; margin-top: 1mm; letter-spacing: 1px; }
    .title { font-family: 'DejaVu Serif', serif; font-size: 26pt; font-weight: bold; color: #C1440E; letter-spacing: 4px; margin-top: 8mm; }
    .rule { width: 60mm; height: 0.8mm; background: #C8A04A; margin: 3mm auto 0; }
    .awarded { font-size: 11pt; color: #4B5563; margin-top: 7mm; }
    .name { font-family: 'DejaVu Serif', serif; font-size: 30pt; font-style: italic; font-weight: bold; color: {{ $color }}; margin-top: 3mm; }
    .completed { font-size: 11pt; color: #4B5563; margin-top: 4mm; }
    .course { font-size: 17pt; font-weight: bold; color: #C1440E; margin-top: 2mm; }
    .meta { font-size: 9.5pt; color: #4B5563; margin-top: 3mm; }
    .description { font-size: 9pt; color: #6B7280; margin: 4mm auto 0; width: 190mm; }
    .footer { position: absolute; left: 25mm; right: 25mm; bottom: 24mm; }
    .footer table { width: 100%; border-collapse: collapse; }
    .footer td { vertical-align: bottom; font-size: 8pt; color: #374151; }
    .seal { width: 26mm; height: 26mm; border-radius: 13mm; background: {{ $color }}; color: #F5B041; text-align: center;
            font-size: 7pt; font-weight: bold; letter-spacing: 1px; border: 1.2mm solid #C8A04A; }
    .seal span { display: block; padding-top: 10mm; }
    .signature-line { border-top: 0.4mm solid #374151; width: 65mm; margin-left: auto; padding-top: 1.5mm; text-align: center; }
    .verify { position: absolute; left: 0; right: 0; bottom: 15mm; text-align: center; font-size: 6.5pt; color: #6B7280; }
</style>
</head>
<body>
    <div class="frame"></div>
    <div class="inner"></div>

    <div class="content">
        @if($logo)<img src="{{ $logo }}" class="logo" alt="">@endif
        <div class="institution">{{ $institution }}</div>
        <div class="subheading">{{ $subheading }}</div>

        <div class="title">{{ __('CERTIFICATE OF COMPLETION') }}</div>
        <div class="rule"></div>

        <div class="awarded">{{ __('learn.cert_awarded_to') }}</div>
        <div class="name">{{ $certificate->user->name }}</div>
        <div class="completed">{{ __('has completed') }}</div>
        <div class="course">{{ $course->title() }}</div>
        <div class="meta">
            {{ __('learn.cert_hours', ['hours' => $course->hoursLabel()]) }}
            @if($finalScore !== null) &nbsp;·&nbsp; {{ __('learn.cert_final_score', ['score' => rtrim(rtrim(number_format((float) $finalScore, 1, '.', ''), '0'), '.')]) }} @endif
        </div>
        @if($description)<div class="description">{{ $description }}</div>@endif
    </div>

    <div class="footer">
        <table>
            <tr>
                <td style="width:38%">
                    {{ __('Date of issue: :date', ['date' => $certificate->issued_at->isoFormat('LL')]) }}<br>
                    {{ __('Certificate ID: :code', ['code' => $certificate->certificate_code]) }}<br>
                    {{ __('Registration number (learner ID): :id', ['id' => str_pad($certificate->user->id, 8, '0', STR_PAD_LEFT)]) }}
                </td>
                <td style="width:24%;text-align:center"><div class="seal" style="margin:0 auto"><span>ACADEXA</span></div></td>
                <td style="width:38%">
                    <div class="signature-line">
                        <strong>{{ $sig_name }}</strong><br>{{ $sig_title }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="verify">{{ __('Verify at: :url', ['url' => route('certificate.verify', $certificate->certificate_code)]) }}</div>

</body>
</html>
