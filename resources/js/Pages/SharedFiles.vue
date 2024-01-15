<template>
    <AuthenticatedLayout>
        <Head title="My Files" />

        <nav class="flex items-center justify-end p-1 mb-3">
            <div>
                <UnshareFilesButton v-if="$page.props.route_name === 'file.sharedByMe'" :all="allSelected" :ids="selectedIds" class="mr-2" @unshare="resetForm" />
                <DownloadFilesButton :all="allSelected" :ids="selectedIds" :sourcePage="sourcePage" class="mr-2"/>
            </div>
        </nav>
        <div class="flex-1 overflow-auto">
            <table class="min-w-full">
                <thead class="bg-gray-100 border-b">
                    <tr>
                        <th class="text-sm font-medium text-gray-900 px-6 py-4 text-left w-[30px] max-w-[30px] pr-0">
                            <Checkbox @change="onSelectAllChange" v-model:checked="allSelected"/>
                        </th>
                        <th class="text-sm font-medium text-gray-900 px-6 py-4 text-left">
                            Name
                        </th>
                        <th class="text-sm font-medium text-gray-900 px-6 py-4 text-left">
                            Path
                        </th>
                        <th class="text-sm font-medium text-gray-900 px-6 py-4 text-left">
                            <span v-if="$page.props.route_name === 'file.sharedWithMe'" >
                                Created By
                            </span>
                            <span v-else-if="$page.props.route_name === 'file.sharedByMe'" >
                                Shared With
                            </span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="file of allFiles.data" :key="file.id"
                        @click="$event => toggleFileSelect(file, $event)"

                        class="border-b transition duration-300 ease-in-out hover:bg-blue-100 cursor-pointer"
                        :class="(selected[file.id] || allSelected ) ? 'bg-blue-50' : 'bg-white'">

                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 w-[30px] max-w-[30px] pr-0">
                            <Checkbox @change="$event => onSelectCheckboxChange(file)" v-model="selected[file.id]" :checked="selected[file.id] || allSelected"/>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 flex items-center">
                            <FileIcon :file="file" />
                            {{ file.name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ file.path }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ file.email }}
                        </td>
                    </tr>
                </tbody>
            </table>

            <div v-if="!allFiles.data.length" class="py-8 text-center text-sm text-gray-400">
                There is no data in this folder
            </div>

            <div ref="loadMoreIntersect"></div>
        </div>

    </AuthenticatedLayout>

</template>

<script setup>

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import FileIcon from '@/Components/App/FileIcon.vue';
import { computed, ref } from 'vue';
import { onMounted } from 'vue';
import { onUpdated } from 'vue';
import {httpGet} from "@/Helper/http-helper.js";
import Checkbox from '@/Components/Checkbox.vue';
import DownloadFilesButton from '@/Components/App/DownloadFilesButton.vue';
import UnshareFilesButton from '@/Components/App/UnshareFilesButton.vue';



const props = defineProps({
    files: Object,
    sourcePage: String,
})
const allSelected = ref(false)
const selected = ref({})
const lastSelected = ref(false);

const allFiles = ref({
    data: props.files.data,
    next: props.files.links.next
})

const loadMoreIntersect = ref(null)
// Computed
const selectedIds = computed(() => Object.entries(selected.value).filter(a => a[1]).map(a => a[0]))

function onSelectAllChange() {
    allFiles.value.data.forEach(f => {
        selected.value[f.id] = allSelected.value
    })
}

function toggleFileSelect(file, e) {
    selected.value[file.id] = !selected.value[file.id]
    onSelectCheckboxChange(file)


    if(e.shiftKey && lastSelected.value) {
        let sortedFiles = []
        let startAdd = false
        allFiles.value.data.forEach( (f) => {
            if(f.id == lastSelected.value || f.id == file.id) {
                startAdd = !startAdd
                if(!startAdd) {
                    sortedFiles.push(f)
                }
            }
            if(startAdd) {
                sortedFiles.push(f)
            }

        })
        sortedFiles.forEach((f) => {
            selected.value[f.id] = true
        })
    }else {
        lastSelected.value = selected.value[file.id] ? file.id : false
    }
}

function onSelectCheckboxChange(file) {
    if(!selected.value[file.id]) {
        allSelected.value = false;
    }else {
        let checked = true
        for(let file of allFiles.value.data) {
            if(!selected.value[file.id]) {
                checked = false
                break
            }
        }
        allSelected.value = checked
    }
}


function loadMore() {
    console.log("load more");
    console.log(allFiles.value.next);

    if (allFiles.value.next === null) {
        return
    }

    httpGet(allFiles.value.next)
        .then(res => {
            allFiles.value.data = [...allFiles.value.data, ...res.data]
            allFiles.value.next = res.links.next
        })
}

function resetForm(){
    allSelected.value = false
    selected.value = {}
}

onUpdated(() => {
    allFiles.value = {
        data: props.files.data,
        next: props.files.links.next
    }
})

onMounted(() => {
    const observe = new IntersectionObserver( (entries) => entries.forEach(entry => entry.isIntersecting && loadMore()), {
        rootMargin : '-250px 0px 0px 0px'
    })

    observe.observe(loadMoreIntersect.value)

})
</script>

<style scoped>

</style>
