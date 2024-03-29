<script setup>
import { defineProps } from "vue";
import { applicationStatusIcon } from "@/utils.js";
import moment from "moment";
import ResultsSummary from "./ResultsSummary.vue";
import Evaluation from "./Evaluation.vue";
import PreviousApplicationStatus from "@/Components/Gitcoin/Application/PreviousApplicationStatus.vue";
import ApplicationAnswers from "@/Components/Gitcoin/Application/ApplicationAnswers.vue";
import { usePage, Link, router } from "@inertiajs/vue3";
import { defineEmits } from "vue";

const emit = defineEmits(["perform-gpt-evaluation", "user-evaluation-updated"]);

const pinataUrl = import.meta.env.VITE_PINATA_CLOUDFRONT_URL;

const formatDate = (dateString) => {
    return moment(dateString).fromNow();
};

// Define props
const props = defineProps({
    application: {
        type: Object,
        required: true,
    },
    averageGPTEvaluationTime: {
        type: Number,
        required: true,
    },
});

const handleEvaluateApplication = (application) => {
    // emit
    emit("perform-gpt-evaluation", application);
};

const refreshApplication = (application) => {
    emit("user-evaluation-updated", application);
};
</script>

<template>
    <div class="p-2 flex w-full justify-between">
        <div>
            <div class="flex mb-5">
                <div v-if="application.project.logoImg" class="mr-3">
                    <img
                        :src="
                            pinataUrl +
                            '/' +
                            application.project.logoImg +
                            '?img-width=100'
                        "
                        style="width: 100px; max-width: inherit"
                        class="mx-auto rounded-full"
                    />
                </div>
                <div>
                    <div>
                        <Link
                            v-if="application.project"
                            :href="
                                route('project.show', {
                                    project: application.project.slug,
                                })
                            "
                            class="text-blue-500 hover:underline mr-2 text-2xl"
                        >
                            {{ application.project.title }}
                        </Link>

                        <ApplicationAnswers
                            :applicationUuid="application.uuid"
                        />
                    </div>
                    <div>
                        in
                        <Link
                            :href="route('round.show', application.round)"
                            class="text-blue-500 hover:underline"
                        >
                            {{ application.round.name }}
                        </Link>
                    </div>
                    <div>
                        <span>
                            <span
                                v-html="
                                    applicationStatusIcon(application.status)
                                "
                                class="mr-1"
                            ></span>
                            <span class="text-xs">
                                {{ formatDate(application.created_at) }}
                            </span>
                        </span>
                        <span
                            class="text-xs"
                            v-if="application?.round?.chain?.name"
                        >
                            on {{ application.round.chain.name }}</span
                        >
                        <br />
                        <PreviousApplicationStatus
                            :application="application"
                            class="text-xs"
                        />
                    </div>
                </div>
            </div>
            <div class="mb-3 text-sm">
                <div class="flex items-center space-x-4">
                    <div v-if="application.project?.website">
                        <i class="fa fa-globe mr-2" aria-hidden="true"></i>
                        <a
                            :href="application.project.website"
                            target="_blank"
                            class="text-blue-500"
                        >
                            {{ application.project.website }}
                        </a>
                    </div>
                    <div v-if="application.project?.projectTwitter">
                        <i
                            class="fa fa-twitter text-blue-500 mr-2"
                            aria-hidden="true"
                        ></i>
                        <a
                            :href="
                                'https://twitter.com/' +
                                application.project.projectTwitter
                            "
                            target="_blank"
                            class="text-blue-500"
                        >
                            {{ application.project.projectTwitter }}
                        </a>
                    </div>
                </div>
                <div class="flex space-x-4">
                    <div v-if="application.project?.projectGithub">
                        <i class="fa fa-github mr-2" aria-hidden="true"></i>
                        <a
                            :href="
                                'https://github.com/' +
                                application.project.projectGithub
                            "
                            target="_blank"
                            class="text-blue-500"
                        >
                            {{ application.project.projectGithub }}
                            (Project)
                        </a>
                    </div>
                    <div v-if="application.project?.userGithub">
                        <i class="fa fa-github mr-2" aria-hidden="true"></i>
                        <a
                            :href="
                                'https://github.com/' +
                                application.project.userGithub
                            "
                            target="_blank"
                            class="text-blue-500"
                        >
                            {{ application.project.userGithub }}
                            (User)
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="p-2 text-center">
            <div v-if="application.project">
                <Evaluation
                    :application="application"
                    @perform-gpt-evaluation="handleEvaluateApplication"
                    @user-evaluation-updated="refreshApplication"
                    :loadingBarInSeconds="averageGPTEvaluationTime"
                />
                <div class="mt-2 flex justify-center">
                    <ResultsSummary :application="application" />
                </div>

                <a
                    :href="
                        'https://manager.gitcoin.co/#/round/' +
                        application.round.round_addr.toLowerCase() +
                        '/application/' +
                        application.round.round_addr.toLowerCase() +
                        '-' +
                        application.application_id.toLowerCase()
                    "
                    target="_blank"
                    class="text-blue-500 underline"
                >
                    <i class="fa fa-external-link" aria-hidden="true"></i>
                    Manager link
                </a>
                <!-- <ReviewedBy :application="application" /> -->
            </div>
            <div v-else>No project data available yet</div>
        </div>
    </div>
</template>
