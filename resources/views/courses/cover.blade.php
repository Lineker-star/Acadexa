{{-- Generated course cover (see CourseCoverController). Served as image/svg+xml. --}}
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1280 720" width="1280" height="720">
    <defs>
        <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0" stop-color="{{ $from }}"/>
            <stop offset="1" stop-color="{{ $to }}"/>
        </linearGradient>
        <pattern id="kente" width="96" height="28" patternUnits="userSpaceOnUse">
            <rect width="96" height="28" fill="#C1440E"/>
            <rect width="32" height="28" fill="#F5B041"/>
            <rect x="32" width="16" height="28" fill="#1E8449"/>
            <rect x="48" width="16" height="28" fill="#0A2A5E"/>
            <rect x="4" y="6" width="24" height="4" fill="#0A2A5E"/>
            <rect x="4" y="18" width="24" height="4" fill="#1E8449"/>
        </pattern>
    </defs>
    <rect width="1280" height="720" fill="url(#bg)"/>
    <circle cx="1090" cy="170" r="300" fill="#FFFFFF" fill-opacity=".06"/>
    <circle cx="1090" cy="170" r="190" fill="#FFFFFF" fill-opacity=".05"/>
    <g transform="translate(930 40) scale(20)" fill="#FFFFFF" fill-opacity=".22">{!! $icon !!}</g>
    <rect x="0" y="692" width="1280" height="28" fill="url(#kente)"/>

    @if($category)
        <text x="{{ $rtl ? 1200 : 80 }}" y="{{ 640 - count($lines) * 92 - 40 }}" @if($rtl) text-anchor="end" direction="rtl" @endif
              font-family="Segoe UI, Roboto, Helvetica, Arial, sans-serif" font-size="30" font-weight="600" letter-spacing="3" fill="#F5B041">{{ mb_strtoupper($category) }}</text>
    @endif
    @foreach($lines as $i => $line)
        <text x="{{ $rtl ? 1200 : 80 }}" y="{{ 640 - (count($lines) - 1 - $i) * 92 - 30 }}" @if($rtl) text-anchor="end" direction="rtl" @endif
              font-family="Segoe UI, Roboto, Helvetica, Arial, 'Noto Sans', 'Microsoft YaHei', sans-serif" font-size="76" font-weight="700" fill="#FFFFFF">{{ $line }}</text>
    @endforeach
    <text x="{{ $rtl ? 80 : 1200 }}" y="660" text-anchor="{{ $rtl ? 'start' : 'end' }}" font-family="Segoe UI, Roboto, Helvetica, Arial, sans-serif"
          font-size="26" font-weight="800" letter-spacing="4" fill="#FFFFFF" fill-opacity=".75">ACADEXA</text>
</svg>
