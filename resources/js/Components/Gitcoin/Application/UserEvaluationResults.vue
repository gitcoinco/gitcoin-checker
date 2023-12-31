<script>
import { defineComponent } from "vue";
import Modal from "@/Components/Modal.vue";
import { showDateInShortFormat as importedShowDateInShortFormat } from "@/utils";

export default defineComponent({
    data() {
        return {
            openModal: false,
        };
    },
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
        userEvaluationAverage() {
            if (this.application?.evaluation_answers?.length > 0) {
                const total = this.application.evaluation_answers.reduce(
                    (acc, curr) => acc + curr.score,
                    0
                );
                return total / this.application.evaluation_answers.length;
            }
        },
    },
    methods: {
        toggleModal() {
            this.openModal = !this.openModal;
        },
        showDateInShortFormat: importedShowDateInShortFormat,
    },
});
</script>

<template>
    <div class="text-blue-500 hover:underline" v-if="userEvaluationAverage">
        <span @click="toggleModal" class="pointer">
            <i class="fa fa-users" aria-hidden="true"></i>
            {{ userEvaluationAverage }}%
        </span>

        <Modal :show="openModal" @close="toggleModal()">
            <div class="modal-content">
                <h2 class="modal-title flex justify-between">
                    Evaluation Details
                    <span @click="toggleModal" class="cursor-pointer">
                        <i class="fa fa-times-circle-o" aria-hidden="true"></i>
                    </span>
                </h2>

                <table
                    class="table-auto w-full"
                    v-if="application.evaluation_answers.length > 0"
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
                            v-for="(
                                answer, index
                            ) in application.evaluation_answers"
                            :key="index"
                        >
                            <td>
                                {{ showDateInShortFormat(answer.updated_at) }}
                            </td>
                            <td>{{ answer.user.name }}</td>
                            <td>{{ answer.score }}</td>
                            <td>{{ answer.notes }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Modal>
    </div>
</template>
