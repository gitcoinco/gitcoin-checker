<template>
    <table class="score-table text-xs" v-if="result">
        <thead>
            <tr>
                <th>Score</th>
                <th>Criteria</th>
                <th>Reason</th>
            </tr>
        </thead>
        <tbody>
            <tr
                v-for="(res, index) in JSON.parse(result.results_data)"
                :key="'resultsdata-' + result.id + '-' + index"
            >
                <td class="score-value">
                    {{ res.score }}
                </td>
                <td>
                    {{ res.criteria }}
                </td>
                <td>
                    {{ res.reason }}
                </td>
            </tr>
        </tbody>
    </table>
</template>

<script>
export default {
    props: {
        result: {
            type: Object,
            required: true,
        },
    },
    computed: {},
};
</script>

<style scoped>
/* You can add your styles here if needed */
</style>
