<script setup>
import { ref } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, useForm, usePage, Link } from "@inertiajs/vue3";

const accessControls = ref(usePage().props.accessControls.valueOf());

const form = useForm({
    eth_addr: null,
    role: "admin",
});

const addAccessControl = () => {
    form.post(route("access-control.upsert", {}), {
        onSuccess: (response) => {
            accessControls.value = response.props.accessControls;
        },
        onError: (error) => {},
    });

    form.eth_addr = "";
    form.role = "admin";
};

const deleteAccessControl = async (accessControl) => {
    if (!confirm("Are you sure you want to delete this access control?")) {
        return;
    }

    await useForm({ uuid: accessControl.uuid }).delete(
        route("access-control.delete", { accessControl: accessControl.uuid }),
        {
            onSuccess: (response) => {
                accessControls.value = response.props.accessControls;
            },
            onError: (error) => {},
        }
    );
};
</script>

<template>
    <AppLayout title="Profile">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Users
            </h2>
        </template>

        <div>
            <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
                <div>
                    Add Ethereum addresses to the access control list to grant
                    them access.
                </div>

                <table v-if="accessControls && accessControls.length > 0">
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
                            v-for="(accessControl, index) in accessControls"
                            :key="index"
                        >
                            <td>
                                {{
                                    accessControl.user
                                        ? accessControl.user.name
                                        : ""
                                }}
                            </td>
                            <td>
                                {{
                                    accessControl.user
                                        ? accessControl.user.email
                                        : ""
                                }}
                            </td>
                            <td>{{ accessControl.eth_addr }}</td>
                            <td>{{ accessControl.role }}</td>
                            <td>
                                <button
                                    @click="deleteAccessControl(accessControl)"
                                >
                                    Delete
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <form @submit.prevent="addAccessControl">
                    <TextInput
                        v-model="form.eth_addr"
                        placeholder="Ethereum Address"
                    />
                    <select v-model="form.role">
                        <option value="admin">Admin</option>
                    </select>
                    <PrimaryButton type="submit">Add</PrimaryButton>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
