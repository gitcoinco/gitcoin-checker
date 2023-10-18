<script setup>
import { defineProps, defineEmits } from "vue";

// Define props with 'modelValue' for v-model
const props = defineProps({
    modelValue: Number, // This is used for v-model binding from the parent
    min: Number,
    max: Number,
});

// Define emit function for emitting events
const emit = defineEmits(["update:modelValue"]);

const updateValue = (event) => {
    const newValue = event.target.valueAsNumber;
    // Emit the 'update:modelValue' event, as per v-model's contract in Vue 3
    emit("update:modelValue", newValue);
};
</script>

<template>
    <input
        type="range"
        :min="min"
        :max="max"
        :value="modelValue"
        @input="updateValue"
        class="slider bg-gray-300 focus:outline-none transition duration-150 hover:bg-gray-400 focus:bg-indigo-300 w-full"
    />
</template>
