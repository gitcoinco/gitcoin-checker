<template>
    <div
        class="relative inline-block cursor-pointer"
        @mouseenter="showTooltip"
        @mouseleave="startHideTimeout"
    >
        <!-- Slot for the triggering element -->
        <slot></slot>

        <!-- Tooltip content -->
        <div
            v-if="isVisible"
            @mouseenter="extendHideTimeout"
            @mouseleave="startHideTimeout"
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
            hideTimeout: null,
        };
    },
    methods: {
        showTooltip() {
            this.isVisible = true;
            this.clearHideTimeout();
        },
        startHideTimeout() {
            this.clearHideTimeout(); // Clear any existing timeouts
            this.hideTimeout = setTimeout(() => {
                this.isVisible = false;
            }, 1000);
        },
        clearHideTimeout() {
            if (this.hideTimeout) {
                clearTimeout(this.hideTimeout);
            }
        },
        extendHideTimeout() {
            this.clearHideTimeout(); // Clear the current timeout
            //            this.startHideTimeout(); // Start a new timeout
        },
    },
};
</script>

<style scoped>
.tooltip-content {
    min-width: 200px;
    max-width: 300px;
    z-index: 9999;
}
</style>
