<?php

// Third-party services. The framework's defaults (mail providers…) are merged with this file.
return [

    // Brevo (transactional e-mails): API key from Brevo → SMTP & API → API keys.
    'brevo' => [
        'key' => env('BREVO_API_KEY'),
    ],

    // "Continue with Google" on the login and registration pages. Create the OAuth client in
    // Google Cloud Console → APIs & Services → Credentials, with the redirect URI below.
    'google' => [
        'client_id'     => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect'      => env('GOOGLE_REDIRECT_URI', rtrim((string) env('APP_URL'), '/') . '/auth/google/callback'),
    ],

];
