<footer class="acadexxa-footer">
    <div class="container">
        <div class="row g-4">
            <!-- Brand -->
            <div class="col-md-4">
                <div class="footer-brand mb-3">ACADE<span>XA</span></div>
                <p style="font-size:.9rem;line-height:1.7;max-width:280px;">
                    Empowering World Innovators and Leaders for Global Impact — Now Online.<br>
                    Operated by ZTF University Institute, Bertoua, East Region, Cameroon.
                </p>
                <div class="social-icons mt-3">
                    <a href="{{ $siteSettings['facebook_url'] ?? '#' }}" target="_blank" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                    <a href="{{ $siteSettings['twitter_url'] ?? '#' }}" target="_blank" aria-label="Twitter/X"><i class="bi bi-twitter-x"></i></a>
                    <a href="{{ $siteSettings['youtube_url'] ?? '#' }}" target="_blank" aria-label="YouTube"><i class="bi bi-youtube"></i></a>
                    <a href="{{ $siteSettings['linkedin_url'] ?? '#' }}" target="_blank" aria-label="LinkedIn"><i class="bi bi-linkedin"></i></a>
                    <a href="{{ $siteSettings['instagram_url'] ?? '#' }}" target="_blank" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
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
                    <li><a href="{{ route('cms.page', 'faq') }}">FAQ</a></li>
                    <li><a href="{{ route('cms.page', 'terms') }}">Terms of Service</a></li>
                    <li><a href="{{ route('cms.page', 'privacy') }}">Privacy Policy</a></li>
                    <li><a href="{{ route('certificate.verify', 'ACADEXXA-XXXX-XXXX-' . date('Y')) }}">Verify Certificate</a></li>
                    <li><a href="{{ route('sitemap') }}">Sitemap</a></li>
                </ul>
            </div>

            <!-- Contact -->
            <div class="col-md-4">
                <h5>{{ __('navigation.contact_us') }}</h5>
                <ul class="list-unstyled" style="font-size:.9rem;">
                    <li class="mb-2"><i class="bi bi-geo-alt me-2" style="color:var(--secondary);"></i>ZTF University Institute, Bertoua, East Region, Cameroon</li>
                    <li class="mb-2"><i class="bi bi-envelope me-2" style="color:var(--secondary);"></i>
                        <a href="mailto:{{ $siteSettings['contact_email'] ?? 'info@acadexxa.com' }}">{{ $siteSettings['contact_email'] ?? 'info@acadexxa.com' }}</a>
                    </li>
                    <li class="mb-2"><i class="bi bi-telephone me-2" style="color:var(--secondary);"></i>{{ $siteSettings['contact_phone'] ?? '+237 000 000 000' }}</li>
                    <li><i class="bi bi-globe me-2" style="color:var(--secondary);"></i>
                        <a href="https://www.ztfuniversity.com" target="_blank">www.ztfuniversity.com</a>
                    </li>
                </ul>
            </div>
        </div>

        <hr class="footer-divider">

        <div class="footer-bottom">
            &copy; {{ date('Y') }} ACADEXXA — ZTF University Institute. All rights reserved. &nbsp;|&nbsp;
            Powered by ACADEXXA LMS
        </div>
    </div>
</footer>
