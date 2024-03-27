<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Gitcoin Checker')</title>
    <link rel="stylesheet" href="{{ asset('css/public.css') }}">
    <link rel="stylesheet" href="{{ asset('css/font-awesome-4.7.0/css/font-awesome.min.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.1.3/dist/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">

    <meta name="description" content="@yield('meta_description', 'A showcase of projects on Gitcoin.')">
    <meta name="keywords" content="@yield('meta_keywords', 'Gitcoin, Open Source, Projects, Grants, Crowdfunding')">

    <meta name="google-site-verification" content="-DJDK5GlrthN2Fg_kOX1bbcSAR9ws8RBmfO4LMiaXP0" />

    <link rel="shortcut icon" href="/img/favicon.png" type="image/x-icon">

    @stack('head')

    @yield('scripts')


</head>

<body class="bg-gray-100" style="background-attachment: fixed; background-image: url('/img/backgrounds/bg3.jpeg');">

    <header class="bg-white p-4 shadow mb-5" style="opacity: 0.75;">
        <div class="container mx-auto">
            <div class="d-flex justify-content-between">
                <div class="d-flex">
                    <div style="max-width: 75px;" class="mr-2">
                        @include('public.includes.logo')
                    </div>
                    <div>

                        <a href="/public" class="text-lg font-bold text-gray-700 items-center space-x-2 flex">
                            <div style="max-width: 100px;">
                                <svg id="Layer_2" data-name="Layer 2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1083.49 192.9" class="w-6 h-6">
                                    <defs>
                                        <style>
                                            .cls-1 {
                                                fill: #130c03;
                                            }
                                        </style>
                                    </defs>
                                    <g id="Layer_1-2" data-name="Layer 1">
                                        <g>
                                            <path class="cls-1" d="m333.9,189.69V45.69h-40.75V3.46h127.2v42.24h-40.75v144h-45.69Z" />
                                            <path class="cls-1" d="m0,97.32C0,39.52,44.21,0,100.03,0c33.59,0,63.97,13.58,83.48,42.73l-40.26,20.99c-10.87-14.82-24.95-22.72-42.73-22.72-31.37,0-53.35,24.45-53.35,56.56s23.96,56.07,51.87,56.07c22.97,0,39.52-13.83,42.48-32.11v-.25h-39.02v-35.07h90.15v7.9c0,60.76-36.31,98.8-94.1,98.8C43.96,192.9,0,153.63,0,97.32Z" />
                                            <path class="cls-1" d="m223.99,189.69V3.46h45.69v186.23h-45.69Z" />
                                            <path class="cls-1" d="m823.07,189.69V3.46h45.69v186.23h-45.69Z" />
                                            <path class="cls-1" d="m1038.79,3.46h44.71v186.23h-37.05l-93.36-117.32.74,117.32h-44.95V3.46h38.04l93.36,116.58-1.48-116.58Z" />
                                            <path class="cls-1" d="m531.05,150.67c-28.4,0-52.36-22.23-52.36-54.09,0-30.13,22.97-54.09,52.86-54.09,14.72,0,27.57,5.57,36.92,14.79,4.51-14.4,11.53-27.87,20.86-39.91C572.99,6.46,553.11.25,531.79.25c-54.83,0-100.28,41-100.28,96.08,0,57.8,45.69,96.57,99.54,96.57,21.5,0,41.53-6.02,57.96-16.72-9.22-12.03-16.17-25.58-20.62-40.16-9.49,9.18-22.53,14.65-37.34,14.65Z" />
                                            <path class="cls-1" d="m694.83.25c-34.41,0-65.12,16.15-83.23,41.79-10.74,15.22-17.05,33.78-17.05,54.29s6.29,40.25,16.95,55.45c18.07,25.77,48.72,41.12,82.59,41.12,55.08,0,100.53-39.52,100.53-96.08S749.42.25,694.83.25Zm-.74,150.42c-28.4,0-52.36-22.23-52.36-54.09,0-30.13,22.97-54.09,52.86-54.09s52.86,23.71,52.86,54.34-22.97,53.84-53.35,53.84Z" />
                                        </g>
                                    </g>
                                </svg>
                            </div>
                        </a>
                        <div>
                            <a href="/public" class="text-muted text-decoration-none">
                                <small class="text-muted"><em>Checker: <span>An AI/LLM Powered Evaluation Tool by Gitcoin</span></em></small>
                            </a>
                        </div>
                    </div>
                </div>
                <div>
                    <a href="https://gitcoin.co/?utm_source=checker.gitcoin.co" target="_blank">
                        Learn more about Gitcoin
                    </a>
                </div>
            </div>
        </div>
    </header>

    <main class="container mx-auto mt-6" style="opacity: 0.75;">
        @yield('content')
    </main>

    @stack('scripts')

    @production
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ env('GOOGLE_TAGMANAGER_ID') }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }
        gtag('js', new Date());

        gtag('config', '{{ env("GOOGLE_TAGMANAGER_ID") }}');
    </script>
    @endproduction

</body>

</html>