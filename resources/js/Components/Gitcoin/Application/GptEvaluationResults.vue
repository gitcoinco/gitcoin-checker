<script>
import { ref } from "vue";
import Tooltip from "@/Components/Tooltip.vue";
import Modal from "@/Components/Modal.vue";
import ResultsData from "@/Components/Gitcoin/Application/ResultsData.vue";
import { scoreTotal } from "@/utils.js";

export default {
    data() {
        return {
            openModal: false,
        };
    },
    components: {
        Tooltip,
        Modal,
        ResultsData,
    },
    props: {
        application: {
            type: Object,
            required: true,
        },
        loadingStates: {
            type: Object,
            default: () => ({}),
        },
    },
    computed: {
        loading() {
            return this.loadingStates[this.application.id];
        },
        hasResults() {
            return (
                this.application.results && this.application.results.length > 0
            );
        },
        haslatest_prompt() {
            return this.application.latest_prompt;
        },
        totalScore() {
            // Implement the 'scoreTotal' function logic here or pass as a prop
            return scoreTotal(this.application.results);
        },
        showTooltip() {
            return (
                this.hasResults &&
                this.haslatest_prompt &&
                this.application.results[0].prompt_id !==
                    this.application.latest_prompt.id
            );
        },
        isModalOpen() {
            // You would need to manage the state of modal being open in your data or store
            //            return this.$store.state.openModalId === this.application.id; // Or any other logic you use to handle modals
        },
        firstResult() {
            return this.application.project.results[0];
        },
    },
    methods: {
        toggleModal() {
            this.openModal = !this.openModal;
        },
        // toggleModal() {
        //     // Logic to toggle modal
        //     this.$emit("toggle-modal", this.application.id);
        // },
    },
};
</script>

<!-- EvaluationResults.vue -->
<template>
    <div>
        <span v-if="loadingStates[application.id]" class="ml-2">
            <i class="fa fa-spinner fa-spin"></i>
        </span>
        <span v-else>
            <span
                v-if="
                    application.results.length > 0 && application.latest_prompt
                "
            >
                <a
                    href="#"
                    class="text-blue-500 hover:underline"
                    @click="toggleModal()"
                >
                    <span>
                        <i
                            class="fa fa-android mr-1"
                            aria-hidden="true"
                            style="display: inherit"
                        ></i
                        >{{ totalScore }}
                        <Tooltip
                            v-if="
                                application.results &&
                                application.results.length > 0 &&
                                application.latest_prompt &&
                                application.results[0].prompt_id !==
                                    application.latest_prompt.id
                            "
                        >
                            <i
                                class="fa fa-exclamation-circle text-red-500"
                                aria-hidden="true"
                            ></i>
                            <template #content>
                                This score was calculated using an older version
                                of the scoring criteria.
                            </template>
                        </Tooltip>
                    </span>
                </a>
            </span>
            <Modal
                v-if="application.results && application.results.length > 0"
                :show="openModal"
                @close="toggleModal()"
            >
                <div class="modal-content">
                    <h2 class="modal-title">
                        Score Details for
                        {{ application.project.title }}
                    </h2>
                    <ResultsData :result="application.results[0]" />
                </div>
            </Modal>
        </span>
    </div>
</template>

<style>
/* Add any scoped or general styles you need for this component */
</style>
