<script setup>
import { ref, onMounted, defineEmits } from "vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, useForm, usePage, Link, router } from "@inertiajs/vue3";

const emit = defineEmits(["selectedRoundsChanged"]);

const rounds = ref([]);
const selectedRounds = ref([]);
const showRounds = ref(false);
const search = ref("");

async function searchRounds() {
    try {
        const response = await axios.get(
            `${route("user-preferences.rounds.search")}?search=${search.value}`
        );
        rounds.value = response.data.rounds;
        selectedRounds.value = response.data.selectedRounds;
    } catch (error) {
        console.error(error);
    }
}

const toggleRound = async (round) => {
    try {
        const response = await axios.get(
            route("user-preferences.round.toggle", { round: round.uuid })
        );
        selectedRounds.value = response.data.selectedRounds;
        emit("selectedRoundsChanged");
    } catch (error) {
        console.error(error);
    }
};

onMounted(async () => {
    try {
        await searchRounds();
    } catch (error) {
        console.error(error);
    }
});
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
