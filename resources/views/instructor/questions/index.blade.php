@extends('layouts.instructor')
@section('title', __('lms.qa'))
@section('page-title', __('lms.qa'))

@section('content')
<div class="btn-group btn-group-sm mb-3">
    <a href="{{ route('instructor.qa.index', ['filter' => 'unanswered']) }}" class="btn {{ $filter === 'unanswered' ? 'btn-primary' : 'btn-outline-primary' }}">{{ __('lms.unanswered') }}</a>
    <a href="{{ route('instructor.qa.index', ['filter' => 'all']) }}" class="btn {{ $filter !== 'unanswered' ? 'btn-primary' : 'btn-outline-primary' }}">{{ __('lms.all') }}</a>
</div>

@forelse($questions as $q)
    <div class="bg-white rounded-xl shadow-brand p-4 mb-3">
        <div class="small text-muted mb-2">
            {{ \Illuminate\Support\Str::limit($q->lesson->module->course->title(), 45) }} › {{ $q->lesson->title() }}
        </div>
        <div class="d-flex gap-3">
            <img src="{{ $q->user->avatarUrl() }}" alt="" width="38" height="38" class="rounded-circle">
            <div class="flex-grow-1">
                <strong class="small">{{ $q->user->name }}</strong> <span class="small text-muted">· {{ $q->created_at->diffForHumans() }}</span>
                <p class="mb-2" style="white-space:pre-wrap">{{ $q->comment }}</p>
                @foreach($q->replies as $reply)
                    <div class="d-flex gap-2 ps-3 border-start mb-2">
                        <img src="{{ $reply->user->avatarUrl() }}" alt="" width="28" height="28" class="rounded-circle">
                        <div><strong class="small">{{ $reply->user->name }}</strong><div class="small" style="white-space:pre-wrap">{{ $reply->comment }}</div></div>
                    </div>
                @endforeach
                <form method="POST" action="{{ route('instructor.comment.reply', $q) }}" class="d-flex gap-2 mt-2">
                    @csrf
                    <textarea name="reply" rows="1" class="form-control form-control-sm" required maxlength="2000" placeholder="{{ __('lms.your_reply') }}"></textarea>
                    <button class="btn btn-sm btn-primary">{{ __('lms.reply') }}</button>
                </form>
            </div>
        </div>
    </div>
@empty
    <div class="text-center text-muted py-5 bg-white rounded-xl shadow-brand"><x-icon name="chat-square-heart" class="fs-3 d-block mb-2" />{{ __('lms.no_questions_to_answer') }}</div>
@endforelse
{{ $questions->links() }}
@endsection
