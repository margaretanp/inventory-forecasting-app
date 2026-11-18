<script setup>
import { ref } from 'vue'
import { UploadCloud, X, FileText, CheckCircle2, AlertCircle } from 'lucide-vue-next'
import inventoryApi from '../services/inventoryApi'

const props = defineProps({
  isOpen: Boolean
})

const emit = defineEmits(['close', 'imported'])

const isDragging = ref(false)
const selectedFile = ref(null)
const replaceExisting = ref(true)
const isLoading = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

const handleFileSelect = (event) => {
  const file = event.target.files[0]
  if (file) {
    selectedFile.value = file
    errorMessage.value = ''
  }
}

const handleDrop = (event) => {
  isDragging.value = false
  const file = event.dataTransfer.files[0]
  if (file && (file.name.endsWith('.csv') || file.type.includes('csv') || file.name.endsWith('.txt'))) {
    selectedFile.value = file
    errorMessage.value = ''
  } else {
    errorMessage.value = 'Hanya file CSV atau TXT yang didukung.'
  }
}

const submitImport = async () => {
  if (!selectedFile.value) {
    errorMessage.value = 'Silakan pilih file CSV terlebih dahulu.'
    return
  }

  isLoading.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    const res = await inventoryApi.importCsv(selectedFile.value, replaceExisting.value)
    successMessage.value = res.data.message || 'Data inventaris berhasil diimpor!'
    setTimeout(() => {
      emit('imported')
      emit('close')
      selectedFile.value = null
      successMessage.value = ''
    }, 1500)
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Gagal mengimpor file. Pastikan format CSV sesuai.'
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm transition-opacity">
    <div class="bg-white rounded-[2rem] max-w-lg w-full p-7 shadow-2xl border border-pink-100 transform transition-all animate-in fade-in zoom-in-95 duration-200">
      
      <!-- Modal Header -->
      <div class="flex items-center justify-between pb-4 border-b border-pink-50 mb-6">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-pink-50 border border-pink-100 flex items-center justify-center text-pink-500">
            <UploadCloud class="w-5 h-5" />
          </div>
          <div>
            <h3 class="font-bold text-lg text-gray-900">Import Inventory Data</h3>
            <p class="text-xs text-gray-400 font-medium">Unggah berkas CSV untuk memuat data inventaris</p>
          </div>
        </div>
        <button @click="$emit('close')" class="w-8 h-8 rounded-full flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors">
          <X class="w-4 h-4" />
        </button>
      </div>

      <!-- Drag & Drop Zone -->
      <div
        @dragover.prevent="isDragging = true"
        @dragleave.prevent="isDragging = false"
        @drop.prevent="handleDrop"
        :class="[
          'border-2 border-dashed rounded-2xl p-6 text-center transition-all cursor-pointer mb-5',
          isDragging ? 'border-pink-500 bg-pink-50/60' : 'border-pink-200 hover:border-pink-300 bg-[#FCF8FA]'
        ]"
        @click="$refs.fileInput.click()"
      >
        <input
          ref="fileInput"
          type="file"
          accept=".csv,.txt"
          class="hidden"
          @change="handleFileSelect"
        />

        <div v-if="!selectedFile" class="flex flex-col items-center">
          <div class="w-12 h-12 rounded-full bg-pink-100/80 flex items-center justify-center text-pink-500 mb-3">
            <UploadCloud class="w-6 h-6" />
          </div>
          <p class="text-sm font-semibold text-gray-800 mb-1">Klik untuk pilih file atau tarik ke sini</p>
          <p class="text-xs text-gray-400">Format yang didukung: .csv, .txt (Maks 5MB)</p>
        </div>

        <div v-else class="flex items-center justify-between bg-white p-3 rounded-xl border border-pink-100 shadow-sm">
          <div class="flex items-center gap-3">
            <FileText class="w-6 h-6 text-pink-500" />
            <div class="text-left">
              <p class="text-xs font-bold text-gray-900 truncate max-w-[220px]">{{ selectedFile.name }}</p>
              <p class="text-[10px] text-gray-400">{{ (selectedFile.size / 1024).toFixed(1) }} KB</p>
            </div>
          </div>
          <button @click.stop="selectedFile = null" class="text-gray-400 hover:text-rose-500 text-xs font-medium px-2 py-1">Ganti</button>
        </div>
      </div>

      <!-- Options -->
      <div class="bg-gray-50 rounded-xl p-3.5 mb-5 flex items-center justify-between text-xs">
        <div>
          <span class="font-semibold text-gray-700">Timpa data lama</span>
          <p class="text-[10px] text-gray-400">Hapus data stok sebelumnya dan ganti dengan data file baru</p>
        </div>
        <input
          type="checkbox"
          v-model="replaceExisting"
          class="w-4 h-4 rounded text-pink-600 focus:ring-pink-500 border-gray-300"
        />
      </div>

      <!-- Feedback Alerts -->
      <div v-if="errorMessage" class="flex items-center gap-2 p-3 rounded-xl bg-rose-50 border border-rose-100 text-rose-700 text-xs mb-4">
        <AlertCircle class="w-4 h-4 shrink-0" />
        <span>{{ errorMessage }}</span>
      </div>

      <div v-if="successMessage" class="flex items-center gap-2 p-3 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-700 text-xs mb-4">
        <CheckCircle2 class="w-4 h-4 shrink-0" />
        <span>{{ successMessage }}</span>
      </div>

      <!-- Actions -->
      <div class="flex items-center justify-end gap-3 pt-2">
        <button
          type="button"
          @click="$emit('close')"
          class="px-5 py-2.5 rounded-full text-xs font-semibold text-gray-500 hover:bg-gray-100 transition-colors"
        >
          Batal
        </button>
        <button
          type="button"
          @click="submitImport"
          :disabled="!selectedFile || isLoading"
          class="flex items-center gap-2 bg-gradient-to-r from-pink-500 to-rose-400 hover:from-pink-600 hover:to-rose-500 disabled:opacity-50 text-white px-6 py-2.5 rounded-full font-semibold text-xs shadow-md shadow-pink-200 transition-all active:scale-95"
        >
          <UploadCloud class="w-4 h-4" />
          {{ isLoading ? 'Mengunggah...' : 'Unggah & Proses' }}
        </button>
      </div>

    </div>
  </div>
</template>

