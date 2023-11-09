<script setup>
const props = defineProps({
    application: Object,
});

const reviewedBy = () => {
    let list = [];
    if (props.application?.results.length > 0) {
        list.push("GPT");
    }

    if (props.application?.evaluation_answers?.length > 0) {
        for (let i = 0; i < props.application.evaluation_answers.length; i++) {
            let name = props.application.evaluation_answers[i].user.name;
            if (name.includes(" ")) {
                let splitName = name.split(" ");
                name = `${splitName[0]} ${splitName[1].charAt(0)}`;
            }
            list.push(name);
        }
    }
    return list.join(", ");
};
</script>

<template>
    <div class="text-xs text-gray-400">
        {{ reviewedBy() }}
    </div>
</template>
