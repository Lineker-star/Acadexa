<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function switch(Request $request)
    {
        $supported = config('app.supported_locales', ['en', 'fr', 'es', 'pt', 'zh', 'ar']);
        $locale    = $request->input('locale', 'en');

        if (! in_array($locale, $supported)) {
            $locale = 'en';
        }

        session(['locale' => $locale]);

        if ($request->user()) {
            $request->user()->update(['preferred_language' => $locale]);
        }

        return redirect()->back()->withHeaders([
            'Vary' => 'Accept-Language',
        ]);
    }
}
