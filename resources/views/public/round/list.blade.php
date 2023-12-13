@extends('public')

@section('title')
Gitcoin: A list of funding rounds that have been run on Gitcoin.
@endsection


@section('content')
<div class="container-fluid bg-light ml-0 mr-0 pl-0 pr-0">
    <div class="container py-3 ml-0 mr-0 pl-0 pr-0">
        <div class="card mb-3">
            <div class="card-body">

                <div class="mb-4">
                    <a href="{{ route('public.projects.home') }}" title="View a list of projects that have applied for funding via Gitcoin">Home</a> |
                    <a href="{{ route('public.projects.list') }}" title="View a list of rounds">Projects</a>
                </div>


                <div>
                    <form action="/public/rounds/list">
                        <div class="input-group mb-3">
                            <input name="search" type="text" value="{{ $search }}" class="form-control" placeholder="Search rounds..." aria-label="Search for rounds" aria-describedby="button-addon2">
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary" type="button" id="button-addon2">Search</button>
                            </div>
                        </div>
                    </form>

                    @if ($rounds && count($rounds) > 0)
                    <div class="mb-5">

                        @foreach ($rounds as $round)
                        <div class="mb-3">
                            <h4 class="mb-0"> <a href="{{ route('public.round.show', $round) }}">
                                    {{ $round->name }} </a>
                            </h4>

                            <span class="text-xs">{{ $round->chain->name }}, {{ \Carbon\Carbon::parse($round->round_start_time)->format('d M Y H:i') }} to {{ \Carbon\Carbon::parse($round->round_end_time)->format('d M Y H:i') }}<br />({{ $round->applications_count}} applications)</span>
                        </div>
                        @endforeach
                    </div>


                    <!-- Pagination -->
                    {{ $rounds->links() }}
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection