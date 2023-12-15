@extends('public')

@section('title')
Gitcoin: A list of projects that have been funded by Gitcoin Grants
@endsection


@section('content')
<div class="container-fluid bg-light ml-0 mr-0 pl-0 pr-0">
    <div class="container py-3 ml-0 mr-0 pl-0 pr-0">
        <div class="card mb-3">
            <div class="card-body">
                @include('public.breadcrumb')
                <div class="container py-3">
                    <div class="mb-5 highlight-green">
                        <div id="randomProject"></div>
                        <div id="randomProjectCounter" class="pointer text-right text-muted font-italic small"></div>
                        <script>
                            let counter = 10;
                            document.querySelector('#randomProjectCounter').addEventListener('click', function() {
                                getRandomProject();
                                document.querySelector('#randomProjectCounter').innerHTML = '';
                                counter = 10;
                            });

                            let countdown = setInterval(function() {
                                document.querySelector('#randomProjectCounter').innerHTML = 'Auto refresh in ' + counter + ' seconds';
                                counter--;
                                if (counter < 0) {
                                    counter = 10;
                                    getRandomProject();
                                }
                            }, 1000);
                        </script>
                    </div>


                    <form action="/public/projects/list">
                        <div class="input-group mb-3">
                            <input name="search" type="text" value="{{ $search }}" class="form-control" placeholder="Search projects..." aria-label="Search for projects" aria-describedby="button-addon2">
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary" type="button" id="button-addon2">Search</button>
                            </div>
                        </div>
                    </form>
                    @if ($projects && count($projects) > 0)
                    @foreach ($projects as $project)
                    <div class="mb-5 d-flex">
                        <div class="mr-3">
                            <a href="{{ route('public.project.show', $project) }}">
                                <img src="{{ $project->logoImg ? $pinataUrl.'/'.$project->logoImg.'?img-width=100' : '/img/placeholder.png' }}" onerror="this.onerror=null; this.src='/img/placeholder.png';" style="width: 100px; max-width: inherit" class="mx-auto rounded-circle" />
                            </a>
                        </div>
                        <div>
                            <div>
                                <a href="{{ route('public.project.show', $project) }}" class="text-primary">{{ $project->title }}</a>
                                @if($project->gpt_summary)
                                <div class="text-xs">
                                    {{ $project->gpt_summary }}
                                </div>
                                @endif
                            </div>
                            <!-- <div class="small descriptionHTML">
                        {!! ($project->descriptionHTML) !!}
                    </div> -->
                            <div class="small">
                                @if($project->website)
                                <a href="{{ $project->website }}" target="_blank">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-globe-europe-africa" viewBox="0 0 16 16">
                                        <path d="M8 0a8 8 0 1 0 0 16A8 8 0 0 0 8 0M3.668 2.501l-.288.646a.847.847 0 0 0 1.479.815l.245-.368a.809.809 0 0 1 1.034-.275.809.809 0 0 0 .724 0l.261-.13a1 1 0 0 1 .775-.05l.984.34c.078.028.16.044.243.054.784.093.855.377.694.801-.155.41-.616.617-1.035.487l-.01-.003C8.274 4.663 7.748 4.5 6 4.5 4.8 4.5 3.5 5.62 3.5 7c0 1.96.826 2.166 1.696 2.382.46.115.935.233 1.304.618.449.467.393 1.181.339 1.877C6.755 12.96 6.674 14 8.5 14c1.75 0 3-3.5 3-4.5 0-.262.208-.468.444-.7.396-.392.87-.86.556-1.8-.097-.291-.396-.568-.641-.756-.174-.133-.207-.396-.052-.551a.333.333 0 0 1 .42-.042l1.085.724c.11.072.255.058.348-.035.15-.15.415-.083.489.117.16.43.445 1.05.849 1.357L15 8A7 7 0 1 1 3.668 2.501Z" />
                                    </svg>

                                    {{ $project->website }}</a><br />
                                @endif

                                @if($project->projectTwitter)
                                <a href="https://twitter.com/{{ $project->projectTwitter }}" target="_blank">

                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-twitter" viewBox="0 0 16 16">
                                        <path d="M5.026 15c6.038 0 9.341-5.003 9.341-9.334 0-.14 0-.282-.006-.422A6.685 6.685 0 0 0 16 3.542a6.658 6.658 0 0 1-1.889.518 3.301 3.301 0 0 0 1.447-1.817 6.533 6.533 0 0 1-2.087.793A3.286 3.286 0 0 0 7.875 6.03a9.325 9.325 0 0 1-6.767-3.429 3.289 3.289 0 0 0 1.018 4.382A3.323 3.323 0 0 1 .64 6.575v.045a3.288 3.288 0 0 0 2.632 3.218 3.203 3.203 0 0 1-.865.115 3.23 3.23 0 0 1-.614-.057 3.283 3.283 0 0 0 3.067 2.277A6.588 6.588 0 0 1 .78 13.58a6.32 6.32 0 0 1-.78-.045A9.344 9.344 0 0 0 5.026 15" />
                                    </svg>

                                    {{ $project->projectTwitter }}</a><br />
                                @endif

                                @if($project->projectGithub)
                                <a href="https://github.com/{{ $project->projectGithub }}" target="_blank">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-github" viewBox="0 0 16 16">
                                        <path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.012 8.012 0 0 0 16 8c0-4.42-3.58-8-8-8" />
                                    </svg>
                                    {{ $project->projectGithub }}</a><br />
                                @endif
                                <div class="text-muted font-italic">
                                    Created {{ $project->created_at->diffForHumans() }}
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach


                    <!-- Pagination -->
                    {{ $projects->links() }}
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

<script>
    function getRandomProject() {
        fetch('{{ route("public.project.random") }}')
            .then(response => response.json())
            .then(data => {
                let project = data.project;
                let pinataUrl = data.pinataUrl;
                let projectDiv = document.createElement('div');
                projectDiv.innerHTML = `
                <h3 class="mb-3 text-dark">In the spotlight</h3>
                <a href="/public/project/show/${project.slug}" class="text-dark">
                                <div class="d-flex">
                                <div class="mr-3">
                                <img width="100" height="100" src="${pinataUrl}/${project.logoImg}?img-width=100" class="mx-auto rounded-circle" />
                                </div>
                                <div>
                            <h6>${project.title}</h6>
                            <div>${project.gpt_summary}</div>
                            </div>
                            </div>
                            </a>
                        `;
                document.querySelector('#randomProject').innerHTML = projectDiv.outerHTML;
            })
            .catch(error => console.error('Error:', error));
    }

    getRandomProject();
</script>