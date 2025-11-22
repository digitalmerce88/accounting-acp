<template>
  <Modal :show="show" maxWidth="md" @close="onCancel">
    <div class="p-4">
      <h3 class="text-lg font-semibold mb-2">{{ title }}</h3>
      <p class="text-sm text-gray-600 mb-3">คุณสามารถใส่หมายเหตุประกอบการดำเนินการได้ (ไม่บังคับ)</p>
      <textarea v-model="localComment" rows="4" class="w-full border rounded p-2 text-sm" :placeholder="placeholder"></textarea>
      <div class="flex justify-end gap-2 mt-4">
        <SecondaryButton type="button" @click="onCancel">ยกเลิก</SecondaryButton>
        <PrimaryButton type="button" :disabled="submitting" @click="emitSubmit">
          <span v-if="!submitting">ยืนยัน</span>
          <span v-else>กำลังดำเนินการ...</span>
        </PrimaryButton>
      </div>
    </div>
  </Modal>
</template>
<script setup>
import Modal from './Modal.vue'
import PrimaryButton from './PrimaryButton.vue'
import SecondaryButton from './SecondaryButton.vue'
import { watch, ref } from 'vue'

const props = defineProps({
  show: { type: Boolean, default: false },
  title: { type: String, default: 'ยืนยันการทำรายการ' },
  placeholder: { type: String, default: 'ใส่หมายเหตุ (ถ้ามี)' },
  submitting: { type: Boolean, default: false },
  modelValue: { type: String, default: '' },
})
const emit = defineEmits(['update:modelValue','submit','cancel'])
const localComment = ref(props.modelValue)
watch(() => props.modelValue, v => { localComment.value = v })
watch(localComment, v => emit('update:modelValue', v))
function onCancel(){ emit('cancel') }
function emitSubmit(){ emit('submit', localComment.value) }
</script>
