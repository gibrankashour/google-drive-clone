<template>
    <modal :show="modelValue"  max-width="sm" @close="closeModal">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900">
                Share files and folders
            </h2>
            <div class="mt-6">
                <InputLabel for="userEmail" value="Enter email addresses" class="sr-only"/>

                <TextInput type="email"
                           ref="userEmail"
                           id="userEmail" v-model="form.email"
                           class="mt-1 block w-full"
                           :class="form.errors.email ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : ''"
                            placeholder="Enter email addresses"
                           @keyup.enter="share"
                />
                <InputError :message="form.errors.email" class="mt-2"/>
            </div>
            <div class="mt-6 flex justify-end">
                <SecondaryButton @click="closeModal">Cancel</SecondaryButton>
                <PrimaryButton class="ml-3"
                               :class="{ 'opacity-25': form.processing }"
                               @click="share" :disable="form.processing">
                    Submit
                </PrimaryButton>
            </div>
        </div>
    </modal>
</template>

<script setup>
import modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import InputError from '../InputError.vue';
import { showSuccessNotification } from '@/event-bus';

const page = usePage()
const props = defineProps({
    modelValue:  {
        type: Boolean,
        required: true,
        default: false
    },
    allSelected: {
        type: Boolean,
        required: false,
        default: false
    },
    selectedIds: {
        type: Array,
        required: false
    }
})

const form = useForm({
    parent_id: null,
    all: false,
    ids: [],
    email:''
})

const emit = defineEmits(['closeModel'])

function closeModal() {
    emit('closeModel')
    form.clearErrors()
    form.reset()
}

function share() {
    form.all = props.allSelected
    form.ids = props.selectedIds
    form.parent_id = page.props.folder.id
    let email = form.email
    if(form.email !=='') {
        form.post(route('file.share'), {
            onSuccess: () => {
                showSuccessNotification(`Selected files will be shared to "${email}" if the email exists in the system`);
                closeModal()
            },
            onError: (e) => {
                // console.log(e)
                showSuccessNotification(`Selected files will be shared to "${email}" if the email exists in the system`);
                closeModal()
            },

        })
    }else {
        form.errors.email = 'type user email'
    }

}
</script>

<style scoped>

</style>
