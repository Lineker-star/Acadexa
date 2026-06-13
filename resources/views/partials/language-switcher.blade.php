<li class="nav-item dropdown">
    <a class="nav-link d-flex align-items-center gap-1" href="#" data-bs-toggle="dropdown" aria-label="Language">
        <span>{{ config('app.locale_flags.' . app()->getLocale(), '🌐') }}</span>
        <span class="d-none d-xl-inline" style="font-size:.85rem;">{{ strtoupper(app()->getLocale()) }}</span>
    </a>
    <ul class="dropdown-menu dropdown-menu-end">
        @foreach(config('app.supported_locales', ['en','fr','es','pt','zh','ar']) as $locale)
            <li>
                <form method="POST" action="{{ route('locale.switch') }}">
                    @csrf
                    <input type="hidden" name="locale" value="{{ $locale }}">
                    <button type="submit" class="dropdown-item {{ app()->getLocale() === $locale ? 'active fw-bold' : '' }}">
                        {{ config('app.locale_flags.' . $locale, '🌐') }}
                        {{ config('app.locale_names.' . $locale, $locale) }}
                    </button>
                </form>
            </li>
        @endforeach
    </ul>
</li>
