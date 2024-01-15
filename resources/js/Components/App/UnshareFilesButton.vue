
<template>
    <button @click="onClick"
            class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white mr-3">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-2">
                <path stroke-linecap="round" stroke-linejoin="round" d="m15 15-6 6m0 0-6-6m6 6V9a6 6 0 0 1 12 0v3" />
            </svg>

        Unshare
    </button>

    <ConfirmationDialog :show="showUnshareDialog"
                        message="Are you sure you want to unshare selected files?"
                        @cancel="onUnshareCancel"
                        @confirm="onUnshareConfirm">
    </ConfirmationDialog></template>


<script setup>
import { ref } from 'vue';
import { showErrorNotification, showSuccessNotification } from '@/event-bus';
import ConfirmationDialog from '../ConfirmationDialog.vue';
import { useForm } from '@inertiajs/vue3';


const props = defineProps({
    all: {
        type: Boolean,
        required: false,
        default: false
    },
    ids: {
        type: Array,
        required: false
    }
})

const showUnshareDialog = ref(false)
const form = useForm({
    all: null,
    ids: [],
})

const emit = defineEmits(['unshare'])

function onClick() {
    if(!props.all && props.ids.length == 0) {
        showErrorNotification('Please select at least one file or folder to unshare')
    }else {
        showUnshareDialog.value = true
    }
}

function onUnshareCancel() {
    showUnshareDialog.value = false
}

function onUnshareConfirm() {
    form.all = props.all
    form.ids = props.ids
    form.post(route('file.unshare'), {
        onSuccess: () => {
            showUnshareDialog.value = false
            emit('unshare')
            showSuccessNotification('Selected files have been unshared')
        },
        onFinish: () => {
            form.all = null
            form.ids = []
        }
    })
}
</script>

<style scoped>

</style>
