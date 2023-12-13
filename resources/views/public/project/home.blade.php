@extends('public')

@section('title')
Gitcoin: What we have been funding
@endsection

@section('content')
<div class="container-fluid bg-light ml-0 mr-0 pl-0 pr-0">
    <div class="container py-3 ml-0 mr-0 pl-0 pr-0">
        <div class="card mb-3">
            <div class="card-body">

                <div class="mb-4">
                    <a href="{{ route('public.projects.list') }}" title="View a list of projects that have applied for funding via Gitcoin">Projects</a> |
                    <a href="{{ route('public.rounds.list') }}" title="View a list of rounds">Rounds</a>
                </div>

                <div class="mb-4">
                    Gitcoin creates onchain grants and identity management solutions that let communities govern their shared resources with trust and transparency. Our mission is to create technologies and opportunities that enable communities to build, fund and protect what matters.
                </div>


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
                                We work out these numbers using <a href="https://wtfisqf.com/?utm_source=checker.gitcoin.co" target="_blank">Quadratic Funding</a>, which gives more money to projects that have broader community appeal.
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <div class="mb-4">
                    Dive into a world where boundaries are pushed, and innovation flourishes across various sectors. Gitcoin has evolved into more than merely a grants program; it's a catalyst for change, empowering any community to fund what matters to them.
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
                        <a href="https://grants.gitcoin.co/?utm_source=checker.gitcoin.co" target="_blank">Explore projects, contribute to diverse causes</a>, and be part of a community that's shaping the future.
                    </div>

                    <div class="mb-3">
                        Follow us on <a href="https://twitter.com/gitcoin?utm_source=checker.gitcoin.co" target="_blank">Twitter</a> or <a href="https://www.gitcoin.co/?action=get-updates&utm_source=checker.gitcoin.co">sign up to our newsletter</a> for the latest news, project highlights, and community stories.
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection