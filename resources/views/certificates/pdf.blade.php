<!DOCTYPE html>
{{-- Certificate PDF (DomPDF, A4 landscape), in the colours of the platform (navy / orange).
     Layout: ACADEXXA logo at the top, title, learner's name in script, course, then date – seal –
     signature, and at the bottom the institute ACADEXXA belongs to. Texts come from the admin's
     "Certificate template" settings; the decoration is an inline SVG (no image file needed). --}}
@php
    $navy   = \App\Models\Setting::get('cert_bg_color', '#0A2A5E') ?: '#0A2A5E';
    $orange = '#C1440E';
    $soft   = '#F3C7B1';   // light orange for the ribbons
    $institution = \App\Models\Setting::get('cert_institution') ?: 'Institut de Formation Professionnelle ZTF';
    $description = \App\Models\Setting::get('cert_description');

    // Logo: the one uploaded in the admin, otherwise the ACADEXXA logo. DomPDF needs GD for PNG files:
    // without it the name is written instead, so that issuing a certificate never fails.
    $logoPath = \App\Models\Setting::get('cert_logo');
    $logoFile = $logoPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($logoPath) ? \Illuminate\Support\Facades\Storage::disk('public')->path($logoPath) : public_path('images/logo.png');
    $logo = is_file($logoFile) && extension_loaded('gd')
        ? 'data:' . (mime_content_type($logoFile) ?: 'image/png') . ';base64,' . base64_encode(file_get_contents($logoFile))
        : null;

    $course = $certificate->course;
    $finalScore = $course->finalExam
        ? $course->finalExam->standardAttempts()->where('user_id', $certificate->user_id)->where('passed', true)->max('score')
        : null;
    $scoreText = $finalScore !== null ? rtrim(rtrim(number_format((float) $finalScore, 1, '.', ''), '0'), '.') : null;

    // Seal: a scalloped disc (36 points).
    $seal = [];
    for ($i = 0; $i < 72; $i++) {
        $r = $i % 2 ? 43 : 48;
        $a = M_PI * $i / 36;
        $seal[] = round(561 + $r * cos($a), 1) . ',' . round(668 + $r * sin($a), 1);
    }

    // Decoration for a 1122 × 794 page (A4 landscape at 96 dpi).
    $decor = '<svg xmlns="http://www.w3.org/2000/svg" width="1122" height="794" viewBox="0 0 1122 794">'
        // top-left blocks
        . '<rect x="-190" y="-150" width="330" height="330" rx="16" fill="' . $navy . '" transform="rotate(45 -25 15)"/>'
        . '<rect x="-52" y="318" width="112" height="112" rx="10" fill="' . $orange . '" transform="rotate(45 4 374)"/>'
        . '<rect x="-70" y="408" width="112" height="112" rx="10" fill="' . $navy . '" transform="rotate(45 -14 464)"/>'
        . '<rect x="110" y="-70" width="84" height="84" rx="8" fill="' . $orange . '" transform="rotate(45 152 -28)"/>'
        // bottom-right blocks
        . '<rect x="982" y="614" width="330" height="330" rx="16" fill="' . $navy . '" transform="rotate(45 1147 779)"/>'
        . '<rect x="1062" y="364" width="112" height="112" rx="10" fill="' . $orange . '" transform="rotate(45 1118 420)"/>'
        . '<rect x="1080" y="274" width="112" height="112" rx="10" fill="' . $navy . '" transform="rotate(45 1136 330)"/>'
        . '<rect x="928" y="780" width="84" height="84" rx="8" fill="' . $orange . '" transform="rotate(45 970 822)"/>'
        // ribbons (top-right, bottom-left)
        . '<path d="M860 -20 C 900 60, 1010 70, 1060 20 C 1100 -20, 1040 -60, 1000 -10 C 960 50, 1040 130, 1140 90" fill="none" stroke="' . $soft . '" stroke-width="13" stroke-linecap="round"/>'
        . '<path d="M940 -30 C 990 20, 1090 10, 1140 -30" fill="none" stroke="' . $soft . '" stroke-width="9" stroke-linecap="round"/>'
        . '<path d="M-20 700 C 60 660, 150 700, 120 760 C 100 800, 30 790, 50 740 C 80 680, 200 720, 260 814" fill="none" stroke="' . $soft . '" stroke-width="13" stroke-linecap="round"/>'
        . '<path d="M-30 760 C 30 730, 90 760, 120 814" fill="none" stroke="' . $soft . '" stroke-width="9" stroke-linecap="round"/>'
        // seal
        . '<polygon points="' . implode(' ', $seal) . '" fill="' . $orange . '"/>'
        . '<circle cx="561" cy="668" r="36" fill="none" stroke="#FFFFFF" stroke-width="1.6"/>'
        . '<circle cx="561" cy="668" r="30" fill="' . $navy . '"/>'
        . '<path d="M546 668 L557 680 L578 656" fill="none" stroke="#FFFFFF" stroke-width="5.5" stroke-linecap="round" stroke-linejoin="round"/>'
        . '</svg>';
@endphp
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 0; }
    @font-face { font-family: 'Great Vibes'; font-style: normal; font-weight: normal; src: url('{{ resource_path('fonts/GreatVibes-Regular.ttf') }}') format('truetype'); }
    * { margin: 0; padding: 0; }
    body { font-family: 'DejaVu Sans', sans-serif; color: #374151; width: 297mm; height: 210mm; }
    .decor { position: absolute; top: 0; left: 0; width: 297mm; height: 210mm; }
    .content { position: absolute; top: 11mm; left: 40mm; right: 40mm; text-align: center; }
    .logo { height: 30mm; }
    .brand { font-size: 22pt; font-weight: bold; color: {{ $navy }}; letter-spacing: 4px; padding-top: 8mm; }
    .title { font-size: 40pt; font-weight: bold; color: {{ $orange }}; letter-spacing: 4px; line-height: 1; margin-top: 1mm; }
    .subtitle { font-size: 17pt; color: {{ $navy }}; letter-spacing: 5px; margin-top: 1.5mm; }
    .awarded { font-size: 10.5pt; color: {{ $navy }}; margin-top: 6mm; }
    .name { font-family: 'Great Vibes', 'DejaVu Serif', serif; font-size: 38pt; color: {{ $orange }}; line-height: 1.1; margin-top: 1mm; }
    .name-line { width: 150mm; margin: 1mm auto 0; border-top: 0.5mm dashed {{ $orange }}; }
    .for { font-size: 10.5pt; color: {{ $navy }}; margin-top: 4mm; }
    .course { font-size: 15pt; font-weight: bold; color: {{ $navy }}; margin-top: 1.5mm; }
    .meta { font-size: 9pt; color: #4B5563; margin-top: 1.5mm; }
    .description { font-size: 8.5pt; color: #6B7280; margin: 1.5mm auto 0; width: 170mm; }
    .sign { position: absolute; left: 52mm; right: 52mm; top: 165.5mm; }
    .sign table { width: 100%; border-collapse: collapse; }
    .sign td { vertical-align: top; text-align: center; font-size: 9pt; color: {{ $navy }}; }
    .sign .line { border-top: 0.45mm dashed {{ $navy }}; width: 52mm; margin: 0 auto 1.5mm; }
    .sign .value { font-weight: bold; font-size: 9.5pt; height: 7mm; }
    .sign .label { font-size: 8pt; color: #6B7280; }
    .footer { position: absolute; left: 60mm; right: 60mm; top: 190mm; text-align: center; }
    .institution { font-size: 10.5pt; font-weight: bold; color: {{ $navy }}; letter-spacing: 1px; }
    .part-of { font-size: 7.5pt; color: #4B5563; margin-top: 0.6mm; }
    .verify { font-size: 6pt; color: #6B7280; margin-top: 0.8mm; }
</style>
</head>
<body>
    <img class="decor" src="data:image/svg+xml;base64,{{ base64_encode($decor) }}" alt="">

    <div class="content">
        @if($logo)
            <img src="{{ $logo }}" class="logo" alt="">
        @else
            <div class="brand">ACADEXXA</div>
        @endif

        <div class="title">{{ __('learn.cert_title') }}</div>
        <div class="subtitle">{{ __('learn.cert_subtitle') }}</div>

        <div class="awarded">{{ __('learn.cert_awarded_to') }}</div>
        <div class="name">{{ $certificate->user->name }}</div>
        <div class="name-line"></div>

        <div class="for">{{ __('learn.cert_for_course') }}</div>
        <div class="course">{{ $course->title() }}</div>
        <div class="meta">
            {{ __('learn.cert_hours', ['hours' => $course->hoursLabel()]) }}
            @if($scoreText !== null) &nbsp;·&nbsp; {{ __('learn.cert_final_score', ['score' => $scoreText]) }} @endif
        </div>
        @if($description)<div class="description">{{ $description }}</div>@endif
    </div>

    <div class="sign">
        <table>
            <tr>
                <td style="width:36%">
                    <div class="value">{{ $certificate->issued_at->isoFormat('LL') }}</div>
                    <div class="line"></div>
                    <div class="label">{{ __('learn.cert_issued_on') }}</div>
                </td>
                <td style="width:28%"></td>
                <td style="width:36%">
                    <div class="value">{{ $sig_name }}</div>
                    <div class="line"></div>
                    <div class="label">{{ $sig_title }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <div class="institution">{{ $institution }}</div>
        <div class="part-of">{{ __('learn.cert_part_of', ['institution' => $institution]) }}</div>
        <div class="verify">{{ __('Certificate ID: :code', ['code' => $certificate->certificate_code]) }} &nbsp;·&nbsp; {{ __('Verify at: :url', ['url' => route('certificate.verify', $certificate->certificate_code)]) }}</div>
    </div>
</body>
</html>
