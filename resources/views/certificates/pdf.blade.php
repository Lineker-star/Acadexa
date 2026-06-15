<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: 'DejaVu Sans', Arial, sans-serif;
        width: 297mm;
        height: 210mm;
        overflow: hidden;
    }
    .certificate {
        width: 297mm;
        height: 210mm;
        position: relative;
        background-image: url('{{ public_path("images/certificate-bg.png") }}');
        background-size: 100% 100%;
        background-repeat: no-repeat;
    }
    .student-name {
        position: absolute;
        top: 108mm;
        left: 50%;
        transform: translateX(-50%);
        font-size: 28pt;
        font-style: italic;
        color: #C1440E;
        font-weight: bold;
        text-align: center;
        width: 200mm;
        font-family: 'DejaVu Serif', Georgia, serif;
    }
    .course-title {
        position: absolute;
        top: 128mm;
        left: 50%;
        transform: translateX(-50%);
        font-size: 16pt;
        font-weight: bold;
        color: #C1440E;
        text-align: center;
        width: 200mm;
        letter-spacing: 1px;
    }
    .cert-id {
        position: absolute;
        bottom: 20mm;
        left: 22mm;
        font-size: 7pt;
        color: #333;
        font-family: 'Courier New', monospace;
    }
    .cert-date-issued {
        position: absolute;
        bottom: 15mm;
        left: 22mm;
        font-size: 7pt;
        color: #333;
    }
    .learner-id {
        position: absolute;
        bottom: 20mm;
        right: 22mm;
        font-size: 7pt;
        color: #333;
        text-align: right;
    }
    .date-issue {
        position: absolute;
        bottom: 15mm;
        right: 22mm;
        font-size: 7pt;
        color: #333;
        text-align: right;
    }
    .verify-url {
        position: absolute;
        bottom: 8mm;
        left: 50%;
        transform: translateX(-50%);
        font-size: 6pt;
        color: #666;
        text-align: center;
        width: 200mm;
    }
</style>
</head>
<body>
<div class="certificate">
    <div class="student-name">{{ $certificate->user->name }}</div>
    <div class="course-title">{{ $certificate->course->title() }}</div>
    <div class="cert-id">Certificate ID: {{ $certificate->certificate_code }}</div>
    <div class="cert-date-issued">Date of Completion: {{ $certificate->issued_at->format('F d, Y') }}</div>
    <div class="learner-id">Matricule Number (Learner ID): {{ str_pad($certificate->user->id, 8, '0', STR_PAD_LEFT) }}</div>
    <div class="date-issue">Date of Issue: {{ $certificate->issued_at->format('F d, Y') }}</div>
    <div class="verify-url">Verify at: {{ config('app.url') }}/verify-certificate/{{ $certificate->certificate_code }}</div>
</div>
</body>
</html>
