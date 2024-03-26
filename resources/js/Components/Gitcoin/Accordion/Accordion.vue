<template>
    <div>
        <slot></slot>
    </div>
</template>

<script>
export default {
    name: "Accordion",
    provide() {
        return {
            toggleSection: this.toggleSection,
        };
    },
    data() {
        return {
            openSection: null,
        };
    },
    methods: {
        toggleSection(section) {
            this.openSection = this.openSection === section ? null : section;
        },
    },
};
</script>
