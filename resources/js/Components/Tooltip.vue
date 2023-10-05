<template>
    <div
        class="relative inline-block"
        @mouseenter="showTooltip"
        @mouseleave="hideTooltip"
    >
        <!-- Slot for the triggering element -->
        <slot></slot>

        <!-- Tooltip content -->
        <div
            v-if="isVisible"
            class="absolute top-full left-1/2 transform -translate-x-1/2 mt-2 p-2 bg-gray-800 text-white text-xs rounded shadow-lg tooltip-content"
        >
            <slot name="content"></slot>
        </div>
    </div>
</template>

<script>
export default {
    data() {
        return {
            isVisible: false,
        };
    },
    methods: {
        showTooltip() {
            this.isVisible = true;
        },
        hideTooltip() {
            this.isVisible = false;
        },
    },
};
</script>

<style scoped>
.tooltip-content {
    min-width: 200px;
    max-width: 300px;
}
</style>
