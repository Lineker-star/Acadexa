<nav class="navbar navbar-expand-lg acadexxa-navbar">
    <div class="container">
        <!-- Brand -->
        <a class="navbar-brand d-flex align-items-center gap-2" href="{{ route('home') }}">
        <img src="{{ asset('images/logo.png') }}" alt="ACADEXA" height="36">
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
                        <button class="btn btn-secondary btn-sm" type="submit"><x-icon name="search" /></button>
                    </div>
                    <div id="searchSuggestions" class="dropdown-menu d-none w-100 shadow"
                         style="top:100%;position:absolute;z-index:1050;"></div>
                </form>
            </div>

            <!-- Right Nav -->
            <ul class="navbar-nav ms-auto align-items-center gap-1">
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('courses.index') }}">
                        <x-icon name="grid-3x3-gap" class="d-lg-none" /> {{ __('navigation.courses') }}
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
                    <!-- Install app (shown by pwa.js when the browser allows it) -->
                    <li class="nav-item" data-pwa-install hidden>
                        <button type="button" class="btn btn-sm btn-outline-primary">
                            <x-icon name="download" class="me-1" />{{ __('lms.install_app') }}
                        </button>
                    </li>

                    <!-- Messages -->
                    <li class="nav-item">
                        @php
                            $unreadMessages = \App\Models\Message::whereNull('read_at')
                                ->where('user_id', '!=', auth()->id())
                                ->whereIn('conversation_id', \App\Models\Conversation::forUser(auth()->user())->select('id'))
                                ->count();
                        @endphp
                        <a class="nav-link position-relative" href="{{ route('messages.index') }}" title="{{ __('lms.messages') }}">
                            <x-icon name="envelope" class="fs-5" />
                            @if($unreadMessages)
                                <span class="badge bg-danger position-absolute top-0 end-0" style="font-size:.6rem;">{{ $unreadMessages }}</span>
                            @endif
                        </a>
                    </li>

                    <!-- Notifications -->
                    @include('partials.notification-bell')

                    <!-- User Menu -->
                    <li class="nav-item dropdown">
                        <a class="nav-link d-flex align-items-center gap-2" href="#" data-bs-toggle="dropdown">
                            <img src="{{ auth()->user()->avatarUrl() }}" class="rounded-circle" width="30" height="30" alt="{{ __('avatar') }}">
                            <span class="d-none d-xl-inline">{{ Str::limit(auth()->user()->name, 15) }}</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            @if(auth()->user()->isAdmin())
                                <li><a class="dropdown-item" href="{{ route('admin.dashboard') }}"><x-icon name="shield-check" class="text-primary me-2" />{{ __('Admin Panel') }}</a></li>
                                <li><hr class="dropdown-divider"></li>
                            @endif
                            @if(auth()->user()->isInstructor())
                                <li><a class="dropdown-item" href="{{ route('instructor.dashboard') }}"><x-icon name="mortarboard" class="text-primary me-2" />{{ __('Instructor Portal') }}</a></li>
                                <li><hr class="dropdown-divider"></li>
                            @endif
                            <li><a class="dropdown-item" href="{{ route('dashboard') }}"><x-icon name="columns-gap" class="me-2" />{{ __('navigation.dashboard') }}</a></li>
                            <li><a class="dropdown-item" href="{{ route('student.courses.index') }}"><x-icon name="play-circle" class="me-2" />{{ __('navigation.my_courses') }}</a></li>
                            <li><a class="dropdown-item" href="{{ route('student.library.index') }}"><x-icon name="book" class="me-2" />{{ __('learn.my_library') }}</a></li>
                            <li><a class="dropdown-item" href="{{ route('offline') }}"><x-icon name="cloud-slash" class="me-2" />{{ __('lms.offline_courses') }}</a></li>
                            <li><a class="dropdown-item" href="{{ route('messages.index') }}"><x-icon name="envelope" class="me-2" />{{ __('lms.messages') }}</a></li>
                            <li><a class="dropdown-item" href="{{ route('student.certificates.index') }}"><x-icon name="award" class="me-2" />{{ __('navigation.certificates') }}</a></li>
                            <li><a class="dropdown-item" href="{{ route('student.profile.edit') }}"><x-icon name="person" class="me-2" />{{ __('navigation.profile') }}</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button class="dropdown-item text-danger"><x-icon name="box-arrow-right" class="me-2" />{{ __('navigation.logout') }}</button>
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
