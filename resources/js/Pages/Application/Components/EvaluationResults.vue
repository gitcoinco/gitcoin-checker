<script setup>
import { ref, defineProps } from "vue";
import { getShortenedName } from "@/utils.js";
import Modal from "@/Components/Modal.vue";

const props = defineProps({
    application: Object,
});

// Reactive state to track open modals
const modalsOpen = ref({});

// Function to open a modal
const openModal = (index) => {
    console.log("open modal " + index);
    modalsOpen.value[index] = true;
};

// Function to check if a modal is open
const isModalOpen = (index) => {
    return modalsOpen.value[index] || false;
};

// Function to close a modal
const closeModal = (index) => {
    modalsOpen.value[index] = false;
};

const gptAnswers = () => {
    if (props.application?.results?.length > 0) {
        return JSON.parse(props.application.results[0].results_data);
    } else {
        return [];
    }
};

const allAnswers = () => {
    let answers = [];

    if (props.application?.evaluation_answers?.length > 0) {
        for (let i = 0; i < props.application.evaluation_answers.length; i++) {
            answers.push({
                user: {
                    name: props.application.evaluation_answers[i].user.name,
                },
                answer: props.application.evaluation_answers[i],
            });
        }
    }

    return answers;
};

// Don't re-parse our answers every time we render the table
let parsedAnswers = ref(null);

const answerForIndex = (index, answers) => {
    if (!parsedAnswers.value) {
        parsedAnswers.value = JSON.parse(answers);
    }
    if (parsedAnswers.value[index]) {
        return parsedAnswers.value[index];
    } else {
        return null;
    }
};

const getGptAnswer = (question) => {
    for (let i = 0; i < gptAnswers().length; i++) {
        if (
            gptAnswers()[i].criteria.trim().toLowerCase() ==
            question.trim().toLowerCase()
        ) {
            return gptAnswers()[i];
        }
    }
    return {
        score: "N/A",
        reason: "N/A",
    };
};

const questions = () => {
    return JSON.parse(props.application.round.evaluation_questions.questions);
};
</script>

<template>
    <div v-if="questions().length > 0" class="text-xs text-gray-500">
        <table class="w-full text-xs">
            <tr>
                <th class="text-gray-500">Round evaluation criteria</th>
                <th v-if="gptAnswers().length > 0">GPT</th>
                <th
                    v-for="(reviewer, index) in allAnswers()"
                    :key="'reviewer-' + index"
                >
                    {{ getShortenedName(reviewer.user.name) }}
                </th>
            </tr>
            <tr v-for="(question, index2) in questions()" :key="index2">
                <td>
                    {{ question.text }}
                </td>
                <td v-if="gptAnswers().length > 0">
                    <span @click="openModal(index2)" class="pointer">
                        {{ getGptAnswer(question.text)?.score }}
                    </span>
                    <Modal
                        :show="isModalOpen(index2)"
                        @close="closeModal(index2)"
                    >
                        <div class="p-5">
                            {{ getGptAnswer(question.text).reason }}
                        </div>
                    </Modal>
                </td>
                <td
                    v-for="(reviewer, index) in allAnswers()"
                    :key="'reviewer-answer-' + index"
                >
                    {{ answerForIndex(index2, reviewer.answer.answers) }}
                </td>
            </tr>
        </table>
    </div>
</template>
