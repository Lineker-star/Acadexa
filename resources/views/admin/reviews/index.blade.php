@extends('layouts.admin')
@section('title', __('Reviews'))
@section('breadcrumb') <li class="breadcrumb-item active">{{ __('Reviews') }}</li> @endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">{{ __('Course Reviews') }}</h4>
</div>

<div class="bg-white rounded-xl shadow-brand p-3 mb-4">
    <form method="GET" class="d-flex gap-2 flex-wrap">
        <select name="rating" class="form-select form-select-sm" style="width:auto;">
            <option value="">{{ __('All Ratings') }}</option>
            @for($r=5;$r>=1;$r--)
            <option value="{{ $r }}" {{ request('rating')==$r?'selected':'' }}>{{ trans_choice(':count star|:count stars', $r, ['count' => $r]) }}</option>
            @endfor
        </select>
        <select name="flagged" class="form-select form-select-sm" style="width:auto;">
            <option value="">{{ __('All Reviews') }}</option>
            <option value="1" {{ request('flagged')=='1'?'selected':'' }}>{{ __('Flagged Only') }}</option>
        </select>
        <button class="btn btn-primary btn-sm">{{ __('Filter') }}</button>
    </form>
</div>

<div class="bg-white rounded-xl shadow-brand overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>{{ __('Student') }}</th><th>{{ __('Course') }}</th><th>{{ __('Rating') }}</th><th>{{ __('Review') }}</th><th>{{ __('Date') }}</th><th>{{ __('Action') }}</th></tr>
            </thead>
            <tbody>
                @forelse($reviews as $review)
                <tr class="{{ $review->is_flagged ? 'table-warning' : '' }}">
                    <td style="font-size:.85rem;">{{ $review->user?->name }}</td>
                    <td style="font-size:.82rem;">{{ Str::limit($review->course?->title(), 40) }}</td>
                    <td>
                        <div class="d-flex gap-1" style="color:#F59E0B;font-size:.8rem;">
                            @for($s=1;$s<=5;$s++)
                            <x-icon :name="'star' . ($s<=$review->rating?'-fill':'')" />
                            @endfor
                        </div>
                    </td>
                    <td style="font-size:.82rem;max-width:250px;">{{ Str::limit($review->comment, 80) }}</td>
                    <td style="font-size:.78rem;">{{ $review->created_at->isoFormat('ll') }}</td>
                    <td>
                        <form method="POST" action="{{ route('admin.reviews.destroy', $review) }}">
                            @csrf @method('DELETE')
                            <button class="btn btn-outline-danger btn-sm"
                                    data-confirm="{{ __('Remove this review?') }}">
                                <x-icon name="trash" />
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center py-4 text-muted">{{ __('No reviews found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $reviews->links() }}</div>
@endsection
