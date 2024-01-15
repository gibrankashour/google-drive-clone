<template>
    <div class="w-[600px] h-[80px] flex items-center">
        <TextInput type="text"
                   class="block w-full mr-2"
                   v-model="search"
                   autocopmlete
                   @keyup.enter.prevent="searchRequest"
                   placeholder="Search for files and folders" />
    </div>
</template>

<script setup>
import TextInput from "@/Components/TextInput.vue";
import { router } from "@inertiajs/vue3";
import { onMounted } from "vue";
import { ref } from "vue";

const search = ref("")
const param = new URLSearchParams(window.location.search)

function searchRequest() {
    param.set("search", search.value)
    let url = window.location.origin + window.location.pathname + "?"+param.toString()
    router.visit(url)
}

onMounted(()=> {
    search.value = param.get("search")
})
</script>

<style scoped>

</style>
