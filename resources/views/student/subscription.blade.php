@extends('layouts.app')
@section('title', __('Subscription'))

@section('content')
<div class="py-5" style="min-height:60vh;background:var(--light);">
    <div class="container text-center" style="max-width:600px;">
        <div style="font-size:5rem;"><x-icon name="alarm" /></div>
        <h2 class="mt-3" style="color:var(--primary);">{{ __('Your Free Trial Has Expired') }}</h2>
        <p class="text-muted my-3" style="font-size:1.1rem;">
            {!! __('Your free trial ended on :date. Subscribe to continue accessing all courses and content.', ['date' => '<strong>' . e($user->trialEndsAt()?->isoFormat('LL')) . '</strong>']) !!}
        </p>

        <div class="bg-white rounded-xl shadow-brand p-5 mt-4">
            <h4 style="color:var(--primary);">{{ __('Full Access Subscription') }}</h4>
            <div style="font-size:3rem;font-weight:800;color:var(--secondary);margin:1rem 0;">
                {{ __('Coming Soon') }}
            </div>
            <p class="text-muted mb-4">
                {{ __('Online payment integration (MTN Mobile Money, Orange Money, PayPal, Stripe) is currently in development. Please contact us directly to discuss subscription options.') }}
            </p>
            <div class="d-flex gap-3 justify-content-center flex-wrap">
                <a href="{{ route('contact') }}" class="btn btn-primary">
                    <x-icon name="envelope" class="me-2" />{{ __('Contact Us') }}
                </a>
                <a href="mailto:{{ $siteSettings['contact_email'] ?? 'info@acadexxa.com' }}" class="btn btn-outline-primary">
                    <x-icon name="envelope-open" class="me-2" />{{ __('Email Directly') }}
                </a>
            </div>
        </div>

        <p class="mt-4 text-muted small">
            {{ __('You can still access your certificates, profile, and course catalog. Only the course player is restricted.') }}
        </p>
    </div>
</div>
@endsection
