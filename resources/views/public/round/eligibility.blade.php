@extends('public')

@section('title')
Gitcoin Round Eligibility: {{ $round->name }}
@endsection

@section('meta_description')
Eligibility criteria for the {{ $round->name }} round on the {{ $round->chain->name }} blockchain.
@endsection

@section('content')

<div class="container-fluid bg-light ml-0 mr-0 pl-0 pr-0">
    <div class="container py-3 ml-0 mr-0 pl-0 pr-0">
        <div class="card mb-3">
            <div class="card-body">
                @include('public.breadcrumb')

                <div class="container py-3">
                    <?php
                    $eligibility = json_decode($round->round_metadata, true);
                    ?>

                    @if (isset($eligibility['name']))
                    <h1 class="card-title"><a href="{{ route('public.round.show', $round) }}" title="{{ $eligibility['name'] }}">{{ $eligibility['name'] }}</a> Eligibility</h1>
                    @endif

                    @if (isset($eligibility['eligibility']['description']))
                    <p class="card-text">{{ $eligibility['eligibility']['description'] }}</p>
                    @endif

                    @if (isset($eligibility['eligibility']['requirements']))
                    <h2>Requirements</h2>
                    <ul>
                        @foreach ($eligibility['eligibility']['requirements'] as $requirement)
                        <li>{!! make_links_clickable($requirement['requirement']) !!}</li>
                        @endforeach
                    </ul>
                    @endif

                </div>
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