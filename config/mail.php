<?php

// Mail settings. The framework's defaults (smtp, log, array… mailers) are merged with this file.
return [

    // Brevo is used as soon as an API key exists, whatever MAIL_MAILER says: a leftover "log" or "smtp"
    // must not keep the e-mails away from Brevo. The key comes from BREVO_API_KEY or from
    // Admin → Settings (see App\Support\Mailing). Without a key: MAIL_MAILER, or the log file.
    'default' => env('BREVO_API_KEY') ? 'brevo' : env('MAIL_MAILER', 'log'),

    'mailers' => [
        // Brevo transactional API (see App\Mail\BrevoTransport).
        'brevo' => ['transport' => 'brevo'],
    ],

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'noreply@acadexxa.com'),
        'name'    => env('MAIL_FROM_NAME', env('APP_NAME', 'ACADEXXA')),
    ],

    // E-mail design: resources/views/vendor/mail/html/themes/acadexxa.css
    'markdown' => [
        'theme' => 'acadexxa',
        'paths' => [resource_path('views/vendor/mail')],
    ],

];
