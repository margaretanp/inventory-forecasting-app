<script setup>
import { ref } from 'vue'
import { ArrowRightLeft, ArrowDownRight, ArrowUpRight, CheckCircle2, AlertCircle, Plus } from 'lucide-vue-next'
import inventoryApi from '../services/inventoryApi'

const props = defineProps({
  products: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['movement-recorded'])

const selectedProductId = ref(props.products[0]?.id || '')
const movementType = ref('in')
const quantity = ref('')
const transactionDate = ref(new Date().toISOString().split('T')[0])
const isSubmitting = ref(false)
const successMsg = ref('')
const errorMsg = ref('')

const recentMovements = ref([
  { id: 1, product_name: 'Espresso House Blend Beans', type: 'in', quantity: 30000, date: '2026-11-18 11:36:47', unit: 'gram' },
  { id: 2, product_name: 'Velvet Barista Oat Milk', type: 'in', quantity: 36, date: '2026-11-18 10:15:20', unit: 'cartons' },
  { id: 3, product_name: 'Blush Roast Espresso Beans', type: 'out', quantity: 12, date: '2026-11-18 09:30:00', unit: 'bags' },
])

const submitMovement = async () => {
  if (!selectedProductId.value || !quantity.value || !transactionDate.value) {
    errorMsg.value = 'Harap isi semua kolom transaksi.'
    return
  }

  isSubmitting.value = true
  errorMsg.value = ''
  successMsg.value = ''

  try {
    const payload = {
      product_id: selectedProductId.value,
      type: movementType.value,
      quantity: parseFloat(quantity.value),
      transaction_date: transactionDate.value,
    }

    const res = await inventoryApi.recordMovement(payload)
    const product = props.products.find(p => p.id === selectedProductId.value)
    
    // Add to local recent movements
    recentMovements.value.unshift({
      id: Date.now(),
      product_name: product?.name || 'Item',
      type: movementType.value,
      quantity: parseFloat(quantity.value),
      date: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }),
      unit: product?.unit || 'unit'
    })

    successMsg.value = res.data.message || 'Transaksi berhasil dicatat dan stok telah diperbarui!'
    quantity.value = ''
    emit('movement-recorded')

    setTimeout(() => {
      successMsg.value = ''
    }, 4000)
  } catch (err) {
    errorMsg.value = err.response?.data?.message || 'Gagal mencatat transaksi stok.'
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Form Card -->
    <div class="bg-white p-7 rounded-[2rem] border border-pink-100/50 shadow-sm space-y-5 lg:col-span-1">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-2xl bg-pink-50 border border-pink-100 flex items-center justify-center text-pink-500">
          <ArrowRightLeft class="w-5 h-5" />
        </div>
        <div>
          <h3 class="font-bold text-lg text-gray-900">Catat Mutasi Stok</h3>
          <p class="text-xs text-gray-400">Pemasukan (In) atau Pengeluaran (Out)</p>
        </div>
      </div>

      <form @submit.prevent="submitMovement" class="space-y-4 pt-2">
        <!-- Type Selection -->
        <div>
          <label class="block text-xs font-bold text-gray-600 mb-1.5">Tipe Pergerakan</label>
          <div class="grid grid-cols-2 gap-2 p-1 bg-[#FCF8FA] border border-pink-100 rounded-2xl">
            <button
              type="button"
              @click="movementType = 'in'"
              :class="[
                'flex items-center justify-center gap-2 py-2 rounded-xl text-xs font-bold transition-all',
                movementType === 'in' ? 'bg-emerald-500 text-white shadow-sm' : 'text-gray-500 hover:text-gray-800'
              ]"
            >
              <ArrowDownRight class="w-4 h-4" />
              Barang Masuk (In)
            </button>
            <button
              type="button"
              @click="movementType = 'out'"
              :class="[
                'flex items-center justify-center gap-2 py-2 rounded-xl text-xs font-bold transition-all',
                movementType === 'out' ? 'bg-rose-500 text-white shadow-sm' : 'text-gray-500 hover:text-gray-800'
              ]"
            >
              <ArrowUpRight class="w-4 h-4" />
              Barang Keluar (Out)
            </button>
          </div>
        </div>

        <!-- Product Selector -->
        <div>
          <label class="block text-xs font-bold text-gray-600 mb-1.5">Pilih Produk</label>
          <select
            v-model="selectedProductId"
            class="w-full px-3.5 py-2.5 bg-[#FCF8FA] border border-pink-100 rounded-xl text-xs text-gray-800 font-medium focus:outline-none focus:border-pink-300"
          >
            <option v-for="p in products" :key="p.id" :value="p.id">
              {{ p.sku }} - {{ p.name }} (Stok: {{ p.current_stock }} {{ p.unit }})
            </option>
          </select>
        </div>

        <!-- Quantity -->
        <div>
          <label class="block text-xs font-bold text-gray-600 mb-1.5">Jumlah Kuantitas</label>
          <input
            v-model="quantity"
            type="number"
            step="0.01"
            min="0.01"
            placeholder="Misal: 50"
            class="w-full px-3.5 py-2.5 bg-[#FCF8FA] border border-pink-100 rounded-xl text-xs text-gray-800 font-medium focus:outline-none focus:border-pink-300"
          />
        </div>

        <!-- Date -->
        <div>
          <label class="block text-xs font-bold text-gray-600 mb-1.5">Tanggal Transaksi</label>
          <input
            v-model="transactionDate"
            type="date"
            class="w-full px-3.5 py-2.5 bg-[#FCF8FA] border border-pink-100 rounded-xl text-xs text-gray-800 font-medium focus:outline-none focus:border-pink-300"
          />
        </div>

        <!-- Feedback -->
        <div v-if="errorMsg" class="flex items-center gap-2 p-3 rounded-xl bg-rose-50 border border-rose-100 text-rose-700 text-xs">
          <AlertCircle class="w-4 h-4 shrink-0" />
          <span>{{ errorMsg }}</span>
        </div>

        <div v-if="successMsg" class="flex items-center gap-2 p-3 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-700 text-xs">
          <CheckCircle2 class="w-4 h-4 shrink-0" />
          <span>{{ successMsg }}</span>
        </div>

        <!-- Submit Button -->
        <button
          type="submit"
          :disabled="isSubmitting"
          class="w-full flex items-center justify-center gap-2 bg-gradient-to-r from-pink-500 to-rose-400 hover:from-pink-600 hover:to-rose-500 disabled:opacity-50 text-white py-3 rounded-xl font-bold text-xs shadow-md shadow-pink-200 transition-all active:scale-95"
        >
          <Plus class="w-4 h-4" />
          {{ isSubmitting ? 'Menyimpan...' : 'Simpan Transaksi' }}
        </button>
      </form>
    </div>

    <!-- History Card -->
    <div class="bg-white p-7 rounded-[2rem] border border-pink-100/50 shadow-sm lg:col-span-2 space-y-4">
      <div class="flex items-center justify-between">
        <div>
          <h3 class="font-bold text-lg text-gray-900">Aktivitas Terakhir</h3>
          <p class="text-xs text-gray-400">Riwayat transaksi pergerakan stok inventaris</p>
        </div>
      </div>

      <div class="divide-y divide-pink-50/60 text-xs">
        <div
          v-for="item in recentMovements"
          :key="item.id"
          class="py-3.5 flex items-center justify-between hover:bg-pink-50/20 px-2 rounded-xl transition-colors"
        >
          <div class="flex items-center gap-3">
            <div
              :class="[
                'w-8 h-8 rounded-xl flex items-center justify-center border font-bold',
                item.type === 'in' ? 'bg-emerald-50 border-emerald-100 text-emerald-600' : 'bg-rose-50 border-rose-100 text-rose-600'
              ]"
            >
              <ArrowDownRight v-if="item.type === 'in'" class="w-4 h-4" />
              <ArrowUpRight v-else class="w-4 h-4" />
            </div>
            <div>
              <p class="font-bold text-gray-800">{{ item.product_name }}</p>
              <p class="text-[10px] text-gray-400">{{ item.date }}</p>
            </div>
          </div>
          <div class="text-right">
            <span
              :class="[
                'font-extrabold text-sm',
                item.type === 'in' ? 'text-emerald-600' : 'text-rose-600'
              ]"
            >
              {{ item.type === 'in' ? '+' : '-' }}{{ item.quantity }}
            </span>
            <span class="text-[10px] text-gray-400 ml-1">{{ item.unit }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

