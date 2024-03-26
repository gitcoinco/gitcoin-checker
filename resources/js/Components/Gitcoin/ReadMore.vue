<template>
    <div>
        <!-- Display truncated or full content based on state -->
        <span v-if="!isReadMore" v-html="truncatedContent"></span>
        <span v-else v-html="fullContent"></span>

        <!-- Toggle link -->
        <a href="#" @click.prevent="toggleReadMore" v-if="showReadMoreLink">
            {{ isReadMore ? "Read less" : "Read more" }}
        </a>
    </div>
</template>

<script>
export default {
    name: "ReadMore",
    props: {
        htmlContent: String,
        words: {
            type: Number,
            required: true,
        },
    },
    data() {
        return {
            isReadMore: false,
            // Initially assume we need to show the link, adjust based on actual content
            showReadMoreLink: true,
        };
    },
    computed: {
        fullContent() {
            // If htmlContent prop is provided, use it; otherwise, extract slot's HTML
            return this.htmlContent || this.extractSlotHtml();
        },
        truncatedContent() {
            const div = document.createElement("div");
            div.innerHTML = this.fullContent;
            const fullText = div.textContent || div.innerText || "";

            const words = fullText.split(/\s+/);
            if (words.length <= this.words) {
                // If the content is short enough, no need for truncation or the read more link
                this.showReadMoreLink = false;
                return this.fullContent; // Show full content as is, no truncation needed
            }

            // Truncate the text and append ellipsis
            const truncatedText = words.slice(0, this.words).join(" ") + "...";
            this.showReadMoreLink = true; // Ensure link is shown for truncated content
            // For simplicity, use the truncated text as the content when 'read more' is not activated
            // This approach maintains text integrity but removes HTML formatting in truncated view
            return truncatedText;
        },
    },
    methods: {
        toggleReadMore(event) {
            this.isReadMore = !this.isReadMore;
        },
        extractSlotHtml() {
            // Extract HTML from slot. This simplistic method assumes single root node text content.
            // You may need a more complex method for handling multiple nodes or deeper structures.
            return this.$slots.default ? this.$slots.default()[0].children : "";
        },
    },
};
</script>
