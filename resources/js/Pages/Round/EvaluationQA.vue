<script setup>
import { ref, reactive, computed, onMounted, onBeforeUnmount } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import { Head, useForm, usePage, Link, router } from "@inertiajs/vue3";
import Modal from "@/Components/Modal.vue";
import Tooltip from "@/Components/Tooltip.vue";
import QuestionTable from "./Components/EvaluationQA/QuestionTable.vue";
import QuestionInput from "./Components/EvaluationQA/QuestionInput.vue";
import { copyToClipboard, shortenAddress } from "@/utils.js";

const round = ref(usePage().props.round.valueOf());
const questions = reactive(
    JSON.parse(round.value.evaluation_questions.questions) || []
);

const form = useForm({
    questions: questions,
});

// Original questions
const originalQuestions = JSON.parse(JSON.stringify(questions));

// Computed property to check for changes
const hasChanges = computed(() => {
    return JSON.stringify(questions) !== JSON.stringify(originalQuestions);
});

const addQuestion = (text, type, options) => {
    questions.push({
        text: text,
        type: type,
        options: options.split(",").map((opt) => opt.trim()),
        weighting: 100, // Set the initial weighting to 100 for the new question
    });

    // Calculate equal weighting and the remainder
    const equalWeighting = Math.floor(100 / questions.length);
    const remainder = 100 % questions.length;

    // Distribute the weightings equally among all questions
    for (let i = 0; i < questions.length; i++) {
        questions[i].weighting = equalWeighting;
        if (i < remainder) {
            // Distribute the remainder
            questions[i].weighting += 1;
        }
    }
};

const removeQuestion = (index) => {
    const removedWeighting = questions[index].weighting;
    questions.splice(index, 1);
    const redistributedWeighting = removedWeighting / questions.length;

    questions.forEach((question) => {
        question.weighting += redistributedWeighting;
    });
};

const updateWeighting = (index, value) => {
    value = parseFloat(value);
    const oldWeighting = parseFloat(questions[index].weighting);

    // Check for invalid inputs
    if (isNaN(value)) {
        console.error("Invalid value input");
        return;
    }
    if (isNaN(oldWeighting)) {
        console.error("Invalid oldWeighting input");
        return;
    }

    const difference = value - oldWeighting;

    // Avoid division by zero
    const divisor = questions.length - 1 || 1;
    const redistributedDifference = difference / divisor;

    questions[index].weighting = value;

    questions.forEach((question, idx) => {
        if (idx !== index) {
            question.weighting -= redistributedDifference;
        }
    });
};

const postQuestions = () => {
    form.questions = questions;
    form.post(
        route("round.evaluation.upsert", {
            round: round.value.uuid,
        }),
        {
            onSuccess: (response) => {},
            onError: (error) => {},
        }
    );
};

const updateQuestion = (index, field, value) => {
    questions[index][field] = value;
};

const moveQuestion = (index, direction) => {
    if (
        (direction === -1 && index > 0) ||
        (direction === 1 && index < questions.length - 1)
    ) {
        const temp = questions[index];
        questions[index] = questions[index + direction];
        questions[index + direction] = temp;
    }
};
</script>

<template>
    <AppLayout title="Profile">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2
                        class="font-semibold text-xl text-gray-800 leading-tight"
                    >
                        {{ round.name }}
                        <span class="text-sm">
                            {{ shortenAddress(round.round_addr) }}
                            <span
                                @click="copyToClipboard(round.round_addr)"
                                class="cursor-pointer"
                            >
                                <i class="fa fa-clone" aria-hidden="true"></i>
                            </span>
                        </span>
                    </h2>
                </div>
            </div>
        </template>

        <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
            <!-- Display questions in a table -->
            <QuestionInput :addQuestionMethod="addQuestion" class="mb-10" />
            <div v-if="questions.length > 0">
                <QuestionTable
                    :questions="questions"
                    :updateQuestionMethod="updateQuestion"
                    :removeQuestionMethod="removeQuestion"
                    :updateWeightingMethod="updateWeighting"
                    :moveQuestionMethod="moveQuestion"
                />

                <div class="mt-4 flex justify-end items-center">
                    <span v-if="hasChanges" class="mr-3">
                        <i
                            class="fa fa-exclamation-circle text-red-500"
                            aria-hidden="true"
                        ></i>
                        Remember to save your changes!
                    </span>
                    <PrimaryButton
                        @click="postQuestions"
                        :disabled="!hasChanges"
                        class="text-white font-bold py-2 px-4 rounded transition duration-150 ease-in-out"
                        :class="{
                            'bg-blue-600 hover:bg-blue-700 focus:border-blue-700 focus:shadow-outline-blue active:bg-blue-800':
                                hasChanges,
                            'bg-gray-400 cursor-not-allowed': !hasChanges,
                        }"
                    >
                        Save
                    </PrimaryButton>
                </div>
            </div>
            <div>
                <div class="max-w-7xl mx-auto py-10">
                    <div class="mb-10"></div>
                    <div>
                        <Link
                            :href="route('round.prompt.show', round)"
                            class="text-blue-500 hover:underline"
                        >
                            ChatGPT Evaluation Criteria
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped></style>
