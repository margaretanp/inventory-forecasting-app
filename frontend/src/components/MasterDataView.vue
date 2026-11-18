<script setup>
import { ref, computed } from 'vue'
import { Search, Filter, ArrowUpRight, AlertCircle, AlertTriangle, CheckCircle2, PackageCheck } from 'lucide-vue-next'

const props = defineProps({
  products: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['select-product'])

const searchQuery = ref('')
const selectedStatus = ref('all')

const filteredProducts = computed(() => {
  return props.products.filter(p => {
    const matchesSearch = p.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                          p.sku.toLowerCase().includes(searchQuery.value.toLowerCase())
    const status = p.latest_recommendation?.status || 'safe'
    const matchesStatus = selectedStatus.value === 'all' || status === selectedStatus.value
    return matchesSearch && matchesStatus
  })
})

const getStatusBadge = (status) => {
  switch (status) {
    case 'critical':
      return { label: 'Critical', bg: 'bg-rose-50 text-rose-700 border-rose-100', icon: AlertCircle }
    case 'warning':
      return { label: 'Warning', bg: 'bg-amber-50 text-amber-700 border-amber-100', icon: AlertTriangle }
    default:
      return { label: 'Safe', bg: 'bg-emerald-50 text-emerald-700 border-emerald-100', icon: CheckCircle2 }
  }
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-[2rem] border border-pink-100/50 shadow-sm">
      <div>
        <h3 class="text-xl font-bold text-gray-900">Master Data Produk</h3>
        <p class="text-xs text-gray-400 mt-1">Daftar item inventaris, stok aktual, lead time, dan batas reorder point</p>
      </div>

      <!-- Filters & Search -->
      <div class="flex items-center gap-3 flex-wrap">
        <div class="relative">
          <Search class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Cari SKU atau nama produk..."
            class="pl-10 pr-4 py-2 bg-[#FCF8FA] border border-pink-100 rounded-full text-xs text-gray-700 placeholder-gray-400 focus:outline-none focus:border-pink-300 w-64"
          />
        </div>

        <div class="flex items-center gap-1 bg-[#FCF8FA] p-1 border border-pink-100 rounded-full text-xs">
          <button
            v-for="st in [
              { id: 'all', label: 'Semua' },
              { id: 'critical', label: 'Critical' },
              { id: 'warning', label: 'Warning' },
              { id: 'safe', label: 'Safe' }
            ]"
            :key="st.id"
            @click="selectedStatus = st.id"
            :class="[
              'px-3 py-1.5 rounded-full font-semibold transition-all',
              selectedStatus === st.id ? 'bg-white shadow-sm text-pink-600' : 'text-gray-500 hover:text-gray-800'
            ]"
          >
            {{ st.label }}
          </button>
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-[2rem] border border-pink-100/50 shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-xs">
          <thead>
            <tr class="border-b border-pink-50 bg-[#FCF8FA] text-gray-500 font-semibold">
              <th class="py-4 px-6">SKU</th>
              <th class="py-4 px-6">Nama Produk</th>
              <th class="py-4 px-6 text-right">Stok Aktual</th>
              <th class="py-4 px-6 text-right">Lead Time</th>
              <th class="py-4 px-6 text-right">Safety Stock</th>
              <th class="py-4 px-6 text-right">Reorder Point</th>
              <th class="py-4 px-6 text-center">Status</th>
              <th class="py-4 px-6 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-pink-50/60">
            <tr
              v-for="product in filteredProducts"
              :key="product.id"
              class="hover:bg-pink-50/30 transition-colors group"
            >
              <td class="py-4 px-6 font-mono font-semibold text-gray-500">{{ product.sku }}</td>
              <td class="py-4 px-6 font-bold text-gray-800">{{ product.name }}</td>
              <td class="py-4 px-6 text-right font-black text-gray-900">
                {{ product.current_stock }} <span class="text-[10px] text-gray-400 font-normal">{{ product.unit }}</span>
              </td>
              <td class="py-4 px-6 text-right text-gray-600">{{ product.lead_time_days }} hari</td>
              <td class="py-4 px-6 text-right text-gray-600 font-medium">
                {{ product.latest_recommendation?.safety_stock ?? '-' }}
              </td>
              <td class="py-4 px-6 text-right text-gray-600 font-medium">
                {{ product.latest_recommendation?.reorder_point ?? '-' }}
              </td>
              <td class="py-4 px-6 text-center">
                <span
                  :class="[
                    'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold border',
                    getStatusBadge(product.latest_recommendation?.status).bg
                  ]"
                >
                  <component :is="getStatusBadge(product.latest_recommendation?.status).icon" class="w-3.5 h-3.5" />
                  {{ getStatusBadge(product.latest_recommendation?.status).label }}
                </span>
              </td>
              <td class="py-4 px-6 text-right">
                <button
                  @click="$emit('select-product', product)"
                  class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full bg-pink-50 hover:bg-pink-100 text-pink-600 font-semibold text-xs transition-colors"
                >
                  Analisis Forecast
                  <ArrowUpRight class="w-3.5 h-3.5" />
                </button>
              </td>
            </tr>
            <tr v-if="filteredProducts.length === 0">
              <td colspan="8" class="py-12 text-center text-gray-400">
                Tidak ada produk yang cocok dengan pencarian.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

