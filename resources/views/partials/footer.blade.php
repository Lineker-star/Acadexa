<footer class="acadexxa-footer">
    <div class="container">
        <div class="row g-4">
            <!-- Brand -->
            <div class="col-md-4">
                <div class="footer-brand mb-3">ACADE<span>XXA</span></div>
                <p style="font-size:.9rem;line-height:1.7;max-width:280px;">
                    {{ __('Empowering World Innovators and Leaders for Global Impact — Now Online.') }}<br>
                    {{ __('Operated by ZTF University Institute, Bertoua, East Region, Cameroon.') }}
                </p>
                <div class="social-icons mt-3">
                    <a href="{{ $siteSettings['facebook_url'] ?? '#' }}" target="_blank" aria-label="{{ __('Facebook') }}"><x-icon name="facebook" /></a>
                    <a href="{{ $siteSettings['twitter_url'] ?? '#' }}" target="_blank" aria-label="{{ __('Twitter/X') }}"><x-icon name="twitter-x" /></a>
                    <a href="{{ $siteSettings['youtube_url'] ?? '#' }}" target="_blank" aria-label="{{ __('YouTube') }}"><x-icon name="youtube" /></a>
                    <a href="{{ $siteSettings['linkedin_url'] ?? '#' }}" target="_blank" aria-label="{{ __('LinkedIn') }}"><x-icon name="linkedin" /></a>
                    <a href="{{ $siteSettings['instagram_url'] ?? '#' }}" target="_blank" aria-label="{{ __('Instagram') }}"><x-icon name="instagram" /></a>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="col-md-2">
                <h5>{{ __('navigation.quick_links') }}</h5>
                <ul class="list-unstyled" style="font-size:.9rem;">
                    <li><a href="{{ route('home') }}">{{ __('navigation.home') }}</a></li>
                    <li><a href="{{ route('courses.index') }}">{{ __('navigation.courses') }}</a></li>
                    <li><a href="{{ route('become-instructor') }}">{{ __('navigation.become_instructor') }}</a></li>
                    <li><a href="{{ route('cms.page', 'about') }}">{{ __('navigation.about') }}</a></li>
                    <li><a href="{{ route('contact') }}">{{ __('navigation.contact') }}</a></li>
                </ul>
            </div>

            <!-- Support -->
            <div class="col-md-2">
                <h5>{{ __('navigation.support') }}</h5>
                <ul class="list-unstyled" style="font-size:.9rem;">
                    <li><a href="{{ route('cms.page', 'faq') }}">{{ __('FAQ') }}</a></li>
                    <li><a href="{{ route('cms.page', 'terms') }}">{{ __('Terms of Service') }}</a></li>
                    <li><a href="{{ route('cms.page', 'privacy') }}">{{ __('Privacy Policy') }}</a></li>
                    <li><a href="{{ route('certificate.verify', 'ACADEXXA-XXXX-XXXX-' . date('Y')) }}">{{ __('Verify Certificate') }}</a></li>
                    <li><a href="{{ route('sitemap') }}">{{ __('Sitemap') }}</a></li>
                </ul>
            </div>

            <!-- Contact -->
            <div class="col-md-4">
                <h5>{{ __('navigation.contact_us') }}</h5>
                <ul class="list-unstyled" style="font-size:.9rem;">
                    <li class="mb-2"><x-icon name="geo-alt" class="me-2" style="color:var(--secondary);" />{{ __('ZTF University Institute, Bertoua, East Region, Cameroon') }}</li>
                    <li class="mb-2"><x-icon name="envelope" class="me-2" style="color:var(--secondary);" />
                        <a href="mailto:{{ $siteSettings['contact_email'] ?? 'info@acadexxa.com' }}">{{ $siteSettings['contact_email'] ?? 'info@acadexxa.com' }}</a>
                    </li>
                    <li class="mb-2"><x-icon name="telephone" class="me-2" style="color:var(--secondary);" />{{ $siteSettings['contact_phone'] ?? '+237 000 000 000' }}</li>
                    <li><x-icon name="globe" class="me-2" style="color:var(--secondary);" />
                        <a href="https://www.ztfuniversity.com" target="_blank">www.ztfuniversity.com</a>
                    </li>
                </ul>
            </div>
        </div>

        <hr class="footer-divider">

        <div class="footer-bottom">
            &copy; {{ date('Y') }} {{ __(':site — ZTF University Institute. All rights reserved.', ['site' => $siteSettings['site_name'] ?? 'ACADEXXA']) }}
        </div>
    </div>
</footer>
