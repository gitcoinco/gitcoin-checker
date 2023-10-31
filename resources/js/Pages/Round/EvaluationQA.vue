<script setup>
import { ref, reactive, computed } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import { Head, useForm, usePage, Link, router } from "@inertiajs/vue3";
import Pagination from "@/Components/Pagination.vue";
import Modal from "@/Components/Modal.vue";
import Tooltip from "@/Components/Tooltip.vue";
import QuestionTable from "./Components/EvaluationQA/QuestionTable.vue";
import QuestionInput from "./Components/EvaluationQA/QuestionInput.vue";
import {
    copyToClipboard,
    shortenAddress,
    scoreTotal,
    shortenURL,
} from "@/utils.js";

const round = ref(usePage().props.round.valueOf());
const questions = reactive([]);
const newQuestion = ref("");
const newQuestionType = ref("select");
const newOptions = ref("");

const totalWeighting = computed(() => {
    return questions.reduce((acc, question) => acc + question.weighting, 0);
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
    const data = {
        questions: questions.map((question) => {
            return {
                text: question.text,
                type: question.type,
                options: question.options,
                weighting: question.weighting,
            };
        }),
    };

    axios
        .post(
            route("round.evaluation.upsert", { round: round.value.uuid }),
            data
        )
        .then((response) => {
            console.log(response);
        })
        .catch((error) => {
            console.error(error);
        });
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

                <div class="mt-4">
                    <PrimaryButton @click="postQuestions">Save</PrimaryButton>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped></style>
