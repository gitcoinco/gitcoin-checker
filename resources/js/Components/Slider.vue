<template>
    <input
        type="range"
        :min="min"
        :max="max"
        :value="modelValue"
        @input="updateValue"
        @change="updateValue"
        class="slider"
    />
</template>

<script setup>
import { defineProps, defineEmits } from "vue";

const props = defineProps({
    modelValue: Number,
    min: Number,
    max: Number,
});

const emit = defineEmits(["update:modelValue"]);

const updateValue = (event) => {
    const newValue = event.target.valueAsNumber;
    emit("update:modelValue", newValue);
};
</script>

<!-- Add this style section to your component -->
<style scoped>
.slider {
    -webkit-appearance: none; /* Necessary to allow custom styling for WebKit browsers */
    width: 100%;
    height: 15px; /* Set height of the slider */
    background: #d3d3d3;
    outline: none;
    opacity: 0.7;
    transition: opacity 0.2s;
}

.slider:hover {
    opacity: 1; /* Fully visible when the user hovers */
}

.slider::-webkit-slider-thumb {
    -webkit-appearance: none;
    appearance: none;
    width: 25px; /* Set a specific slider handle width */
    height: 25px; /* Slider handle height - makes it taller */
    background: #4caf50; /* Green background */
    cursor: pointer; /* Cursor on hover */
}

.slider::-moz-range-thumb {
    width: 25px; /* Set a specific slider handle width */
    height: 25px; /* Slider handle height - makes it taller */
    background: #4caf50; /* Green background */
    cursor: pointer; /* Cursor on hover */
}
</style>
