@extends(auth()->id() === $conversation->instructor_id ? 'layouts.instructor' : 'layouts.app')
@section('title', $conversation->subject)
@section('page-title', __('lms.messages'))

@php $other = $conversation->otherParticipant(auth()->user()); @endphp

@section('content')
<div class="{{ auth()->id() === $conversation->instructor_id ? '' : 'container py-5' }}" style="max-width:860px">
    <a href="{{ route('messages.index') }}" class="small"><x-icon name="arrow-left" class="me-1" />{{ __('lms.messages') }}</a>
    <div class="d-flex align-items-center gap-3 my-3">
        <img src="{{ $other->avatarUrl() }}" alt="" width="48" height="48" class="rounded-circle">
        <div>
            <h1 class="h5 fw-bold mb-0">{{ $conversation->subject }}</h1>
            <div class="small text-muted">{{ $other->name }} @if($conversation->course)· {{ $conversation->course->title() }}@endif</div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-brand p-3 p-md-4 mb-3" style="max-height:60vh;overflow-y:auto" id="thread">
        @foreach($conversation->messages as $m)
            @php $mine = $m->user_id === auth()->id(); @endphp
            <div class="d-flex mb-3 {{ $mine ? 'justify-content-end' : '' }}">
                <div class="p-3 rounded-3 {{ $mine ? 'bg-primary text-white' : 'bg-light' }}" style="max-width:80%">
                    <div style="white-space:pre-wrap">{{ $m->body }}</div>
                    <div class="small mt-1 {{ $mine ? 'text-white-50' : 'text-muted' }}">{{ $m->user->name }} · {{ $m->created_at->isoFormat('L LT') }}</div>
                </div>
            </div>
        @endforeach
        <div id="bottom"></div>
    </div>

    <form method="POST" action="{{ route('messages.reply', $conversation) }}" class="d-flex gap-2">
        @csrf
        <textarea name="body" rows="2" class="form-control" required maxlength="5000" placeholder="{{ __('lms.your_reply') }}"></textarea>
        <button class="btn btn-primary align-self-end"><x-icon name="send" /><span class="visually-hidden">{{ __('lms.send') }}</span></button>
    </form>
</div>
@endsection

@push('scripts')
<script>document.getElementById('thread').scrollTop = 1e9;</script>
@endpush
