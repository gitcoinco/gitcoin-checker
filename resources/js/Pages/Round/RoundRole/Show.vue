<script setup>
import { ref, watch } from "vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { useForm, usePage, Link, router } from "@inertiajs/vue3";
import Pagination from "@/Components/Pagination.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";

const form = useForm({
    address: null,
    role: "reviewer",
});

import { shortenAddress, copyToClipboard } from "@/utils.js";

const round = ref(usePage().props.round.valueOf());
const roundRoles = ref(usePage().props.roundRoles.valueOf());

const addRoundRole = () => {
    form.post(
        route("round.role.upsert", {
            round: round.value.uuid,
        }),
        {
            onSuccess: (response) => {
                roundRoles.value = response.props.roundRoles;
            },
            onError: (error) => {},
        }
    );

    form.address = "";
    form.role = "reviewer";
};

const deleteRoundRole = async (roundRole) => {
    if (!confirm("Are you sure you want to delete this user?")) {
        return;
    }

    await useForm({ uuid: roundRole.uuid }).delete(
        route("round.role.delete", { roundRole: roundRole.uuid }),
        {
            onSuccess: (response) => {
                roundRoles.value = response.props.roundRoles;
            },
            onError: (error) => {},
        }
    );
};
</script>

<template>
    <AuthenticatedLayout title="Profile">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2
                        class="font-semibold text-xl text-gray-800 leading-tight"
                    >
                        Users for {{ round.name }} on {{ round.chain.name }}
                        <span class="text-sm">
                            {{ shortenAddress(round.round_addr) }}

                            <span
                                @click="copyToClipboard(round.round_addr)"
                                class="cursor-pointer"
                            >
                                <i class="fa fa-clone" aria-hidden="true"></i>
                            </span>
                        </span>
                    </h2>
                </div>
                <Link
                    :href="route('round.show', round)"
                    class="text-blue-500 hover:underline"
                >
                    Round
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div
                class="bg-white overflow-hidden shadow-xl sm:rounded-lg mb-3 py-3"
            >
                <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div>
                        Add Ethereum addresses for reviewers of the
                        {{ round.name }} round.
                    </div>

                    <table v-if="roundRoles && roundRoles.length > 0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Ethereum Address</th>
                                <th>Role</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(roundRole, index) in roundRoles"
                                :key="index"
                            >
                                <td>
                                    {{
                                        roundRole.user
                                            ? roundRole.user.name
                                            : ""
                                    }}
                                </td>
                                <td>
                                    {{
                                        roundRole.user
                                            ? roundRole.user.email
                                            : ""
                                    }}
                                </td>
                                <td>{{ roundRole.address }}</td>
                                <td>{{ roundRole.role }}</td>
                                <td>
                                    <button
                                        v-if="roundRole.role == 'reviewer'"
                                        @click="deleteRoundRole(roundRole)"
                                    >
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <form @submit.prevent="addRoundRole">
                        <TextInput
                            v-model="form.address"
                            placeholder="Ethereum Address"
                        />
                        <select v-model="form.role">
                            <option value="reviewer">Reviewer</option>
                        </select>
                        <PrimaryButton type="submit">Add</PrimaryButton>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
