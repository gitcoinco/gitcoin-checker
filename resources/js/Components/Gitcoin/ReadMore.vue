<template>
    <div>
        <!-- Display either the truncated text or the full text based on the state -->
        <span v-if="!isReadMore">{{ truncatedText }}</span>
        <span v-else><slot></slot></span>

        <!-- Show Read more or Read less link -->
        <a href="#" @click="toggleReadMore" v-if="showReadMoreLink">{{
            isReadMore ? "Read less" : "Read more"
        }}</a>
    </div>
</template>

<script>
export default {
    name: "ReadMore",
    props: {
        words: {
            type: Number,
            required: true,
        },
    },
    data() {
        return {
            isReadMore: false, // State to toggle between showing partial/full text
        };
    },
    computed: {
        // Compute the truncated text based on the 'words' prop
        truncatedText() {
            const originalText = this.$slots.default()[0].children;
            if (!originalText) return "";
            const wordsArray = originalText.trim().split(/\s+/);
            if (wordsArray.length <= this.words) {
                this.showReadMoreLink = false; // No need for the link if text is short
                return originalText;
            }
            this.showReadMoreLink = true;
            return wordsArray.slice(0, this.words).join(" ") + "...";
        },
        showReadMoreLink: {
            get() {
                return this.isReadMoreLink;
            },
            set(value) {
                this.isReadMoreLink = value;
            },
        },
    },
    methods: {
        toggleReadMore(event) {
            event.preventDefault();
            this.isReadMore = !this.isReadMore;
        },
    },
};
</script>
