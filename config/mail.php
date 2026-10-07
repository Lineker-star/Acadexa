<?php

// Mail settings. The framework's defaults (smtp, log, array… mailers) are merged with this file.
return [

    // Brevo is used as soon as its API key is present; otherwise e-mails go to the log file.
    'default' => env('MAIL_MAILER', env('BREVO_API_KEY') ? 'brevo' : 'log'),

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
