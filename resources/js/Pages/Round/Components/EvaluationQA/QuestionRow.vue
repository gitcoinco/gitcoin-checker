<template>
    <tr>
        <td class="py-2 px-4">
            <input
                v-model="question.text"
                @input="updateQuestion('text', $event.target.value)"
            />
        </td>
        <td class="py-2 px-4">{{ question.type }}</td>
        <td class="py-2 px-4" v-if="question.type === 'select'">
            <input
                v-model="question.options"
                @input="
                    updateQuestion('options', $event.target.value.split(', '))
                "
            />
        </td>
        <td class="py-2 px-4">
            <input
                type="number"
                v-model="question.weighting"
                @input="updateWeighting($event.target.value)"
            />
        </td>
        <td class="py-2 px-4">
            <SecondaryButton @click="removeQuestion()">Remove</SecondaryButton>
            <button @click="moveQuestion(-1)">&#8679;</button>
            <button @click="moveQuestion(1)">&#8681;</button>
        </td>
    </tr>
</template>

<script>
import SecondaryButton from "@/Components/SecondaryButton.vue";

export default {
    components: {
        SecondaryButton,
    },
    props: {
        question: Object,
        index: Number,
    },
    methods: {
        updateQuestion(field, value) {
            this.$emit("updateQuestion", this.index, field, value);
        },
        updateOptions(event) {
            this.$emit(
                "updateQuestion",
                this.index,
                "options",
                event.target.value.split(", ")
            );
        },
        updateWeighting(value) {
            this.$emit("updateWeighting", this.index, value);
        },
        remove() {
            this.$emit("removeQuestion", this.index);
        },
        moveUp() {
            this.$emit("moveQuestion", this.index, -1);
        },
        moveDown() {
            this.$emit("moveQuestion", this.index, 1);
        },
    },
};
</script>
