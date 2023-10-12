<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Gitcoin Projects')</title>
    <link rel="stylesheet" href="{{ asset('css/public.css') }}">
    <link rel="stylesheet" href="{{ asset('css/font-awesome-4.7.0/css/font-awesome.min.css') }}">

    <meta name="description" content="@yield('meta_description', 'A showcase of projects on Gitcoin.')">
    <meta name="keywords" content="@yield('meta_keywords', 'Gitcoin, Open Source, Projects, Grants, Crowdfunding')">


    @stack('head')
</head>

<body class="bg-gray-100">
    <header class="bg-white p-4 shadow">
        <div class="container mx-auto">
            <div class="flex justify-between">
                <a href="{{ url('/') }}" class="text-lg font-bold text-gray-700">Gitcoin Checker</a>
                <!-- You can add more header elements here -->
            </div>
        </div>
    </header>

    <main class="container mx-auto p-4 mt-6">
        @yield('content')
    </main>

    @stack('scripts')
</body>

</html>