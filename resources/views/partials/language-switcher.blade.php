<li class="nav-item dropdown">
    <a class="nav-link d-flex align-items-center gap-1" href="#" data-bs-toggle="dropdown" aria-label="{{ __('Language') }}">
        <x-icon name="globe2" />
        <span style="font-size:.85rem;">{{ strtoupper(app()->getLocale()) }}</span>
    </a>
    <ul class="dropdown-menu dropdown-menu-end">
        @foreach(config('app.supported_locales') as $locale)
            <li>
                <form method="POST" action="{{ route('locale.switch') }}">
                    @csrf
                    <input type="hidden" name="locale" value="{{ $locale }}">
                    <button type="submit" class="dropdown-item d-flex align-items-center gap-2 {{ app()->getLocale() === $locale ? 'active fw-bold' : '' }}" lang="{{ $locale }}">
                        <span class="badge bg-light text-dark border" style="min-width:2.2em">{{ strtoupper($locale) }}</span>
                        {{ config('app.locale_names.' . $locale, $locale) }}
                    </button>
                </form>
            </li>
        @endforeach
    </ul>
</li>
