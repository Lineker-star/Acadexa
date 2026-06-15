<nav class="navbar navbar-expand-lg acadexxa-navbar">
    <div class="container">
        <!-- Brand -->
        <a class="navbar-brand d-flex align-items-center gap-2" href="{{ route('home') }}">
        <img src="{{ asset('images/logo.png') }}" alt="ACADEXXA" height="36">
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <!-- Search Bar -->
            <div class="mx-auto position-relative d-none d-lg-block" style="width:340px;">
                <form action="{{ route('search') }}" method="GET">
                    <div class="input-group">
                        <input type="text" name="q" id="searchInput" class="form-control form-control-sm"
                               placeholder="{{ __('navigation.search') }}..." autocomplete="off"
                               value="{{ request('q') }}">
                        <button class="btn btn-secondary btn-sm" type="submit"><i class="bi bi-search"></i></button>
                    </div>
                    <div id="searchSuggestions" class="dropdown-menu d-none w-100 shadow"
                         style="top:100%;position:absolute;z-index:1050;"></div>
                </form>
            </div>

            <!-- Right Nav -->
            <ul class="navbar-nav ms-auto align-items-center gap-1">
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('courses.index') }}">
                        <i class="bi bi-grid-3x3-gap d-lg-none"></i> {{ __('navigation.courses') }}
                    </a>
                </li>

                @guest
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('login') }}">{{ __('navigation.login') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-secondary btn-sm ms-1" href="{{ route('register') }}">{{ __('navigation.register') }}</a>
                    </li>
                @else
                    <!-- Notifications -->
                    <li class="nav-item dropdown">
                        <a class="nav-link position-relative" href="#" id="notifBtn" data-bs-toggle="dropdown">
                            <i class="bi bi-bell fs-5"></i>
                            @php $unread = auth()->user()->unreadNotifications()->count(); @endphp
                            @if($unread)
                                <span class="badge bg-danger position-absolute top-0 end-0" style="font-size:.6rem;">{{ $unread }}</span>
                            @endif
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" style="min-width:300px;">
                            <li><h6 class="dropdown-header">{{ __('navigation.notifications') }}</h6></li>
                            @forelse(auth()->user()->unreadNotifications()->take(5)->get() as $notif)
                                <li><a class="dropdown-item small" href="#">{{ $notif->data['message'] ?? 'New notification' }}</a></li>
                            @empty
                                <li><span class="dropdown-item text-muted small">{{ __('navigation.no_notifications') }}</span></li>
                            @endforelse
                        </ul>
                    </li>

                    <!-- User Menu -->
                    <li class="nav-item dropdown">
                        <a class="nav-link d-flex align-items-center gap-2" href="#" data-bs-toggle="dropdown">
                            <img src="{{ auth()->user()->avatarUrl() }}" class="rounded-circle" width="30" height="30" alt="avatar">
                            <span class="d-none d-xl-inline">{{ Str::limit(auth()->user()->name, 15) }}</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            @if(auth()->user()->isAdmin())
                                <li><a class="dropdown-item" href="{{ route('admin.dashboard') }}"><i class="bi bi-shield-check text-primary me-2"></i>Admin Panel</a></li>
                                <li><hr class="dropdown-divider"></li>
                            @endif
                            @if(auth()->user()->isInstructor())
                                <li><a class="dropdown-item" href="{{ route('instructor.dashboard') }}"><i class="bi bi-mortarboard text-primary me-2"></i>Instructor Portal</a></li>
                                <li><hr class="dropdown-divider"></li>
                            @endif
                            <li><a class="dropdown-item" href="{{ route('dashboard') }}"><i class="bi bi-columns-gap me-2"></i>{{ __('navigation.dashboard') }}</a></li>
                            <li><a class="dropdown-item" href="{{ route('student.courses.index') }}"><i class="bi bi-play-circle me-2"></i>{{ __('navigation.my_courses') }}</a></li>
                            <li><a class="dropdown-item" href="{{ route('student.certificates.index') }}"><i class="bi bi-award me-2"></i>{{ __('navigation.certificates') }}</a></li>
                            <li><a class="dropdown-item" href="{{ route('student.profile.edit') }}"><i class="bi bi-person me-2"></i>{{ __('navigation.profile') }}</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>{{ __('navigation.logout') }}</button>
                                </form>
                            </li>
                        </ul>
                    </li>
                @endguest

                <!-- Language Switcher -->
                @include('partials.language-switcher')
            </ul>
        </div>
    </div>
</nav>
