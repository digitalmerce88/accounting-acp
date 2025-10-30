<script setup>
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    status: {
        type: String,
    },
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);
</script>

<template>
    <GuestLayout>
        <Head title="ยืนยันอีเมล" />

        <div class="mb-4 text-sm text-gray-600">
            ขอบคุณที่สมัครสมาชิก กรุณายืนยันอีเมลโดยคลิกลิงก์ที่ส่งให้ หากไม่ได้รับอีเมล เราจะส่งให้ใหม่
        </div>

        <div
            class="mb-4 text-sm font-medium text-green-600"
            v-if="verificationLinkSent"
        >
            ระบบได้ส่งลิงก์ยืนยันอีเมลไปให้คุณเรียบร้อยแล้ว
        </div>

        <form @submit.prevent="submit">
            <div class="mt-4 flex items-center justify-between">
                <PrimaryButton
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    ส่งลิงก์ยืนยันอีกครั้ง
                </PrimaryButton>

                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >ออกจากระบบ</Link
                >
            </div>
        </form>
    </GuestLayout>
</template>
