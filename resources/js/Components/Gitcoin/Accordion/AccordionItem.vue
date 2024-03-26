<template>
    <div class="border-b border-gray-200">
        <h2 class="px-5 py-3 cursor-pointer bg-gray-100" @click="toggle">
            <slot name="heading"></slot>
        </h2>
        <div v-if="isItemOpen" class="p-5">
            <slot name="text"></slot>
        </div>
    </div>
</template>

<script>
export default {
    name: "Item",
    inject: ["toggleSection"],
    props: {
        name: {
            type: String,
            required: true,
        },
        isOpen: {
            type: Boolean,
            default: false,
        },
    },
    data() {
        return {
            isItemOpen: this.isOpen,
        };
    },
    methods: {
        toggle() {
            this.isItemOpen = !this.isItemOpen;
            this.toggleSection(this.name);
        },
    },
    watch: {
        toggleSection: {
            handler(newVal) {
                if (newVal !== this.name) {
                    this.isItemOpen = false;
                }
            },
            deep: true,
        },
    },
};
</script>
