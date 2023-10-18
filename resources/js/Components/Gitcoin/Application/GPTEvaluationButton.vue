<script>
import Tooltip from "@/Components/Tooltip.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";

export default {
    components: {
        Tooltip,
        SecondaryButton,
    },
    props: {
        application: {
            type: Object,
            required: true,
        },
        loadingStates: {
            type: Object,
            required: true,
        },
    },
    computed: {
        shouldShowEvaluationLink() {
            const noResults =
                !this.application.results ||
                this.application.results.length === 0;
            const hasResults =
                this.application.results &&
                this.application.results.length > 0 &&
                this.application.results[0].prompt_id !==
                    this.application.latest_prompt.id;

            return noResults || hasResults;
        },
        isLoading() {
            return this.loadingStates[this.application.id];
        },
    },
    methods: {
        evaluateApplication(event) {
            // Prevent default action for click events
            event.preventDefault();

            /**
             * This function should do whatever it needs to evaluate the application.
             * It's a placeholder representing the method passed via event from the parent.
             * Ideally, you would emit an event here to the parent component, handling the logic there.
             */
            this.$emit("evaluate-application", this.application);
        },
        roundPrompt(round) {
            /**
             * Similar to above, this method is a placeholder for the actual logic.
             * Emit an event to the parent component to handle the action.
             */
            this.$emit("round-prompt", round);
        },
    },
};
</script>

<template>
    <div>
        <!-- First condition -->
        <template v-if="application.latest_prompt">
            <span v-if="shouldShowEvaluationLink">
                <a
                    @click="evaluateApplication($event)"
                    href="#"
                    class="text-blue-500 hover:underline"
                    :disabled="isLoading"
                >
                    GPT Evaluation
                </a>
            </span>
        </template>

        <!-- Second condition -->
        <template v-else>
            <Tooltip>
                <i
                    class="fa fa-exclamation-circle text-red-500"
                    aria-hidden="true"
                ></i>
                <template #content>
                    Cannot evaluate if a prompt is not set for this round.<br /><br />
                    <SecondaryButton
                        @click="roundPrompt(application.round)"
                        class="text-blue-500 hover:underline"
                    >
                        Set Evaluation Criteria
                    </SecondaryButton>
                </template>
            </Tooltip>
        </template>
    </div>
</template>

<!-- Add your styles here (if any) -->
<style scoped></style>
