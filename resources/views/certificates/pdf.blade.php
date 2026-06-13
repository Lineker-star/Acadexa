<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: 'DejaVu Sans', Arial, sans-serif;
        background: #ffffff;
        width: 297mm;
        height: 210mm;
        overflow: hidden;
    }

    .certificate {
        width: 297mm;
        height: 210mm;
        position: relative;
        background: linear-gradient(135deg, #0A2A5E 0%, #0d3575 50%, #0A2A5E 100%);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 0;
    }

    /* Gold border frame */
    .border-frame {
        position: absolute;
        top: 10mm;
        left: 10mm;
        right: 10mm;
        bottom: 10mm;
        border: 3px solid #C1440E;
        border-radius: 4px;
    }
    .border-frame-inner {
        position: absolute;
        top: 12mm;
        left: 12mm;
        right: 12mm;
        bottom: 12mm;
        border: 1px solid rgba(193,68,14,0.5);
        border-radius: 2px;
    }

    .content {
        position: relative;
        z-index: 2;
        text-align: center;
        color: white;
        width: 250mm;
    }

    .institution {
        font-size: 11pt;
        letter-spacing: 3px;
        text-transform: uppercase;
        color: #C1440E;
        font-weight: bold;
        margin-bottom: 2mm;
    }

    .platform {
        font-size: 8pt;
        letter-spacing: 2px;
        color: rgba(255,255,255,0.7);
        margin-bottom: 6mm;
    }

    .cert-title {
        font-size: 28pt;
        font-weight: bold;
        letter-spacing: 6px;
        text-transform: uppercase;
        color: white;
        border-bottom: 2px solid #C1440E;
        padding-bottom: 4mm;
        margin-bottom: 8mm;
    }

    .this-certifies {
        font-size: 10pt;
        color: rgba(255,255,255,0.75);
        letter-spacing: 1px;
        margin-bottom: 3mm;
    }

    .student-name {
        font-size: 24pt;
        font-weight: bold;
        color: white;
        font-style: italic;
        margin-bottom: 3mm;
        border-bottom: 1px solid rgba(193,68,14,0.6);
        padding-bottom: 2mm;
        display: inline-block;
        min-width: 180mm;
    }

    .has-completed {
        font-size: 10pt;
        color: rgba(255,255,255,0.75);
        margin-bottom: 3mm;
    }

    .course-name {
        font-size: 15pt;
        font-weight: bold;
        color: #C1440E;
        font-style: italic;
        margin-bottom: 2mm;
    }

    .cert-desc {
        font-size: 8pt;
        color: rgba(255,255,255,0.6);
        max-width: 200mm;
        margin: 0 auto 8mm;
        line-height: 1.5;
    }

    .footer {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        width: 230mm;
        margin-top: 6mm;
    }

    .sig-block {
        text-align: center;
        min-width: 70mm;
    }

    .sig-line {
        border-top: 1px solid rgba(255,255,255,0.4);
        padding-top: 2mm;
    }

    .sig-name {
        font-size: 9pt;
        font-weight: bold;
        color: white;
    }

    .sig-title {
        font-size: 7pt;
        color: rgba(255,255,255,0.6);
    }

    .cert-code-block {
        text-align: center;
    }

    .cert-code {
        font-size: 7pt;
        color: rgba(255,255,255,0.5);
        letter-spacing: 1px;
        font-family: 'Courier New', monospace;
    }

    .issue-date {
        font-size: 8pt;
        color: rgba(255,255,255,0.6);
    }

    /* Corner decorations */
    .corner {
        position: absolute;
        width: 15mm;
        height: 15mm;
        border-color: #C1440E;
        border-style: solid;
        opacity: 0.8;
    }
    .corner-tl { top: 14mm; left: 14mm; border-width: 2px 0 0 2px; }
    .corner-tr { top: 14mm; right: 14mm; border-width: 2px 2px 0 0; }
    .corner-bl { bottom: 14mm; left: 14mm; border-width: 0 0 2px 2px; }
    .corner-br { bottom: 14mm; right: 14mm; border-width: 0 2px 2px 0; }
</style>
</head>
<body>
<div class="certificate">
    <div class="border-frame"></div>
    <div class="border-frame-inner"></div>
    <div class="corner corner-tl"></div>
    <div class="corner corner-tr"></div>
    <div class="corner corner-bl"></div>
    <div class="corner corner-br"></div>

    <div class="content">
        <div class="institution">{{ $settings['cert_institution'] ?? 'ZTF University Institute' }}</div>
        <div class="platform">{{ $settings['cert_subheading'] ?? 'ACADEXA Learning Management System' }}</div>

        <div class="cert-title">Certificate of Completion</div>

        <div class="this-certifies">This is to certify that</div>

        <div class="student-name">{{ $certificate->user->name }}</div>

        <div class="has-completed">has successfully completed the course</div>

        <div class="course-name">{{ $certificate->course->title() }}</div>

        <div class="cert-desc">
            {{ $settings['cert_description'] ?? 'This certificate is awarded in recognition of the successful completion of the above-mentioned course with dedication and excellence.' }}
        </div>

        <div class="footer">
            <div class="sig-block">
                <div class="sig-line">
                    <div class="sig-name">{{ $settings['cert_sig_name'] ?? 'Prof. Emmanuel ZANG' }}</div>
                    <div class="sig-title">{{ $settings['cert_sig_title'] ?? 'Director, ZTF University Institute' }}</div>
                </div>
            </div>

            <div class="cert-code-block">
                <div class="issue-date">Issued: {{ $certificate->issued_at->format('F d, Y') }}</div>
                <div class="cert-code">{{ $certificate->certificate_code }}</div>
                <div class="cert-code" style="font-size:6pt;opacity:.6;">Verify at: {{ config('app.url') }}/verify-certificate/{{ $certificate->certificate_code }}</div>
            </div>

            <div class="sig-block">
                <div class="sig-line">
                    <div class="sig-name">{{ $certificate->course->instructor?->name }}</div>
                    <div class="sig-title">Course Instructor</div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
