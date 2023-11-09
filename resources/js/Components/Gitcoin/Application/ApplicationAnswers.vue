<script setup>
import { ref, computed } from "vue";
import axios from "axios";
import Modal from "@/Components/Modal.vue";

const openModal = ref(false);
const application = ref(false);
const isLoading = ref(true);

const props = defineProps({
    applicationUuid: String,
});

const fetchApplicationAnswers = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get(
            route("round.application.details", {
                application: props.applicationUuid,
            })
        );

        application.value = response.data.application;
        isLoading.value = false;
    } catch (error) {
        console.error(error); // Handle any errors that occur during the request
    } finally {
        isLoading.value = false;
    }
};

const answers = computed(() => {
    const metadata = application.value?.metadata
        ? JSON.parse(application.value.metadata)
        : {};

    let answers = metadata?.application?.answers
        ? metadata.application.answers
        : [];

    // remove all answers that have answer.hidden = true
    answers = answers.filter((answer) => {
        return !answer.hidden;
    });

    return answers;
});

const toggleModal = () => {
    openModal.value = !openModal.value;
    if (openModal.value) {
        fetchApplicationAnswers();
    }
};
</script>

<template>
    <span>
        <span @click="toggleModal" :disabled="!application" class="pointer">
            <i class="fa fa-question-circle-o" aria-hidden="true"></i>
            <span v-if="isLoading" class="loading-indicator"></span>
        </span>

        <Modal :show="openModal" @close="toggleModal">
            <div class="modal-content text-xs text-gray-500">
                <div v-if="!isLoading">
                    <h2 class="modal-title flex justify-between">
                        Application answers for {{ application.project.title }}
                        <span @click="toggleModal" class="cursor-pointer">
                            <i
                                class="fa fa-times-circle-o"
                                aria-hidden="true"
                            ></i>
                        </span>
                    </h2>

                    <div v-if="answers && !isLoading">
                        <div
                            v-for="(answer, index) in answers"
                            :key="'answers-' + index"
                        >
                            <div
                                class="mb-5"
                                v-if="answer.question && answer.answer"
                            >
                                <div class="font-bold">
                                    {{ answer.question }}
                                </div>
                                <div>
                                    {{ answer.answer }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else>loading..</div>
                </div>

                <div v-else-if="!answers && !isLoading">
                    No application data available.
                </div>

                <div v-else-if="isLoading">Loading application answers...</div>
            </div>
        </Modal>
    </span>
</template>

<style>
/* Your styles here, scoped or otherwise */
</style>
