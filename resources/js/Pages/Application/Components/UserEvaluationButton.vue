<script setup>
import { ref, defineProps, defineEmits } from "vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import Modal from "@/Components/Modal.vue";
import { showDateInShortFormat, shortenURL } from "@/utils";
import { Link } from "@inertiajs/vue3";
import TextareaInput from "@/Components/TextareaInput.vue";

import ReadMore from "@/Components/Gitcoin/ReadMore.vue";

const emit = defineEmits(["evaluatedApplication"]);

const selectedAnswers = ref([]);
const notes = ref(null);
const isModalOpen = ref([]);

const props = defineProps({
    application: Object,
});

const saveStatus = ref(null);

const showPromptModal = ref(false);
const userEvaluationAnswers = ref([]);
const hasRetrievedData = ref(false);
let loggedInUserAnswers = null;
let loggedInUser = null;

const fetchUserData = async () => {
    try {
        const response = await axios.get(
            route("round.application.user.evaluation.index", {
                application: props.application.uuid,
            })
        );
        userEvaluationAnswers.value = response.data.answers;
        loggedInUser = response.data.loggedInUser;
        loggedInUserAnswers = userEvaluationAnswers.value.find(
            (answer) => answer.user_id === loggedInUser.id
        );
        if (loggedInUserAnswers) {
            selectedAnswers.value = JSON.parse(loggedInUserAnswers.answers);
            notes.value = loggedInUserAnswers.notes;
        }
    } catch (error) {
        console.error("There was an error fetching the user data:", error);
    }
};

const submitEvaluation = async () => {
    const form = {
        answers: selectedAnswers.value,
        notes: notes.value,
    };

    saveStatus.value = "saving";

    try {
        await axios.post(
            route("round.application.evaluation.answers.upsert", {
                application: props.application.uuid,
            }),
            form,
            {
                headers: {
                    "Content-Type": "application/json",
                },
            }
        );

        emit("evaluatedApplication");
        await fetchUserData();
        saveStatus.value = "success";
        showPromptModal.value = false;
    } catch (error) {
        console.error("An error occurred while submitting the form:", error);
        saveStatus.value = "error";
    }
};

const toggleModal = () => {
    showPromptModal.value = !showPromptModal.value;
    if (!hasRetrievedData.value) {
        fetchUserData();
        hasRetrievedData.value = true;
    }
};

const displayText = (text) => {
    // turn links into clickable anchor tags with a shortened version of the link, by using the shortenURL function

    return text.replace(/((http:\/\/|https:\/\/)[^\s]+)/g, (match) => {
        return `<a href="${match}" target="_blank">${shortenURL(
            match.replace(/(http:\/\/|https:\/\/)/g, ""),
            15
        )}</a>`;
    });
};

/**
 * Look for a GPT evaluation and return for the specific questionText and return it.
 */
const hasGPTEvaluation = (results, questionText) => {
    if (!results) {
        return false;
    }

    let gptEvaluation = null;
    results.some((result) => {
        let resultsData = JSON.parse(result.results_data);

        for (let key in resultsData) {
            if (
                resultsData[key].criteria.trim().toLowerCase() ==
                questionText.trim().toLowerCase()
            ) {
                gptEvaluation = resultsData[key];
                return true;
            }
        }
    });

    return gptEvaluation;
};

const openModal = (index) => {
    isModalOpen.value[index] = true;
};
</script>

<template>
    <div>
        <span class="text-blue-500 hover:underline cursor-pointer">
            <div
                v-if="
                    application?.round?.evaluation_questions?.questions
                        ?.length > 0
                "
            >
                <SecondaryButton @click="toggleModal">
                    Perform Evaluation
                </SecondaryButton>
            </div>
            <div v-else>
                <Link
                    :href="route('round.evaluation.show.qa', application.round)"
                    class="text-blue-500 hover:underline"
                >
                    <i
                        class="fa fa-exclamation-circle text-red-500"
                        aria-hidden="true"
                    ></i>
                    Setup human criteria
                </Link>
            </div>
        </span>

        <Modal :show="showPromptModal" @close="showPromptModal = false">
            <div class="modal-content flex justify-between">
                <div
                    v-if="application?.project"
                    class="mr-2"
                    style="width: 49%"
                >
                    <div class="mb-3">
                        <div v-if="application.project.title">
                            {{ application.project.title }}
                        </div>

                        <div v-if="application.project.gpt_summary">
                            {{ application.project.gpt_summary }}
                        </div>

                        <div v-if="application.project.website">
                            Website:
                            <a
                                :href="application.project.website"
                                target="_blank"
                                >{{
                                    shortenURL(
                                        application.project.website.replace(
                                            /(http:\/\/|https:\/\/)/g,
                                            ""
                                        ),
                                        30
                                    )
                                }}
                            </a>
                        </div>

                        <div
                            v-for="(answer, index) in JSON.parse(
                                application.metadata
                            ).application.answers"
                            :key="index"
                            class="text-xs"
                        >
                            <div v-if="answer.answer" class="mb-3">
                                <strong>{{ answer.question }}:</strong>

                                <ReadMore :words="30">
                                    {{ answer.answer }}
                                </ReadMore>
                            </div>
                        </div>
                        <div v-if="application.project.projectGithub">
                            Project Github:
                            <a
                                :href="application.project.projectGithub"
                                target="_blank"
                                >{{ application.project.projectGithub }}</a
                            >
                        </div>

                        <div v-if="application.project.userGithub">
                            User Github:
                            <a
                                :href="application.project.userGithub"
                                target="_blank"
                                >{{ application.project.userGithub }}</a
                            >
                        </div>
                    </div>

                    <div v-if="application?.project?.applications">
                        <table class="text-xs">
                            <thead>
                                <tr>
                                    <td>Status</td>
                                    <td>Date</td>
                                    <td>Round</td>
                                </tr>
                            </thead>

                            <tbody>
                                <tr
                                    v-for="(application, index) in application
                                        .project.applications"
                                    :key="index"
                                >
                                    <td>
                                        {{ application.status }}
                                    </td>
                                    <td>
                                        {{
                                            showDateInShortFormat(
                                                application.created_at
                                            )
                                        }}
                                    </td>
                                    <td>
                                        <Link
                                            :href="
                                                route('round.show', {
                                                    round: application.round
                                                        .uuid,
                                                })
                                            "
                                            target="_blank"
                                        >
                                            {{ application.round.name }}
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div style="width: 49%">
                    <h2 class="modal-title flex justify-between">
                        <span>Perform Evaluation</span>
                        <span @click="toggleModal" class="cursor-pointer">
                            <i
                                class="fa fa-times-circle-o"
                                aria-hidden="true"
                            ></i>
                        </span>
                    </h2>
                    <form
                        @submit.prevent="submitEvaluation"
                        class="mb-4 text-sm"
                        v-if="
                            application?.round?.evaluation_questions?.questions
                                ?.length > 0
                        "
                    >
                        <div v-if="loggedInUserAnswers" class="mb-4">
                            Your evaluation from
                            {{
                                showDateInShortFormat(
                                    loggedInUserAnswers.updated_at,
                                    true
                                )
                            }}:
                        </div>
                        <div>
                            <div
                                v-for="(question, qIndex) in JSON.parse(
                                    application.round.evaluation_questions
                                        .questions
                                )"
                                :key="qIndex"
                                class="mb-5"
                            >
                                <p class="mb-2 font-bold">
                                    <span v-html="displayText(question.text)">
                                    </span>
                                    <!-- <span
                                        v-if="
                                            hasGPTEvaluation(
                                                application.results,
                                                question.text
                                            )
                                        "
                                    >
                                        <span
                                            @click="openModal(qIndex)"
                                            class="btn btn-primary pointer"
                                        >
                                            (GPT Result)
                                        </span>

                                        <Modal
                                            :show="
                                                isModalOpen[qIndex]
                                                    ? true
                                                    : false
                                            "
                                            @close="isModalOpen[qIndex] = false"
                                        >
                                            <div class="modal-content">
                                                {{
                                                    hasGPTEvaluation(
                                                        application.results,
                                                        question.text
                                                    ).score
                                                }}
                                                -
                                                {{
                                                    hasGPTEvaluation(
                                                        application.results,
                                                        question.text
                                                    ).reason
                                                }}
                                            </div>
                                        </Modal>
                                    </span> -->
                                </p>

                                <div class="flex flex-wrap">
                                    <div
                                        v-for="(
                                            option, cIndex
                                        ) in question.options"
                                        :key="cIndex"
                                        class="mb-1 mr-2 flex items-center text-xs"
                                    >
                                        <input
                                            type="radio"
                                            :name="'question-' + qIndex"
                                            :value="option"
                                            v-model="selectedAnswers[qIndex]"
                                            class="mr-2"
                                        />
                                        {{ option }}
                                    </div>
                                </div>
                            </div>
                            <div>
                                <TextareaInput
                                    v-model="notes"
                                    placeholder="Notes"
                                ></TextareaInput>
                            </div>
                            <div class="mt-4 flex justify-between items-center">
                                <div>
                                    <div
                                        v-if="saveStatus === 'saving'"
                                        class="mt-2 text-blue-500"
                                    >
                                        Saving...
                                    </div>
                                    <div
                                        v-if="saveStatus === 'success'"
                                        class="mt-2 text-green-500"
                                    >
                                        Saved successfully!
                                    </div>
                                    <div
                                        v-if="saveStatus === 'error'"
                                        class="mt-2 text-red-500"
                                    >
                                        Error saving data. Please try again.
                                    </div>
                                </div>
                                <PrimaryButton
                                    type="submit"
                                    :disabled="
                                        selectedAnswers.length !==
                                        JSON.parse(
                                            application.round
                                                .evaluation_questions.questions
                                        ).length
                                    "
                                    :class="{
                                        'opacity-50 cursor-not-allowed':
                                            selectedAnswers.length !==
                                            JSON.parse(
                                                application.round
                                                    .evaluation_questions
                                                    .questions
                                            ).length,
                                    }"
                                >
                                    Save
                                </PrimaryButton>
                            </div>
                        </div>
                    </form>
                    <div v-else>No questions specified</div>
                </div>
            </div>
        </Modal>
    </div>
</template>
