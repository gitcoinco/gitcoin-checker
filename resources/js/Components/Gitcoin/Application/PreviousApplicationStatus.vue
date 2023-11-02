<script>
import { ref } from "vue";
import Modal from "@/Components/Modal.vue";
import { showDateInShortFormat, applicationStatusIcon } from "@/utils";

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
        applications() {
            return this.application.project.applications;
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
            showDateInShortFormat,
            applicationStatusIcon,
        };
    },
};
</script>
<template>
    <span>
        <span
            @click="toggleModal"
            class="pointer text-blue-500 hover:underline"
        >
            {{ applications.length }} application<span
                v-if="applications.length > 1"
                >s</span
            >
        </span>

        <Modal :show="openModal" @close="toggleModal">
            <div class="modal-content">
                <h2 class="modal-title">
                    Application answers for {{ application.project.title }}
                </h2>

                <!-- You can add more data to display in the modal here -->
                <div v-if="applications">
                    <table class="score-table" v-if="applications">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Round</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(application, index) in applications"
                                :key="'answers-' + index"
                            >
                                <td>
                                    {{
                                        showDateInShortFormat(
                                            application.created_at,
                                            true
                                        )
                                    }}
                                </td>
                                <td class="score-value">
                                    <a
                                        :href="
                                            route(
                                                'round.show',
                                                application.round
                                            )
                                        "
                                        class="text-blue-500 hover:underline"
                                    >
                                        {{ application.round.name }}
                                    </a>
                                </td>
                                <td>
                                    <span
                                        v-html="
                                            applicationStatusIcon(
                                                application.status
                                            )
                                        "
                                    ></span>
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