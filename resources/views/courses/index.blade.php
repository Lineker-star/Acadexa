@extends('layouts.app')
@section('title', __('courses.catalog'))

@section('content')
<!-- Breadcrumb -->
<div class="breadcrumb-bar">
    <div class="container">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('navigation.home') }}</a></li>
            <li class="breadcrumb-item active">{{ __('courses.catalog') }}</li>
        </ol>
    </div>
</div>

<div class="py-5">
    <div class="container">
        <div class="row g-4">
            <!-- Filters Sidebar -->
            <div class="col-lg-3">
                <div class="bg-white rounded-xl shadow-brand p-4">
                    <h6 class="fw-bold mb-3" style="color:var(--primary);">{{ __('courses.filters') }}</h6>
                    <form method="GET" action="{{ route('courses.index') }}" id="filterForm">
                        <!-- Category -->
                        <div class="mb-3">
                            <label class="form-label small fw-bold">{{ __('courses.category') }}</label>
                            @foreach($categories as $cat)
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="category"
                                       value="{{ $cat->slug }}" id="cat_{{ $cat->id }}"
                                       {{ request('category') == $cat->slug ? 'checked' : '' }}
                                       onchange="document.getElementById('filterForm').submit()">
                                <label class="form-check-label small" for="cat_{{ $cat->id }}">
                                    {{ $cat->name(app()->getLocale()) }}
                                </label>
                            </div>
                            @endforeach
                        </div>

                        <!-- Level -->
                        <div class="mb-3">
                            <label class="form-label small fw-bold">{{ __('courses.level') }}</label>
                            @foreach(['beginner','intermediate','advanced'] as $lvl)
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="level"
                                       value="{{ $lvl }}" id="level_{{ $lvl }}"
                                       {{ request('level') == $lvl ? 'checked' : '' }}
                                       onchange="document.getElementById('filterForm').submit()">
                                <label class="form-check-label small" for="level_{{ $lvl }}">{{ ucfirst($lvl) }}</label>
                            </div>
                            @endforeach
                        </div>

                        <!-- Price -->
                        <div class="mb-3">
                            <label class="form-label small fw-bold">{{ __('courses.price') }}</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="price" value="free"
                                       {{ request('price') == 'free' ? 'checked' : '' }}
                                       onchange="document.getElementById('filterForm').submit()">
                                <label class="form-check-label small">{{ __('courses.free') }}</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="price" value="paid"
                                       {{ request('price') == 'paid' ? 'checked' : '' }}
                                       onchange="document.getElementById('filterForm').submit()">
                                <label class="form-check-label small">Paid</label>
                            </div>
                        </div>

                        @if(request()->hasAny(['category','level','price','search']))
                        <a href="{{ route('courses.index') }}" class="btn btn-outline-secondary btn-sm w-100">
                            <i class="bi bi-x-circle me-1"></i>Clear Filters
                        </a>
                        @endif
                    </form>
                </div>
            </div>

            <!-- Results -->
            <div class="col-lg-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h4 mb-1">{{ __('courses.catalog') }}</h1>
                        <p class="text-muted small mb-0">{{ $courses->total() }} {{ __('courses.courses_found') }}</p>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <label class="text-muted small me-1">{{ __('courses.sort_by') }}:</label>
                        <select class="form-select form-select-sm" style="width:auto;"
                                onchange="location.href=this.value">
                            @foreach(['newest'=>__('courses.newest'),'popular'=>__('courses.popular'),'rating'=>__('courses.top_rated'),'price_asc'=>__('courses.price_low')] as $val => $label)
                            <option value="{{ request()->fullUrlWithQuery(['sort'=>$val]) }}"
                                {{ request('sort', 'newest') == $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                @if($courses->count())
                <div class="row g-4">
                    @foreach($courses as $course)
                    <div class="col-sm-6 col-xl-4">
                        @include('partials.course-card', ['course' => $course])
                    </div>
                    @endforeach
                </div>
                <div class="mt-4">{{ $courses->links() }}</div>
                @else
                <div class="text-center py-5">
                    <div style="font-size:4rem;">🔍</div>
                    <h5 class="mt-3">{{ __('courses.no_courses_found') }}</h5>
                    <p class="text-muted">{{ __('courses.try_different_filters') }}</p>
                    <a href="{{ route('courses.index') }}" class="btn btn-primary">Browse All Courses</a>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
