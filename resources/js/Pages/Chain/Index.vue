<script setup>
import { ref, watch, reactive, toRefs } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, useForm, usePage, Link } from "@inertiajs/vue3";

const chains = reactive(usePage().props.chains.valueOf());

const form = useForm({
    chains: chains,
});

const updateChains = () => {
    form.post(route("chain.update-all", {}), {
        onSuccess: (response) => {},
        onError: (error) => {},
    });
};
</script>

<template>
    <AppLayout title="Profile">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Chains
            </h2>
        </template>

        <div>
            <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
                <table v-if="chains && chains.length > 0">
                    <thead>
                        <tr>
                            <th>Chain Id</th>
                            <th>Name</th>
                            <th>RPC Endpoint</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(chain, index) in chains" :key="index">
                            <td>
                                {{ chain.chain_id }}
                            </td>
                            <td>
                                <TextInput
                                    v-model="form.chains[index].name"
                                    @keydown.enter="updateChains()"
                                />
                            </td>
                            <td>
                                <TextInput
                                    v-model="form.chains[index].rpc_endpoint"
                                    @keydown.enter="updateChains()"
                                />
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div class="mt-5">
                    <PrimaryButton @click="updateChains()">
                        Save
                    </PrimaryButton>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
