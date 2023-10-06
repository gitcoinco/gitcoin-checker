<template>
    <div>
        <div
            v-for="(notification, index) in uniqueNotifications"
            :key="`notification-${index}`"
        >
            <div
                v-if="notification.type === 'success'"
                class="items-center bg-green-500 text-white text-sm font-bold px-4 py-3 my-2"
                role="alert"
            >
                <p>{{ notification.message }}</p>
            </div>

            <div
                v-if="notification.type === 'info'"
                class="items-center bg-blue-500 text-white text-sm font-bold px-4 py-3 my-2"
                role="alert"
            >
                <p>{{ notification.message }}</p>
            </div>

            <div
                v-if="notification.type === 'error'"
                class="items-center bg-red-500 text-white text-sm font-bold px-4 py-3 my-2"
                role="alert"
            >
                <p>{{ notification.message }}</p>
            </div>
        </div>
    </div>
</template>

<script>
import { defineComponent, computed } from "vue";

export default defineComponent({
    computed: {
        uniqueNotifications() {
            return Array.from(
                new Set(
                    this.$page.props.flash.notifications.map(JSON.stringify)
                )
            ).map(JSON.parse);
        },
    },
});
</script>
