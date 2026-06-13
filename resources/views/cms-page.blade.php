@extends('layouts.app')
@php $trans = $page->translation(); @endphp
@section('title', $trans?->title ?? ucfirst($page->slug))
@section('meta_description', $trans?->meta_description ?? '')

@section('content')
<div class="breadcrumb-bar"><div class="container"><ol class="breadcrumb mb-0"><li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li><li class="breadcrumb-item active">{{ $trans?->title ?? ucfirst($page->slug) }}</li></ol></div></div>

@if($page->hero_image)
<section class="hero-section" style="min-height:280px;background-image:url('{{ $page->hero_image }}');">
    <div class="container hero-content py-4">
        <h1>{{ $trans?->title }}</h1>
    </div>
</section>
@else
<div class="py-4 bg-light-gray border-bottom">
    <div class="container">
        <h1 class="h2 mb-0">{{ $trans?->title ?? ucfirst($page->slug) }}</h1>
    </div>
</div>
@endif

<div class="py-5">
    <div class="container" style="max-width:900px;">
        <div class="bg-white rounded-xl shadow-brand p-5">
            <div class="cms-content" style="line-height:1.9;font-size:1rem;">
                {!! $trans?->content ?? '<p>Content coming soon.</p>' !!}
            </div>
        </div>
    </div>
</div>
@endsection
