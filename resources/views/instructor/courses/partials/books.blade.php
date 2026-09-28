{{-- Course books: PDF books and audio/video documents students keep in their library (readable offline). --}}
<div class="bg-white rounded-xl shadow-brand p-4">
    <h5 class="fw-bold mb-1"><x-icon name="book" class="me-1" />{{ __('learn.tab_books') }}</h5>
    <p class="small text-muted">{{ __('learn.books_help') }}</p>

    @forelse($course->books as $book)
        <div class="border rounded p-3 mb-2">
            <div class="d-flex align-items-center gap-3">
                <x-icon :name="$book->icon()" class="fs-3 text-primary" />
                <div class="flex-grow-1 min-w-0">
                    <div class="fw-semibold text-truncate">{{ $book->title }}</div>
                    <div class="small text-muted text-truncate">{{ $book->author ? $book->author . ' · ' : '' }}{{ $book->original_name }} · {{ $book->sizeLabel() }}</div>
                </div>
                <button type="button" class="btn btn-sm btn-light" data-open-media="{{ $book->url() }}" data-media-kind="{{ $book->kind() }}" data-media-title="{{ $book->title }}" aria-label="{{ __('lms.preview') }}"><x-icon name="eye" /></button>
                @unless($locked)
                <button type="button" class="btn btn-sm btn-light" data-bs-toggle="collapse" data-bs-target="#book-edit-{{ $book->id }}" aria-label="{{ __('lms.edit') }}"><x-icon name="pencil" /></button>
                <form method="POST" action="{{ route('instructor.books.destroy', $book) }}" data-confirm="{{ __('learn.confirm_delete_book') }}">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-light text-danger" aria-label="{{ __('lms.delete') }}"><x-icon name="trash" /></button>
                </form>
                @endunless
            </div>
            @if($book->description)<p class="small text-muted mb-0 mt-2">{{ $book->description }}</p>@endif
            @unless($locked)
            <form method="POST" action="{{ route('instructor.books.update', $book) }}" class="collapse mt-3" id="book-edit-{{ $book->id }}">
                @csrf @method('PUT')
                <div class="row g-2">
                    <div class="col-md-6"><input type="text" name="title" class="form-control form-control-sm" maxlength="255" required value="{{ $book->title }}" aria-label="{{ __('lms.title') }}"></div>
                    <div class="col-md-6"><input type="text" name="author" class="form-control form-control-sm" maxlength="255" value="{{ $book->author }}" placeholder="{{ __('learn.book_author') }}"></div>
                    <div class="col-12"><textarea name="description" class="form-control form-control-sm" rows="2" maxlength="2000" placeholder="{{ __('lms.description') }}">{{ $book->description }}</textarea></div>
                </div>
                <button class="btn btn-sm btn-primary mt-2">{{ __('lms.save') }}</button>
            </form>
            @endunless
        </div>
    @empty
        <div class="text-center text-muted py-4 border rounded mb-3"><x-icon name="book" class="fs-3 d-block mb-2" />{{ __('learn.no_books') }}</div>
    @endforelse

    @unless($locked)
    <form method="POST" action="{{ route('instructor.books.store', $course) }}" enctype="multipart/form-data" class="row g-2 align-items-end mt-3 border-top pt-3">
        @csrf
        <div class="col-md-6">
            <label class="form-label small">{{ __('lms.title') }}</label>
            <input type="text" name="title" class="form-control form-control-sm" maxlength="255">
        </div>
        <div class="col-md-6">
            <label class="form-label small">{{ __('learn.book_author') }}</label>
            <input type="text" name="author" class="form-control form-control-sm" maxlength="255">
        </div>
        <div class="col-md-9">
            <label class="form-label small">{{ __('lms.file') }} — {{ __('learn.book_formats', ['max' => \App\Support\Format::bytes(config('lms.book.max_size_mb') * 1048576)]) }}</label>
            <input type="file" name="file" class="form-control form-control-sm @error('file') is-invalid @enderror" required
                   accept=".{{ implode(',.', config('lms.book.extensions')) }}">
            @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3"><button class="btn btn-sm btn-primary w-100"><x-icon name="upload" class="me-1" />{{ __('lms.add') }}</button></div>
    </form>
    @endunless
</div>
