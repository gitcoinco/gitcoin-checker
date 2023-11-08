<script>
import { ref } from "vue";
import Modal from "@/Components/Modal.vue";

export default {
    components: {
        Modal,
    },
    props: {
        application: {
            type: Object,
            required: true,
        },
    },
    computed: {
        answers() {
            const metadata = JSON.parse(this.application.metadata);
            return metadata.application.answers.filter(
                (answer) => !answer.hidden
            );
        },
    },
    setup() {
        const openModal = ref(false); // Using the Composition API

        function toggleModal() {
            openModal.value = !openModal.value;
        }

        return {
            openModal,
            toggleModal,
        };
    },
};
</script>
<template>
    <span>
        <span @click="toggleModal" :disabled="!application" class="pointer">
            <i class="fa fa-question-circle-o" aria-hidden="true"></i>
        </span>

        <Modal :show="openModal" @close="toggleModal">
            <div class="modal-content">
                <h2 class="modal-title flex justify-between">
                    Application answers for {{ application.project.title }}
                    <span @click="toggleModal" class="cursor-pointer">
                        <i class="fa fa-times-circle-o" aria-hidden="true"></i>
                    </span>
                </h2>

                <!-- You can add more data to display in the modal here -->
                <div v-if="answers">
                    <table class="score-table" v-if="answers">
                        <thead>
                            <tr>
                                <th>Question</th>
                                <th>Answer</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(answer, index) in answers"
                                :key="'answers-' + index"
                            >
                                <td class="score-value" v-if="answer.question">
                                    {{ answer.question }}
                                </td>
                                <td v-if="answer.answer">
                                    {{ answer.answer }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-else>No application data available.</div>
            </div>
        </Modal>
    </span>
</template>

<style>
/* Your styles here, scoped or otherwise */
</style>
