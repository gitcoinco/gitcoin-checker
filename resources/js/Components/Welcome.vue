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
                    <i
                        class="fa fa-lock text-gray-400 fa-lg"
                        aria-hidden="true"
                    ></i>

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
                        <span class="flex items-center">
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
                        </span>
                    </Link>
                </p>
            </div>

            <div>
                <div class="flex items-center">
                    <i
                        class="fa fa-circle-o text-gray-400 fa-lg"
                        aria-hidden="true"
                    ></i>
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
                        <span class="flex items-center">
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
                        </span>
                    </Link>
                </p>
            </div>

            <div>
                <div class="flex items-center">
                    <i
                        class="fa fa-building-o text-gray-400 fa-lg"
                        aria-hidden="true"
                    ></i>

                    <h2 class="ml-3 text-xl font-semibold text-gray-900">
                        <Link
                            :href="route('project.index')"
                            class="text-blue-500 hover:underline"
                        >
                            Projects
                        </Link>
                    </h2>
                </div>

                <p class="mt-4 text-gray-500 text-sm leading-relaxed">
                    List of projects.
                </p>
            </div>

            <!-- round.application.index -->

            <div>
                <div class="flex items-center">
                    <i
                        class="fa fa-cogs text-gray-400 fa-lg"
                        aria-hidden="true"
                    ></i>

                    <h2 class="ml-3 text-xl font-semibold text-gray-900">
                        <Link
                            :href="route('round.application.index')"
                            class="text-blue-500 hover:underline"
                        >
                            Applications
                        </Link>
                    </h2>
                </div>

                <p class="mt-4 text-gray-500 text-sm leading-relaxed">
                    List of applications.
                </p>
            </div>
        </div>
    </div>
</template>
