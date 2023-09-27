<script setup>
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import { Link } from "@inertiajs/vue3";
</script>

<template>
    <div>
        <div class="p-6 lg:p-8 bg-white border-b border-gray-200">
            <h1 class="mt-8 text-2xl font-medium text-gray-900">
                Gitcoin Checker
            </h1>

            <p class="mt-6 text-gray-500 leading-relaxed">
                A tool for checking Gitcoin projects to see which ones should be
                allowed in a specific round.
            </p>
        </div>

        <div
            class="bg-gray-200 bg-opacity-25 grid grid-cols-1 md:grid-cols-2 gap-6 lg:gap-8 p-6 lg:p-8"
        >
            <div>
                <div class="flex items-center">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                        class="w-6 h-6 stroke-gray-400"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"
                        />
                    </svg>
                    <h2 class="ml-3 text-xl font-semibold text-gray-900">
                        <Link
                            :href="route('access-control.index')"
                            class="text-blue-500 hover:underline"
                        >
                            Admins</Link
                        >
                    </h2>
                </div>

                <p class="mt-4 text-gray-500 text-sm leading-relaxed">
                    Who is allowed to access this tool?
                </p>

                <p class="mt-4 text-sm">
                    <Link
                        :href="route('access-control.index')"
                        class="text-blue-500 hover:underline"
                    >
                        Setup access
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 20 20"
                            class="ml-1 w-5 h-5 fill-indigo-500"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M5 10a.75.75 0 01.75-.75h6.638L10.23 7.29a.75.75 0 111.04-1.08l3.5 3.25a.75.75 0 010 1.08l-3.5 3.25a.75.75 0 11-1.04-1.08l2.158-1.96H5.75A.75.75 0 015 10z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </Link>
                </p>
            </div>

            <div>
                <div class="flex items-center">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                        class="w-6 h-6 stroke-gray-400"
                    >
                        <path
                            stroke-linecap="round"
                            d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z"
                        />
                    </svg>
                    <h2 class="ml-3 text-xl font-semibold text-gray-900">
                        <Link
                            :href="route('round.index')"
                            class="text-blue-500 hover:underline"
                        >
                            Rounds</Link
                        >
                    </h2>
                </div>

                <p class="mt-4 text-gray-500 text-sm leading-relaxed">
                    See a list of rounds.
                </p>

                <p class="mt-4 text-sm">
                    <Link
                        :href="route('round.index')"
                        class="text-blue-500 hover:underline"
                    >
                        Rounds

                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 20 20"
                            class="ml-1 w-5 h-5 fill-indigo-500"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M5 10a.75.75 0 01.75-.75h6.638L10.23 7.29a.75.75 0 111.04-1.08l3.5 3.25a.75.75 0 010 1.08l-3.5 3.25a.75.75 0 11-1.04-1.08l2.158-1.96H5.75A.75.75 0 015 10z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </Link>
                </p>
            </div>

            <div>
                <div class="flex items-center">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                        class="w-6 h-6 stroke-gray-400"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"
                        />
                    </svg>
                    <h2 class="ml-3 text-xl font-semibold text-gray-900">
                        <a href="https://tailwindcss.com/">Tailwind</a>
                    </h2>
                </div>

                <p class="mt-4 text-gray-500 text-sm leading-relaxed">
                    Laravel Jetstream is built with Tailwind, an amazing utility
                    first CSS framework that doesn't get in your way. You'll be
                    amazed how easily you can build and maintain fresh, modern
                    designs with this wonderful framework at your fingertips.
                </p>
            </div>

            <div>
                <div class="flex items-center">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                        class="w-6 h-6 stroke-gray-400"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"
                        />
                    </svg>
                    <h2 class="ml-3 text-xl font-semibold text-gray-900">
                        Authentication
                    </h2>
                </div>

                <p class="mt-4 text-gray-500 text-sm leading-relaxed">
                    Authentication and registration views are included with
                    Laravel Jetstream, as well as support for user email
                    verification and resetting forgotten passwords. So, you're
                    free to get started with what matters most: building your
                    application.
                </p>
            </div>
        </div>
    </div>
</template>
