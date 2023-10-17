<script>
import TextInput from "@/Components/TextInput.vue";

export default {
    components: {
        TextInput,
    },
    data() {
        return {
            rounds: [],
            selectedRounds: [],
            paginationLinks: [],
            showRounds: false, // Initially, rounds selection is not visible
            search: "", // Added search data property
        };
    },
    methods: {
        async fetchRounds(pageUrl) {},
        async searchRounds() {
            // Added searchRounds method
            axios
                .get(
                    route("user-preferences.rounds.search") +
                        `?search=${this.search}`
                )
                .then((response) => {
                    this.rounds = response.data.rounds;
                    this.selectedRounds = response.data.selectedRounds;
                });
        },
        async toggleRound(round) {
            // Added toggleRound method
            axios
                .get(
                    route("user-preferences.round.toggle", {
                        round: round.uuid,
                    })
                )
                .then((response) => {
                    this.selectedRounds = response.data.selectedRounds;
                });
        },
    },
    created() {
        axios
            .get(route("user-preferences.rounds.search", {}))
            .then((response) => {
                this.rounds = response.data.rounds;
                this.selectedRounds = response.data.selectedRounds;
            })
            .finally(() => {});
    },
    watch: {
        selectedRounds: function (newVal) {
            this.$emit("selectedRoundsChanged", newVal);
        },
    },
};
</script>

<template>
    <div>
        <button @click="showRounds = true" v-if="!showRounds">
            Add rounds to your short list.
        </button>
        <button @click="showRounds = false" v-if="showRounds">Hide</button>

        <div v-if="showRounds">
            <!-- Section for round selection -->
            <div>
                <!-- Added click event and changed text -->
                <div>
                    <!-- This div will be shown when showRounds is true -->
                    <!-- Added TextInput for search -->
                    <TextInput
                        v-model="search"
                        @keyup.enter="searchRounds"
                        placeholder="Search for rounds to add to your shortlist"
                    />
                    <!-- Iterate over the rounds and create a checkbox for each one -->
                    <div
                        v-for="(round, index) in rounds"
                        :key="index"
                        class="mb-2"
                    >
                        <input
                            type="checkbox"
                            :id="`round-${index}`"
                            :value="round.id"
                            @change="toggleRound(round)"
                            :checked="
                                selectedRounds.some(
                                    (selectedRound) =>
                                        selectedRound.uuid === round.uuid
                                )
                            "
                        />
                        <label :for="`round-${index}`">{{ round.name }}</label>
                    </div>
                </div>
            </div>

            <!-- Display selected rounds -->
            <div v-if="selectedRounds.length > 0">
                <!-- This div will be shown when there are selected rounds -->
                <ul style="display: flex; flex-wrap: wrap; padding: 0">
                    <li
                        v-for="(round, index) in selectedRounds"
                        :key="index"
                        style="margin-right: 10px"
                    >
                        {{ round.name }}
                        <button @click="toggleRound(round)">
                            <i class="fa fa-trash" aria-hidden="true"></i>
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Your styles here. Consider styling the h2 to look more like a clickable link or button,
   to make it intuitive for users that they should click on it to see the rounds. */
</style>
