@extends('public')

@section('head')
<title>Welcome</title>
@endsection

@section('content')
<div class="container-fluid bg-light">

    <div class="container py-3">
        <div class="d-flex justify-content-start">
            <!-- Replace with your Blade components or images -->
            <!-- GitcoinLogo -->
            <!-- ApplicationLogo -->
        </div>

        <div class="container py-3">
            @if ($projects && count($projects) > 0)

            <h1>Gitcoin Projects</h1>

            <table class="table">
                <thead>
                    <tr>
                        <th>
                            <!-- <input type="text" placeholder="Search..." value="{{ request('search') }}" /> -->
                            Project
                        </th>
                        <th>Website</th>
                        <th>Twitter</th>
                        <th>User Github</th>
                        <th>Project Github</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($projects as $project)
                    <tr>
                        <td><a href="{{ route('public.project.show', $project) }}" class="text-primary">{{ $project->title }}</a></td>
                        <td><a href="{{ $project->website }}" target="_blank">{{ $project->website }}</a></td>
                        <td><a href="https://twitter.com/{{ $project->projectTwitter }}" target="_blank">{{ $project->projectTwitter }}</a></td>
                        <td><a href="https://github.com/{{ $project->userGithub }}" target="_blank">{{ $project->userGithub }}</a></td>
                        <td><a href="https://github.com/{{ $project->projectGithub }}" target="_blank">{{ $project->projectGithub }}</a></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- Pagination -->
            {{ $projects->links() }}
            @endif
        </div>
    </div>
</div>
@endsection