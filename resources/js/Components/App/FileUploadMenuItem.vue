<template>
    <MenuItem v-slot="{ active }">
        <a href="#" class="text-gray-700 block px-4 py-2 text-sm relative">
            Upload Files
            <input @change="onchange" type="file" class="absolute left-0 top-0 bottom-0 right-0 cursor-pointer opacity-0" multiple>
        </a>
    </MenuItem>
</template>

<script setup>
import { MenuItem } from '@headlessui/vue';
import { emitter, FILE_UPLOAD_STARTED } from '@/event-bus';

function onchange(ev) {
    // ev.target.files تمثل الملفات التي تم اختيارها بواسظة حقل الإدخال
    emitter.emit(FILE_UPLOAD_STARTED, ev.target.files)
}

</script>

<style scoped>

</style>
