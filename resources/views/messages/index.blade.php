@extends(auth()->user()->isInstructor() ? 'layouts.instructor' : 'layouts.app')
@section('title', __('lms.messages'))
@section('page-title', __('lms.messages'))

@section('content')
<div class="{{ auth()->user()->isInstructor() ? '' : 'container py-5' }}" style="max-width:960px">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h2 class="h4 fw-bold mb-0">{{ __('lms.messages') }}</h2>
        @if($courses->isNotEmpty())
            <button class="btn btn-primary btn-sm" data-bs-toggle="collapse" data-bs-target="#newConversation" aria-expanded="{{ request('course') ? 'true' : 'false' }}">
                <x-icon name="pencil-square" class="me-1" />{{ __('lms.new_message') }}
            </button>
        @endif
    </div>

    @if($courses->isNotEmpty())
    <div class="collapse {{ request('course') || $errors->any() ? 'show' : '' }}" id="newConversation">
        <form method="POST" action="{{ route('messages.store') }}" class="bg-white rounded-xl shadow-brand p-4 mb-4">
            @csrf
            <p class="small text-muted">{{ __('lms.new_message_help') }}</p>
            <div class="row g-2">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">{{ __('lms.course') }}</label>
                    <select name="course_id" class="form-select form-select-sm" required>
                        @foreach($courses as $c)
                            <option value="{{ $c->id }}" @selected(request('course') == $c->id)>{{ $c->title() }} — {{ $c->instructor?->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">{{ __('lms.subject') }}</label>
                    <input type="text" name="subject" class="form-control form-control-sm" required maxlength="255" value="{{ old('subject') }}">
                </div>
                <div class="col-12">
                    <textarea name="body" rows="4" class="form-control" required maxlength="5000" placeholder="{{ __('lms.message') }}">{{ old('body') }}</textarea>
                </div>
            </div>
            <button class="btn btn-primary btn-sm mt-2"><x-icon name="send" class="me-1" />{{ __('lms.send') }}</button>
        </form>
    </div>
    @endif

    <div class="bg-white rounded-xl shadow-brand">
        @forelse($conversations as $c)
            @php $other = $c->otherParticipant(auth()->user()); @endphp
            <a href="{{ route('messages.show', $c) }}" class="d-flex gap-3 align-items-center p-3 border-bottom text-decoration-none text-dark {{ $c->unread_count ? 'bg-primary-subtle bg-opacity-25' : '' }}">
                <img src="{{ $other->avatarUrl() }}" alt="" width="44" height="44" class="rounded-circle">
                <div class="flex-grow-1 min-w-0">
                    <div class="d-flex justify-content-between gap-2">
                        <strong class="text-truncate">{{ $other->name }}</strong>
                        <span class="small text-muted flex-shrink-0">{{ $c->last_message_at?->diffForHumans() }}</span>
                    </div>
                    <div class="small fw-semibold text-truncate">{{ $c->subject }} @if($c->course)<span class="text-muted fw-normal">· {{ $c->course->title() }}</span>@endif</div>
                    <div class="small text-muted text-truncate">{{ $c->latestMessage?->body }}</div>
                </div>
                @if($c->unread_count)<span class="badge bg-danger">{{ $c->unread_count }}</span>@endif
            </a>
        @empty
            <div class="text-center text-muted py-5"><x-icon name="envelope-open" class="fs-2 d-block mb-2" />{{ __('lms.no_messages') }}</div>
        @endforelse
    </div>
    <div class="mt-3">{{ $conversations->links() }}</div>
</div>
@endsection
