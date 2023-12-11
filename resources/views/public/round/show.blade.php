@extends('public')

@section('title')
Gitcoin Round: {{ $round->title }}
@endsection

@section('meta_description')
The {{ $round->title }} round was ran on {{ $round->round_start_time }}.
@endsection


@section('content')

<div class="container-fluid bg-light">

    <div class="container py-3">

        <!-- Project Details -->
        <div class="card mb-3">
            <div class="card-body">
                <h1 class="card-title">{{ $round->name }}</h1>

                <p>
                    The {{ $round->name }} round ran on the {{ $round->chain->name }} blockchain from {{ \Carbon\Carbon::parse($round->round_start_time)->format('d M Y H:i') }} to {{ \Carbon\Carbon::parse($round->round_end_time)->format('d M Y H:i') }}.
                </p>
            </div>
        </div>


        <!-- Back to projects -->
        <div class="card mb-3">
            <div class="card-body">
                <a href="{{ route('public.projects.list') }}" class="text-decoration-none text-primary">Back to Projects</a>
            </div>
        </div>
    </div>
</div>


@endsection