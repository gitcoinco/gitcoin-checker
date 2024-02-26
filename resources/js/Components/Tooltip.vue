<template>
    <div
        class="relative inline-block cursor-pointer"
        @mouseenter="showTooltip"
        @mouseleave="startHideTimeout"
    >
        <!-- Slot for the triggering element -->
        <span class="underline dotted">
            <slot></slot>
        </span>
        <!-- Tooltip content -->
        <div
            v-if="isVisible"
            @mouseenter="extendHideTimeout"
            @mouseleave="startHideTimeout"
            :class="[
                'absolute mt-2 p-2 bg-gray-800 text-white text-xs rounded shadow-lg tooltip-content',
                positionClass,
            ]"
        >
            <slot name="content"></slot>
        </div>
    </div>
</template>

<script>
export default {
    props: {
        position: {
            type: String,
            default: "north", // Default position
        },
    },

    data() {
        return {
            isVisible: false,
            hideTimeout: null,
        };
    },
    computed: {
        positionClass() {
            switch (this.position) {
                case "north":
                    return "bottom-full mb-2 left-1/2 transform -translate-x-1/2";
                case "south":
                    return "top-full mt-2 left-1/2 transform -translate-x-1/2";
                case "east":
                    return "left-full ml-2 top-1/2 transform -translate-y-1/2";
                case "west":
                    return "right-full mr-2 top-1/2 transform -translate-y-1/2";
                default:
                    return "top-full mt-2 left-1/2 transform -translate-x-1/2"; // Default position
            }
        },
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
