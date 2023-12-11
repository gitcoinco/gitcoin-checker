@extends('public')

@section('head')
<title>Welcome</title>
@endsection

@section('content')
<div class="container-fluid bg-light">

    <div class="container py-3">

        @if ($spotlightProject)
        <div class="highlight-green mb-4">
            <h3>In the spotlight</h3>

            <div class="row">
                <div class="col-md-auto">
                    <a href="{{ route('public.project.show', $spotlightProject) }}">
                        <img src="{{ $spotlightProject->logoImg ? $pinataUrl.'/'.$spotlightProject->logoImg.'?img-width=100' : '/img/placeholder.png' }}" onerror="this.onerror=null; this.src='/img/placeholder.png';" style="width: 100px; max-width: inherit" class="mx-auto rounded-circle" />
                    </a>
                </div>
                <div class="col">
                    <div class="mb-2">
                        Gitcoin funded <a href="{{ route('public.project.show', $spotlightProject) }}">{{ $spotlightProject->title }}</a> to the tune of ${{ number_format($spotlightProjectTotalDonorAmountUSD + $spotlightProjectTotalMatchAmountUSD, 0) }}. The project had support from {{ $spotlightProjectUniqueDonors}} individual donors, which contributed ${{ $spotlightProjectTotalDonorAmountUSD }} and this was matched by a Gitcoin contribution of ${{ $spotlightProjectTotalMatchAmountUSD }}.
                    </div>
                    <div>
                        We work out these numbers using <a href="https://wtfisqf.com/">Quadratic Funding</a>, which gives more money to projects that have broader community appeal.
                    </div>
                </div>
            </div>
        </div>
        @endif


        <div class="mb-4">
            <a href="{{ route('public.projects.list') }}">
                View a more Gitcoin projects
            </a>
        </div>


        <div class="mb-4">
            From pioneering open-source software to trailblazing climate solutions and building robust Web3 infrastructure, Gitcoin stands at the forefront of collaborative funding. Our mission is to connect visionary creators with a community eager to support impactful initiatives.
        </div>

        <div class="mb-4">
            Dive into a world where boundaries are pushed, and innovation flourishes across various sectors. Gitcoin is not just a funding platform; it's a catalyst for change, fostering a space where ideas, big and small, get the support they need to make a lasting impact.
        </div>

        <div class="mb-5">
            <h2>Key Highlights</h2>
            <div class="mb-3">
                Projects Across Diverse Domains: Explore a wide range of projects spanning open-source development, climate initiatives, Web3 infrastructure, and more, each contributing uniquely to our collective future.
            </div>
            <div class="mb-3">
                Over ${{ number_format($totalDonorAmountUSD + $totalMatchAmountUSD, 0) }} in Diverse Grants Distributed: Our grants go beyond conventional boundaries, supporting a multitude of sectors. Discover the impact of our funding rounds in driving forward-thinking projects.
            </div>
            <div class="mb-3">
                More than {{ $totalUniqueDonors }} contributors and Supporters: Be part of an enthusiastic and diverse community. Our platform is a hub for talents and supporters from various backgrounds, united by a shared vision of progress.
            </div>

        </div>

        <div class="mb-5">
            <h2>Stay Connected</h2>

            <div class="mb-3">
                <a href="https://gitcoin.co" target="_blank">Join the Gitcoin Ecosystem</a>: Whether you're an innovator, a developer, or a passionate supporter, your journey towards making a difference starts here. Explore projects, contribute to diverse causes, and be part of a community that's shaping the future.
            </div>

            <div class="mb-3">
                <a href="https://twitter.com/gitcoin" target="_blank">Stay Informed and Connected</a>: Follow us on <a href="https://twitter.com/gitcoin" target="_blank">Twitter</a> for the latest news, project highlights, and community stories.
            </div>
        </div>

    </div>
</div>
@endsection