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
            // No need for 'const self = this;' because 'this' inside arrow function will refer to your Vue instance

            try {
                const response = await axios.get(
                    route("user-preferences.round.toggle", {
                        round: round.uuid,
                    })
                );

                this.selectedRounds = response.data.selectedRounds;
                //TODO::: This emit isn't happening consistently, which prevents the parent from refreshing the application results
                this.$emit("selectedRoundsChanged");
            } catch (error) {
                console.error(error);
            }
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
};
</script>

<template>
    <div>
        <button @click="showRounds = true" v-if="!showRounds">
            <i class="fa fa-plus-circle mr-1" aria-hidden="true"></i>
            Add rounds to your short list.
        </button>
        <button @click="showRounds = false" v-if="showRounds">
            <i class="fa fa-minus-circle mr-1" aria-hidden="true"></i>Hide
        </button>

        <div v-if="showRounds">
            <!-- Section for round selection -->
            <div>
                <!-- Added click event and changed text -->
                <div>
                    <!-- This div will be shown when showRounds is true -->
                    <!-- Added TextInput for search -->
                    <div>
                        <TextInput
                            v-model="search"
                            @keyup.enter="searchRounds"
                            placeholder="Search for rounds to add to your shortlist"
                            style="width: 100%"
                        />
                    </div>
                    <!-- Iterate over the rounds and create a checkbox for each one -->
                    <div
                        v-for="(round, index) in rounds"
                        :key="index"
                        class="mb-2 mr-2"
                        style="display: inline-block"
                    >
                        <span>
                            <input
                                class="mr-1"
                                type="checkbox"
                                :id="`round-${index}`"
                                :value="round.uuid"
                                @change="toggleRound(round)"
                                :checked="
                                    selectedRounds.some(
                                        (selectedRound) =>
                                            selectedRound.uuid === round.uuid
                                    )
                                "
                            />
                            <label :for="`round-${index}`">{{
                                round.name
                            }}</label>
                        </span>
                    </div>
                    <hr class="mb-5" />
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
