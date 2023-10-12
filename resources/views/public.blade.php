<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Gitcoin Projects')</title>
    <link rel="stylesheet" href="{{ asset('css/public.css') }}">
    <link rel="stylesheet" href="{{ asset('css/font-awesome-4.7.0/css/font-awesome.min.css') }}">

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

    <!-- <footer class="bg-white mt-12 p-4 border-t">
        <div class="container mx-auto">
            <p class="text-center text-gray-500 text-sm">© 2023 My App. All rights reserved.</p>
        </div>
    </footer> -->

    <!-- <script src="{{ asset('js/app.js') }}"></script> -->
    @stack('scripts')
</body>

</html>