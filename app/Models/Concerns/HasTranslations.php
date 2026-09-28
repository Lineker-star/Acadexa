<?php

namespace App\Models\Concerns;

/**
 * Resolves the best translation row for a locale.
 * Fallback order: requested locale -> French -> English -> any available.
 * French comes first because ZTF-UI content is authored primarily in French.
 */
trait HasTranslations
{
    public function translationFor(?string $locale = null)
    {
        $translations = $this->translations;
        $locale = $locale ?? app()->getLocale();

        foreach (array_unique([$locale, 'fr', 'en']) as $candidate) {
            $trans = $translations->firstWhere('locale', $candidate);
            if ($trans) {
                return $trans;
            }
        }

        return $translations->first();
    }

    /** Exact translation for a locale, without fallback (used by editors). */
    public function translationExact(string $locale)
    {
        return $this->translations->firstWhere('locale', $locale);
    }
}
