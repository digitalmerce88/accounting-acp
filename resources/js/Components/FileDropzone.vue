<template>
  <div>
    <label class="block text-sm text-gray-600 mb-1">แนบไฟล์ (ลาก/วาง หรือ คลิกเพื่อเลือก)</label>
    <div
      class="border-2 border-dashed border-gray-300 rounded p-4 text-sm text-gray-600 bg-gray-50"
      :class="{'bg-white': isDragOver}
      "
      @dragover.prevent="onDragOver"
      @dragenter.prevent="onDragOver"
      @dragleave.prevent="onDragLeave"
      @drop.prevent="onDrop"
      @click="openPicker"
      role="button"
    >
      <div class="flex items-center gap-3">
        <div class="flex-1">ลากไฟล์มาวางที่นี้ หรือ คลิกเพื่อเลือกไฟล์ (สามารถเลือกหลายไฟล์)</div>
        <div>
          <button type="button" class="px-3 py-1 bg-gray-200 rounded text-xs">Choose files</button>
        </div>
      </div>
      <input ref="picker" type="file" multiple class="hidden" @change="onPick">
    </div>

    <ul class="mt-3 list-disc pl-5 text-sm space-y-1">
      <li v-for="(f,idx) in filesLocal" :key="f.__local_id">
        <div class="flex items-center gap-3">
          <div class="flex items-center gap-2">
            <img v-if="f.preview && isImage(f)" :src="f.preview" class="w-10 h-10 object-cover rounded" />
            <div>
              <div class="text-sm font-medium">{{ f.name }}</div>
              <div class="text-xs text-gray-500">{{ humanSize(f.size) }}</div>
            </div>
          </div>
          <div class="ml-auto">
            <button type="button" class="text-xs text-red-600" @click.prevent="remove(idx)">ลบ</button>
          </div>
        </div>
      </li>
      <li v-if="!filesLocal || filesLocal.length===0" class="text-gray-500">-</li>
    </ul>
  </div>
</template>

<script setup>
import { ref, watch, onBeforeUnmount } from 'vue'

const props = defineProps({
  modelValue: { type: Array, default: () => [] },
})
const emit = defineEmits(['update:modelValue'])

const picker = ref(null)
const isDragOver = ref(false)

// Internal file objects: for File instances we add preview and __local_id
const filesLocal = ref((props.modelValue || []).map((f, i) => attachMeta(f, i)))
let nextId = filesLocal.value.length

watch(() => props.modelValue, (v) => {
  // when parent sets modelValue (e.g. from server), ensure meta present
  filesLocal.value = (v || []).map((f, i) => attachMeta(f, i))
  nextId = filesLocal.value.length
}, { deep: true })

function attachMeta(f, idx){
  // if it's a File (browser), create preview
  const out = Object.assign({}, f)
  out.__local_id = out.__local_id ?? ('f' + (nextId++))
  if (f instanceof File) {
    try { out.preview = URL.createObjectURL(f) } catch(e) { out.preview = null }
    out.name = f.name
    out.size = f.size
  } else {
    // existing JSON metadata from server: {path,name,size,mime}
    out.preview = null
    out.name = out.name || out.path || 'file'
    out.size = out.size || 0
  }
  return out
}

function onDragOver(e){ isDragOver.value = true }
function onDragLeave(e){ isDragOver.value = false }
function onDrop(e){
  isDragOver.value = false
  const dtFiles = Array.from(e.dataTransfer?.files || [])
  if (dtFiles.length) addFiles(dtFiles)
}

function openPicker(){ picker.value && picker.value.click() }
function onPick(e){ const f = Array.from(e.target.files || []); addFiles(f); e.target.value = null }

function addFiles(list){
  const added = list.map((f,i)=> attachMeta(f, filesLocal.value.length + i))
  filesLocal.value = filesLocal.value.concat(added)
  // emit raw File objects for those that are File instances, and existing metadata objects unchanged
  emitModel()
}

function remove(idx){
  const removed = filesLocal.value.splice(idx,1)[0]
  // revoke preview for File
  if (removed && removed.preview && removed.preview.startsWith('blob:')) {
    try { URL.revokeObjectURL(removed.preview) } catch(e){}
  }
  emitModel()
}

function emitModel(){
  // normalize to either File instances (for newly added) or metadata objects
  const out = filesLocal.value.map(f => {
    // if original is File, it will have a File instance under .raw? but we stored File itself
    // If f is a File, return it directly (it will be appended to FormData by parent)
    if (f instanceof File) return f
    // if it has a __file prop referencing File (unlikely), return that
    if (f.raw instanceof File) return f.raw
    // otherwise it's metadata (from server) - keep as-is
    return {
      path: f.path,
      name: f.name,
      size: f.size,
      mime: f.mime,
    }
  })
  emit('update:modelValue', out)
}

function humanSize(bytes){ if(!bytes) return '0 B'; const units=['B','KB','MB','GB']; let i=0; let v=bytes; while(v>=1024 && i<units.length-1){ v/=1024; i++ } return v.toFixed(v<10 && i>0?2:1)+ ' ' + units[i] }

function isImage(f){ const m = f.mime || (f.type||''); return m.startsWith('image/') }

onBeforeUnmount(()=>{
  filesLocal.value.forEach(f => { if (f.preview && f.preview.startsWith('blob:')) { try { URL.revokeObjectURL(f.preview) } catch(e){} } })
})
</script>

<style scoped>
/* minimal styles; Tailwind used in templates */
</style>
