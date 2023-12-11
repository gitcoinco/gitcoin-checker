@extends('public')

@section('title')
Gitcoin: A list of funding rounds that have been run on Gitcoin.
@endsection


@section('content')
<div class="container-fluid bg-light">

    <div class="container py-3">
        <div class="d-flex justify-content-start">
            <!-- Replace with your Blade components or images -->
            <!-- GitcoinLogo -->
            <!-- ApplicationLogo -->
        </div>

        <div>
            <form action="/public/rounds/list">
                <div class="input-group mb-3">
                    <input name="query" type="text" class="form-control" placeholder="Search rounds..." aria-label="Search for rounds" aria-describedby="button-addon2">
                    <div class="input-group-append">
                        <button class="btn btn-outline-secondary" type="button" id="button-addon2">Search</button>
                    </div>
                </div>
            </form>

            @if ($rounds && count($rounds) > 0)
            <div class="mb-5">

                @foreach ($rounds as $round)
                <div class="mb-2">
                    <a href="{{ route('public.round.show', $round) }}">
                        {{ $round->name }} <span class="text-xs">on {{ $round->chain->name }}, {{ \Carbon\Carbon::parse($round->round_start_time)->format('d M Y H:i') }} to {{ \Carbon\Carbon::parse($round->round_end_time)->format('d M Y H:i') }} ({{ $round->applications_count}} applications)</span>
                    </a>
                </div>
                @endforeach
            </div>


            <!-- Pagination -->
            {{ $rounds->links() }}
            @endif
        </div>
    </div>
</div>
@endsection