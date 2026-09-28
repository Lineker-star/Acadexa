@extends('layouts.app')
@section('title', __('navigation.notifications'))

@section('content')
<div class="container py-5" style="max-width:820px">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="h4 fw-bold mb-0">{{ __('navigation.notifications') }}</h1>
        <form method="POST" action="{{ route('notifications.read-all') }}">
            @csrf
            <button class="btn btn-sm btn-outline-primary">{{ __('lms.mark_all_read') }}</button>
        </form>
    </div>

    <form method="POST" action="{{ route('notifications.preferences') }}" class="bg-white rounded-xl shadow-brand p-3 mb-3 d-flex align-items-center gap-3">
        @csrf
        <div class="form-check form-switch mb-0">
            <input class="form-check-input" type="checkbox" role="switch" id="emailNotif" name="email_notifications" value="1" @checked(auth()->user()->email_notifications) onchange="this.form.submit()">
            <label class="form-check-label" for="emailNotif">{{ __('lms.email_notifications') }}</label>
        </div>
        <noscript><button class="btn btn-sm btn-light">{{ __('lms.save') }}</button></noscript>
    </form>

    <div class="bg-white rounded-xl shadow-brand">
        @forelse($notifications as $n)
            <a href="{{ route('notifications.open', $n->id) }}" class="notif-item {{ $n->read_at ? '' : 'unread' }}">
                <x-icon :name="$n->data['icon'] ?? 'bi-bell'" />
                <span>
                    <span class="d-block fw-semibold">{{ $n->data['title'] ?? '' }}</span>
                    <span class="d-block">{{ $n->data['message'] ?? '' }}</span>
                    <span class="d-block notif-time">{{ $n->created_at->diffForHumans() }}</span>
                </span>
            </a>
        @empty
            <div class="text-center text-muted py-5">{{ __('navigation.no_notifications') }}</div>
        @endforelse
    </div>
    <div class="mt-3">{{ $notifications->links() }}</div>
</div>
@endsection
