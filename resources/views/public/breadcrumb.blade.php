<div class="mb-4 mybreadcrumb d-flex justify-content-between">
    <div>
        <a href="{{ route('public.projects.home') }}" title="View a list of projects that have applied for funding via Gitcoin" class="{{ Request::is('public/projects') ? 'selected' : '' }}">Home</a>
        <a href="{{ route('public.projects.list') }}" title="View a list of projects that have applied for funding via Gitcoin" class="{{ Request::is('public/projects/list') | Request::is('public/project/show/*') ? 'selected' : '' }}">Projects</a>
        <a href="{{ route('public.rounds.list') }}" title="View a list of rounds" class="{{ Request::is('public/rounds/list') || Request::is('public/round/show/*') ? 'selected' : '' }}">Rounds</a>
    </div>
    <div>
        @yield('breadcrumbExtra')
    </div>
</div>