<script setup>
import { onMounted, ref } from "vue";

defineProps({
    modelValue: String,
    rows: {
        type: Number,
        default: undefined,
    },
});

const emit = defineEmits(["update:modelValue"]);

const textarea = ref(null);

onMounted(() => {
    if (textarea.value.hasAttribute("autofocus")) {
        textarea.value.focus();
    }
    if (rows === undefined) {
        autoResize();
    }
});

function onInput(event) {
    autoResize();
    emit("update:modelValue", event.target.value);
}

function autoResize() {
    textarea.value.style.height = "auto";
    textarea.value.style.height = textarea.value.scrollHeight + "px";
}

defineExpose({ focus: () => textarea.value.focus(), onInput });
</script>

<template>
    <textarea
        ref="textarea"
        class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
        :value="modelValue"
        @input="onInput"
        style="width: 100%"
        :rows="rows"
    ></textarea>
</template>
