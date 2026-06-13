@extends('layouts.admin')
@section('title', 'Reviews')
@section('breadcrumb') <li class="breadcrumb-item active">Reviews</li> @endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Course Reviews</h4>
</div>

<div class="bg-white rounded-xl shadow-brand p-3 mb-4">
    <form method="GET" class="d-flex gap-2 flex-wrap">
        <select name="rating" class="form-select form-select-sm" style="width:auto;">
            <option value="">All Ratings</option>
            @for($r=5;$r>=1;$r--)
            <option value="{{ $r }}" {{ request('rating')==$r?'selected':'' }}>{{ $r }} Stars</option>
            @endfor
        </select>
        <select name="flagged" class="form-select form-select-sm" style="width:auto;">
            <option value="">All Reviews</option>
            <option value="1" {{ request('flagged')=='1'?'selected':'' }}>Flagged Only</option>
        </select>
        <button class="btn btn-primary btn-sm">Filter</button>
    </form>
</div>

<div class="bg-white rounded-xl shadow-brand overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>Student</th><th>Course</th><th>Rating</th><th>Review</th><th>Date</th><th>Action</th></tr>
            </thead>
            <tbody>
                @forelse($reviews as $review)
                <tr class="{{ $review->is_flagged ? 'table-warning' : '' }}">
                    <td style="font-size:.85rem;">{{ $review->user?->name }}</td>
                    <td style="font-size:.82rem;">{{ Str::limit($review->course?->title(), 40) }}</td>
                    <td>
                        <div class="d-flex gap-1" style="color:#F59E0B;font-size:.8rem;">
                            @for($s=1;$s<=5;$s++)
                            <i class="bi bi-star{{ $s<=$review->rating?'-fill':'' }}"></i>
                            @endfor
                        </div>
                    </td>
                    <td style="font-size:.82rem;max-width:250px;">{{ Str::limit($review->comment, 80) }}</td>
                    <td style="font-size:.78rem;">{{ $review->created_at->format('M d, Y') }}</td>
                    <td>
                        <form method="POST" action="{{ route('admin.reviews.destroy', $review) }}">
                            @csrf @method('DELETE')
                            <button class="btn btn-outline-danger btn-sm"
                                    onclick="return confirm('Remove this review?')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center py-4 text-muted">No reviews found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $reviews->links() }}</div>
@endsection
