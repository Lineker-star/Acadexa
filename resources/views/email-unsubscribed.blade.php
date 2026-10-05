@extends('layouts.app')
@section('title', __('learn.unsubscribed_title'))
@section('robots', 'noindex, nofollow')

@section('content')
<div class="container py-5" style="max-width:620px">
    <div class="bg-white rounded-xl shadow-brand p-4 p-md-5 text-center">
        <x-icon name="envelope-check" style="font-size:3rem;color:var(--bs-primary)" />
        <h1 class="h4 fw-bold mt-3">{{ __('learn.unsubscribed_title') }}</h1>
        <p class="text-muted">{{ __('learn.unsubscribed_' . $type, ['email' => $email]) }}</p>
        <p class="small text-muted">{{ __('learn.unsubscribed_note') }}</p>
        <a href="{{ auth()->check() ? route('student.profile.edit') : route('login') }}" class="btn btn-primary">{{ __('learn.mail_manage') }}</a>
    </div>
</div>
@endsection
