@php
    $unread = auth()->user()->unreadNotifications()->count();
    $latest = auth()->user()->notifications()->take(6)->get();
@endphp
<li class="nav-item dropdown">
    <a class="nav-link position-relative" href="#" id="notifBtn" data-bs-toggle="dropdown" aria-label="{{ __('navigation.notifications') }}">
        <x-icon name="bell" class="fs-5" />
        @if($unread)
            <span class="badge bg-danger position-absolute top-0 end-0" style="font-size:.6rem;">{{ $unread > 99 ? '99+' : $unread }}</span>
        @endif
    </a>
    <div class="dropdown-menu dropdown-menu-end p-0 notif-menu">
        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
            <strong class="small">{{ __('navigation.notifications') }}</strong>
            @if($unread)
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                <button class="btn btn-link btn-sm p-0 small">{{ __('lms.mark_all_read') }}</button>
            </form>
            @endif
        </div>
        <div class="notif-list">
            @forelse($latest as $notif)
                <a class="notif-item {{ $notif->read_at ? '' : 'unread' }}" href="{{ route('notifications.open', $notif->id) }}">
                    <x-icon :name="$notif->data['icon'] ?? 'bi-bell'" />
                    <span>
                        <span class="d-block fw-semibold">{{ $notif->data['title'] ?? __('navigation.notifications') }}</span>
                        <span class="d-block text-muted">{{ \Illuminate\Support\Str::limit($notif->data['message'] ?? '', 90) }}</span>
                        <span class="d-block notif-time">{{ $notif->created_at->diffForHumans() }}</span>
                    </span>
                </a>
            @empty
                <div class="text-muted small text-center py-4">{{ __('navigation.no_notifications') }}</div>
            @endforelse
        </div>
        <a href="{{ route('notifications.index') }}" class="d-block text-center small py-2 border-top">{{ __('lms.see_all') }}</a>
    </div>
</li>
