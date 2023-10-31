<script setup>
import { ref, onMounted, defineProps, defineEmits } from "vue";
import { useForm } from "@inertiajs/vue3";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import Modal from "@/Components/Modal.vue";
import Slider from "@/Components/Slider.vue";

const emit = defineEmits(["evaluatedApplication"]);

const selectedAnswers = ref([]);

// Accept application as a prop
const props = defineProps({
    application: Object,
});

const form = useForm({
    score: 50,
    notes: null,
});

// Reactive reference for managing modal visibility
const showPromptModal = ref(false);
const scoreData = ref([]); // For storing user data
const hasRetrievedData = ref(false); // For storing user data

// Method to fetch user data from the server
const fetchscoreData = async () => {
    try {
        // Replace with your actual request URL and parameters if needed
        const response = await axios.get(
            route("round.application.user.score.index", {
                application: props.application.uuid,
            })
        );
        scoreData.value = response.data.userScores;
        const loggedInUser = response.data.loggedInUser;
        const loggedInUserScore = scoreData.value.find(
            (score) => score.user.id === loggedInUser.id
        );
        if (loggedInUserScore) {
            form.score = loggedInUserScore.score;
            form.notes = loggedInUserScore.notes;
        }
    } catch (error) {
        console.error("There was an error fetching the user data:", error);
    }
};

const submitScore = async () => {
    // You can perform any validation or pre-processing here if needed

    try {
        // Use Inertia.js's post method to submit the form data
        // Replace 'your-submit-endpoint' with your actual endpoint URL
        axios.post(
            route("round.application.user.score.upsert", {
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

        await fetchscoreData();
    } catch (error) {
        // Handle submission error (show error message, log, etc.)
        console.error("An error occurred while submitting the form:", error);
    }
};

// Method to toggle modal's visibility
const toggleModal = () => {
    showPromptModal.value = !showPromptModal.value;
    if (!hasRetrievedData.value) {
        fetchscoreData();
        hasRetrievedData.value = true;
    }
};
</script>

<template>
    <div>
        <!-- Button to trigger modal -->
        <span
            @click="toggleModal"
            class="text-blue-500 hover:underline cursor-pointer"
        >
            Human Evaluation
        </span>

        <!-- Modal component -->
        <Modal :show="showPromptModal" @close="showPromptModal = false">
            <div class="modal-content">
                {{ selectedAnswers }}
                <h2 class="modal-title flex justify-between">
                    <span>Human Evaluations</span>
                    <span @click="toggleModal" class="cursor-pointer">
                        <i class="fa fa-times-circle-o" aria-hidden="true"></i>
                    </span>
                </h2>
                <form @submit.prevent="submitScore" class="p-4">
                    <div
                        v-for="(question, qIndex) in JSON.parse(
                            application.round.evaluation_questions.questions
                        )"
                        :key="qIndex"
                    >
                        <p class="mb-2 font-bold">{{ question.text }}</p>
                        <div class="flex flex-wrap">
                            <div
                                v-for="(option, cIndex) in question.options"
                                :key="cIndex"
                                class="mb-1 mr-2 flex items-center"
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
                    <div class="mt-4 text-right">
                        <SecondaryButton type="submit">Save</SecondaryButton>
                    </div>
                </form>

                <!-- Table to display user data -->
                <table class="table-auto w-full">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Score</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Render table rows dynamically based on fetched user data -->
                        <tr v-for="(score, index) in scoreData" :key="index">
                            <td>{{ score.user.name }}</td>
                            <td>{{ score.score }}%</td>
                            <td>{{ score.notes }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Modal>
    </div>
</template>
