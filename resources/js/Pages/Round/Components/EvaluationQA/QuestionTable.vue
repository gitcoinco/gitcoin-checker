<template>
    <table class="min-w-full bg-white">
        <thead>
            <tr>
                <th class="py-2 px-4">Question</th>
                <th class="py-2 px-4">Type</th>
                <th class="py-2 px-4">Options</th>
                <th class="py-2 px-4">Weighting</th>
                <th class="py-2 px-4">Actions</th>
            </tr>
        </thead>
        <tbody>
            <QuestionRow
                v-for="(question, index) in questions"
                :key="index"
                :question="question"
                :index="index"
                @updateQuestion="updateQuestion"
                @removeQuestion="removeQuestion"
                @updateWeighting="updateWeighting"
                @moveQuestion="moveQuestion"
            />
        </tbody>
    </table>
</template>

<script>
import QuestionRow from "./QuestionRow.vue";

export default {
    components: {
        QuestionRow,
    },
    props: {
        questions: Array,
        updateQuestionMethod: Function,
        removeQuestionMethod: Function,
        updateWeightingMethod: Function,
        moveQuestionMethod: Function,
    },
    methods: {
        updateQuestion(index, field, value) {
            this.updateQuestionMethod(index, field, value);
        },
        removeQuestion(index) {
            this.removeQuestionMethod(index);
        },
        updateWeighting(index, value) {
            this.updateWeightingMethod(index, value);
        },
        moveQuestion(index, direction) {
            this.moveQuestionMethod(index, direction);
        },
    },
};
</script>
