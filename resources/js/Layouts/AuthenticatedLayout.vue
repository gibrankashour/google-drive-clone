<template>
    <div class="h-screen bg-gray-50 flex w-full ap-4">
        <Navigation />

        <main
            @drop.prevent="handleDrop"
            @dragover.prevent="onDragOver"
            @dragleave.prevent="onDragLeave"
            class="flex flex-col flex-1 px-4 overflow-hidden"
            :class="dragOver ? 'dropzone' : ''">
            <template v-if="dragOver" class="text-gray-500 text-center py-8 text-sm">
                Drop files here to upload
            </template>
            <template v-else>
                <div class="flex items-center justify-between w-full">
                    <SearchForm/>
                    <UserSettingsDropdown/>
                </div>
                <div class="flex-1 flex flex-col overflow-auto">
                    <slot/>
                </div>
            </template>
        </main>
    </div>
    <ErrorDialog />
    <FormProgress :form="fileUploadForm"/>
    <Notification />
</template>

<script setup>

import ErrorDialog from '@/Components/App/ErrorDialog.vue';
import FormProgress from '@/Components/App/FormProgress.vue';
import Navigation from '@/Components/App/Navigation.vue';
import SearchForm from '@/Components/App/SearchForm.vue';
import UserSettingsDropdown from '@/Components/App/UserSettingsDropdown.vue';
import Notification from '@/Components/Notification.vue';
import { emitter, FILE_UPLOAD_STARTED, SHOW_ERROR_DIALOG, showSuccessNotification } from '@/event-bus';
import { useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import { onMounted } from 'vue';

const dragOver = ref(false)
const page = usePage()
const fileUploadForm = useForm({
    files: [],
    relative_paths: [],
    // تعتبر الظريقة الوحيدة لمعرفة في أي مسار موجود المجلد . مثلا
    // webkitRelativePath: "cats/next.php"
    // والملفات لا تعظي قيمة لهذه الحخاصية الا اذا رفعناها كمجلد
    parent_id: null
})

function onDragOver() {
    if(page.props.route_name !== 'myFiles') {
        return
    }
    dragOver.value = true
}

function onDragLeave() {
    dragOver.value = false
}

function handleDrop(ev) {
    if(page.props.route_name !== 'myFiles') {
        return
    }
    dragOver.value = false
    // الحصول على الملفات التي تم جلبها بالسحب ev.dataTransfer.files
    const files = ev.dataTransfer.files

    if(!files.length) {
        return
    }
    uploadedFiles(files)
}

onMounted( () => {
    emitter.on(FILE_UPLOAD_STARTED, uploadedFiles)
})

function uploadedFiles(files) {

    fileUploadForm.files = files
    fileUploadForm.parent_id = page.props.folder.id
    // console.log(files instanceof Array) false
    // files is not array so we cant use map() immediatily
    // convert files to array as following [...files]

    // اذا كنا نرفع ملفات فقظ وليس مجلد فإن قيمة
    // f.webkitRelativePath
    // ستكون فارغة وبالتالي قيمة
    // fileUploadForm.relative_paths
    // ستكون فارغة أيضا
    fileUploadForm.relative_paths = [...files].map(f => f.webkitRelativePath);

    fileUploadForm.post(route('file.store'),{
        onSuccess: () => {
            showSuccessNotification(`${files.length} files have been uploaded`)
        },
        onError: errors => {
            let message = ''
            if(Object.keys(errors).length > 0) {
                /*
                    The Object.keys() method returns an Array Iterator object with the keys of an object.
                    The Object.keys() method does not change the original object.
                    example :
                    const person = {
                        firstName: "John",
                        lastName: "Doe",
                        age: 50,
                        eyeColor: "blue"
                    };
                    console.log(Object.keys(person))
                    => ["firstName", "lastName", "age", "eyeColor"]
                */
                message = errors[Object.keys(errors)[0]]

            } else {
                message = 'Error during file upload. Please try again later.'
            }
            emitter.emit(SHOW_ERROR_DIALOG, {message})
        },
        onFinish: () => {
            fileUploadForm.clearErrors()
            fileUploadForm.reset()
        }
    })
}
</script>

<style scoped>
    .dropzone {
        width: 100%;
        height: 100%;
        color: #8d8d8d;
        border: 2px dashed gray;
        display: flex;
        justify-content: center;
        align-items: center;
    }
</style>
