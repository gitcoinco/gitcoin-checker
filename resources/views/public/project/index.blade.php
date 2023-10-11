@extends('public')

@section('head')
<title>Welcome</title>
@endsection

@section('content')
<div class="relative sm:flex sm:justify-center sm:items-center min-h-screen bg-dots-darker bg-center bg-gray-100 dark:bg-dots-lighter dark:bg-gray-900 selection:bg-red-500 selection:text-white">


    <div class="max-w-7xl mx-auto p-6 lg:p-8">
        <div class="flex justify-left">
            <!-- Replace with your Blade components or images -->
            <!-- GitcoinLogo -->
            <!-- ApplicationLogo -->
        </div>

        <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
            @if ($projects && count($projects) > 0)

            <h1>Gitcoin Projects</h1>

            <table>
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
                        <td><a href="{{ route('public.project.show', $project->id) }}" class="text-blue-500 hover:underline">{{ $project->title }}</a></td>
                        <td>{{ $project->website }}</td>
                        <td>{{ $project->projectTwitter }}</td>
                        <td>{{ $project->userGithub }}</td>
                        <td>{{ $project->projectGithub }}</td>
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