<template>
    <div>
        <nav class="min-w-[200px] ">
            <Link :href="route('myFiles')" class="h-[80px] px-3 flex items-center gap-3">
                <ApplicationLogo
                    class="block h-9 w-auto fill-current text-gray-800"
                />
                LaraStorage
            </Link>

            <div class="px-3">
                <CreateNewDropdown />
                <div class="py-3">
                    <Navlink :href="route('myFiles')" :active="$page.props.route_name === 'myFiles'">My Files</Navlink>
                    <Navlink :href="route('file.sharedWithMe')" :active="$page.props.route_name === 'file.sharedWithMe'">Shared with me</Navlink>
                    <Navlink :href="route('file.sharedByMe')" :active="$page.props.route_name === 'file.sharedByMe'">Shared by me</Navlink>
                    <Navlink :href="route('trash')" :active="$page.props.route_name === 'trash'">Trash</Navlink>
                </div>
            </div>
        </nav>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import CreateNewDropdown from '@/Components/App/CreateNewDropdown.vue';
import Navlink from '@/Components/NavLink.vue';


</script>

<style scoped>

</style>
