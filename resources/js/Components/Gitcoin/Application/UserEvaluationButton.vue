<script setup>
import { ref, defineProps, defineEmits } from "vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import Modal from "@/Components/Modal.vue";
import { showDateInShortFormat } from "@/utils";
import { Link } from "@inertiajs/vue3";
import TextareaInput from "@/Components/TextareaInput.vue";
1;
const emit = defineEmits(["evaluatedApplication"]);

const selectedAnswers = ref([]);
const notes = ref(null);

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
                <div @click="toggleModal">Human Evaluation</div>
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
            <div class="modal-content">
                <h2 class="modal-title flex justify-between">
                    <span>Human Evaluations</span>
                    <span @click="toggleModal" class="cursor-pointer">
                        <i class="fa fa-times-circle-o" aria-hidden="true"></i>
                    </span>
                </h2>
                <form
                    @submit.prevent="submitEvaluation"
                    class="mb-4"
                    v-if="
                        application?.round?.evaluation_questions?.questions
                            ?.length > 0
                    "
                >
                    <div v-if="loggedInUserAnswers" class="mb-4">
                        Your answers from
                        {{
                            showDateInShortFormat(
                                loggedInUserAnswers.updated_at
                            )
                        }}:
                    </div>
                    <div>
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
                                        application.round.evaluation_questions
                                            .questions
                                    ).length
                                "
                                :class="{
                                    'opacity-50 cursor-not-allowed':
                                        selectedAnswers.length !==
                                        JSON.parse(
                                            application.round
                                                .evaluation_questions.questions
                                        ).length,
                                }"
                            >
                                Save
                            </PrimaryButton>
                        </div>
                    </div>
                </form>
                <div v-else>No questions specified</div>

                <table
                    class="table-auto w-full"
                    v-if="userEvaluationAnswers.length > 0"
                >
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>User</th>
                            <th>Score</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(answer, index) in userEvaluationAnswers"
                            :key="index"
                        >
                            <td>
                                {{ showDateInShortFormat(answer.updated_at) }}
                            </td>
                            <td>{{ answer.user.name }}</td>
                            <td>{{ answer.score }}</td>
                            <td>
                                {{ answer.notes }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Modal>
    </div>
</template>
