<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>
        </template>
        <div>
            <p>
                Lorem ipsum dolor sit amet consectetur, adipisicing elit. Modi, ducimus.sdf
            </p>
        </div>

    </AuthenticatedLayout>
</template>
