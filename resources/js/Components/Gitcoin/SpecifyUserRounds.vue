<script setup>
import { ref, onMounted, defineEmits } from "vue";
import TextInput from "@/Components/TextInput.vue";
import Modal from "@/Components/Modal.vue";
import { Head, useForm, usePage, Link, router } from "@inertiajs/vue3";
import { showDateInShortFormat } from "@/utils.js";

const emit = defineEmits(["selected-rounds-changed"]);

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
        emit("selected-rounds-changed");
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
            <i class="fa fa-filter mr-1" aria-hidden="true"></i>
        </button>
        <button @click="showRounds = false" v-if="showRounds">
            <i class="fa fa-minus-circle mr-1" aria-hidden="true"></i>Hide
        </button>

        <Modal :show="showRounds" @close="showRounds = false">
            <div class="p-5">
                <div class="modal-header">
                    <div class="flex justify-between">
                        <h2 class="text-2xl">Select Rounds</h2>
                        <button
                            class="close text-4xl"
                            @click="showRounds = false"
                        >
                            &times;
                        </button>
                    </div>
                </div>
                <div class="modal-body">
                    <TextInput
                        v-model="search"
                        @keyup.enter="searchRounds"
                        placeholder="Search for rounds"
                        class="mb-3 w-full"
                    />

                    <!-- Display selected rounds -->

                    <div class="flex flex-wrap -mx-2 mb-5">
                        <div
                            v-for="(round, index) in rounds"
                            :key="index"
                            class="mb-3 flex items-center w-1/2 px-2"
                        >
                            <div>
                                <input
                                    type="checkbox"
                                    :id="`round-${index}`"
                                    :value="round.uuid"
                                    @change="toggleRound(round)"
                                    :checked="
                                        selectedRounds.some(
                                            (selectedRound) =>
                                                selectedRound.uuid ===
                                                round.uuid
                                        )
                                    "
                                    class="mr-1"
                                />
                                <label :for="`round-${index}`" class="text-xs"
                                    >{{ round.name }}<br />

                                    <span class="text-xs italic"
                                        >({{
                                            showDateInShortFormat(
                                                round.donations_start_time
                                            )
                                        }}
                                        - {{ round.chain.name }})</span
                                    ></label
                                >
                            </div>
                        </div>
                    </div>

                    <div v-if="selectedRounds.length > 0">
                        <div class="flex flex-wrap -mx-2">
                            <div
                                v-for="(round, index) in selectedRounds"
                                :key="index"
                                class="w-1/2"
                            >
                                <div class="flex items-center">
                                    <div class="flex-grow">
                                        <button @click="toggleRound(round)">
                                            <i
                                                class="fa fa-trash"
                                                aria-hidden="true"
                                            ></i>
                                        </button>
                                        {{ round.name }}<br />
                                        <span
                                            class="text-xs italic"
                                            v-if="round?.chain?.chain_id"
                                            >({{
                                                showDateInShortFormat(
                                                    round.donations_start_time
                                                )
                                            }}
                                            {{ round.chain.name }})</span
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Modal>
    </div>
</template>

<style scoped>
/* Your styles here. Consider styling the h2 to look more like a clickable link or button,
   to make it intuitive for users that they should click on it to see the rounds. */
</style>
