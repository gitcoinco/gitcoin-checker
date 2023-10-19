<script>
import { defineComponent } from "vue";
import Modal from "@/Components/Modal.vue";

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
        userScoreAverage() {
            if (!this.application.user_scores.length > 0) {
                return null;
            }
            let total = 0;
            for (let i = 0; i < this.application.user_scores.length; i++) {
                total += this.application.user_scores[i].score;
            }
            return total / this.application.user_scores.length;
        },
    },
    methods: {
        toggleModal() {
            this.openModal = !this.openModal;
        },
    },
});
</script>

<template>
    <div class="text-blue-500 hover:underline" v-if="userScoreAverage">
        <span @click="toggleModal" class="pointer">
            <i class="fa fa-users" aria-hidden="true"></i>
            {{ userScoreAverage }}%
        </span>

        <Modal :show="openModal" @close="toggleModal()">
            <div class="modal-content">
                <h2 class="modal-title">Score Details</h2>
                <table class="table-auto w-full">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Score</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(score, index) in application.user_scores"
                            :key="index"
                        >
                            <td>{{ score.user.name }}</td>
                            <td>{{ score.score }}</td>
                            <td>{{ score.notes }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Modal>
    </div>
</template>
