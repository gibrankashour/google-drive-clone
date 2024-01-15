<template>
    <Modal :show="show" max-width="md">
        <div class="p-6">
            <h2 class="text-2xl mb-2 text-red-600 font-semibold">Error</h2>
            <p>{{errorMessage}}</p>
            <div class="mt-6 flex justify-end">
                <PrimaryButton @click="close">OK</PrimaryButton>
            </div>
        </div>
    </Modal>
</template>

<script setup>

import PrimaryButton from '../PrimaryButton.vue';
import Modal from '@/Components/Modal.vue';
import { onMounted, ref } from 'vue';
import { SHOW_ERROR_DIALOG, emitter } from '@/event-bus';

const show = ref(false)
const errorMessage = ref('')

function close() {
    show.value = false
    errorMessage.value = ''
}

onMounted( () => {
    emitter.on(SHOW_ERROR_DIALOG, (message) => {
        errorMessage.value = message.message
        show.value = true
    })
})
</script>

<style scoped>

</style>
