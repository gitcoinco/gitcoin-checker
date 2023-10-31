<template>
    <div>
        <label for="question">Add Question:</label>
        <input v-model="newQuestion" placeholder="Enter a question" />
        <select v-model="newQuestionType">
            <option value="radio">Radio</option>
        </select>
        <input
            disabled
            v-if="newQuestionType === 'radio'"
            v-model="newOptions"
            placeholder="Enter options separated by comma"
        />
        <PrimaryButton @click="addQuestion">Add</PrimaryButton>
    </div>
</template>

<script>
import { ref, onMounted } from "vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";

export default {
    components: {
        PrimaryButton,
    },
    props: {
        addQuestionMethod: Function,
    },
    setup(props) {
        const newQuestion = ref("");
        const newQuestionType = ref("radio");
        const newOptions = ref("");

        onMounted(() => {
            newOptions.value = "Yes, No, Uncertain";
        });

        const addQuestion = () => {
            props.addQuestionMethod(
                newQuestion.value,
                newQuestionType.value,
                newOptions.value
            );
            newQuestion.value = "";
            newQuestionType.value = "radio";
            // newOptions.value = "";
        };

        return {
            newQuestion,
            newQuestionType,
            newOptions,
            addQuestion,
        };
    },
};
</script>
