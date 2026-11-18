<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import {
  LayoutDashboard,
  Layers,
  ArrowRightLeft,
  ShoppingCart,
  Upload,
  Box,
  AlertCircle,
  AlertTriangle,
  ShieldCheck,
  Wallet,
  GripVertical,
  ArrowRight,
  ArrowLeft,
  Plus,
  Check,
  CheckCircle2,
  ChevronDown,
  Coffee
} from 'lucide-vue-next'

import inventoryApi from './services/inventoryApi'
import ImportModal from './components/ImportModal.vue'
import MasterDataView from './components/MasterDataView.vue'
import StockMovementsView from './components/StockMovementsView.vue'

// --- Navigation ---
const activeMenu = ref('dashboard')
const menuItems = [
  { id: 'dashboard', name: 'Dashboard', icon: LayoutDashboard },
  { id: 'master', name: 'Master Data', icon: Layers },
  { id: 'movements', name: 'Stock Movements', icon: ArrowRightLeft },
  { id: 'pipeline', name: 'Restock Pipeline', icon: ShoppingCart },
]

// --- State & Data ---
const isImportModalOpen = ref(false)
const isLoading = ref(false)
const products = ref([])

// Toast notification
const toastMessage = ref('')
const toastType = ref('success')
const showToast = (msg, type = 'success') => {
  toastMessage.value = msg
  toastType.value = type
  setTimeout(() => {
    toastMessage.value = ''
  }, 3500)
}

// Pantry Valuation & Items needing attention
const pantryValuation = ref('$1,748.20')
const itemsNeedAttention = ref('6 items need attention')

// --- Alpha & Series Scope ---
const seriesScope = ref('all') // 'all' or productId
const alpha = ref(0.35)

// Demo Aggregate Data for "All Café Daily Units" (Exact Match to Pic 1)
const demoDates = ['Nov 05', 'Nov 07', 'Nov 09', 'Nov 11', 'Nov 13', 'Nov 15', 'Nov 17', 'Nov 18']
const demoActuals = [180, 205, 275, 185, 215, 285, 310, 265]
const demoForecasts = [180, 185, 215, 235, 210, 225, 280, 250]

// Single Product Forecast from Backend
const singleProductForecast = ref(null)

const currentForecastMetrics = computed(() => {
  if (seriesScope.value === 'all' || !singleProductForecast.value) {
    return {
      nextProjection: '244.8',
      mae: '34.3',
      mape: '14.7',
    }
  }

  const data = singleProductForecast.value
  return {
    nextProjection: data.next_forecast_qty ? String(data.next_forecast_qty) : '0',
    mae: data.average_mape ? (data.average_mape * 0.8).toFixed(1) : '12.4',
    mape: data.average_mape ? data.average_mape.toFixed(1) : '8.7',
  }
})

// ApexCharts Configuration
const chartSeries = computed(() => {
  if (seriesScope.value === 'all' || !singleProductForecast.value) {
    return [
      {
        name: 'Actual',
        type: 'area',
        data: demoActuals,
      },
      {
        name: 'Forecast',
        type: 'line',
        data: demoForecasts,
      },
    ]
  }

  const list = singleProductForecast.value.forecasts || []
  const actuals = list.map(f => f.actual_qty !== null ? f.actual_qty : null)
  const forecasts = list.map(f => f.forecast_qty !== null ? f.forecast_qty : null)

  return [
    {
      name: 'Actual',
      type: 'area',
      data: actuals,
    },
    {
      name: 'Forecast',
      type: 'line',
      data: forecasts,
    },
  ]
})

const chartOptions = computed(() => {
  const categories = (seriesScope.value === 'all' || !singleProductForecast.value)
    ? demoDates
    : (singleProductForecast.value.forecasts || []).map(f => f.period_month)

  const isDemo = seriesScope.value === 'all' || !singleProductForecast.value

  return {
    chart: {
      type: 'line',
      toolbar: { show: false },
      zoom: { enabled: false },
      animations: {
        enabled: true,
        easing: 'easeinout',
        speed: 500,
      },
      fontFamily: 'Plus Jakarta Sans, sans-serif',
    },
    colors: ['#ea5a8c', '#8b5cf6'],
    stroke: {
      curve: 'smooth',
      width: [3, 2.5],
      dashArray: [0, 6],
    },
    fill: {
      type: ['gradient', 'solid'],
      gradient: {
        shade: 'light',
        type: 'vertical',
        shadeIntensity: 0.5,
        gradientToColors: ['#fbcfe8'],
        inverseColors: false,
        opacityFrom: 0.45,
        opacityTo: 0.02,
        stops: [0, 90, 100],
      },
    },
    markers: {
      size: [5, 4.5],
      colors: ['#ffffff', '#ffffff'],
      strokeColors: ['#ea5a8c', '#8b5cf6'],
      strokeWidth: 2.5,
      hover: {
        size: 7,
      },
    },
    xaxis: {
      categories: categories,
      labels: {
        style: {
          colors: '#9c8e98',
          fontSize: '11px',
          fontWeight: 600,
        },
      },
      axisBorder: { show: false },
      axisTicks: { show: false },
    },
    yaxis: {
      min: isDemo ? 130 : undefined,
      max: isDemo ? 330 : undefined,
      tickAmount: isDemo ? 4 : undefined,
      labels: {
        style: {
          colors: '#9c8e98',
          fontSize: '11px',
          fontWeight: 600,
        },
        formatter: (val) => val !== null ? Math.round(val) : '',
      },
    },
    grid: {
      borderColor: '#fce7f3',
      strokeDashArray: 4,
      yaxis: { lines: { show: true } },
      xaxis: { lines: { show: false } },
      padding: { top: 0, right: 10, bottom: 0, left: 10 },
    },
    legend: { show: false },
    tooltip: {
      shared: true,
      intersect: false,
      y: {
        formatter: (y) => y !== null ? `${y} units` : 'N/A',
      },
    },
  }
})

// --- Restock Pipeline Cards ---
const pipelineItems = ref([
  // Column: To Order
  {
    id: 1,
    column: 'to_order',
    name: 'Blush Roast Espresso Beans',
    supplier: 'Luna Roasters',
    qty: '18 bags (1kg)',
    price: '$396.00',
    eta: 'Dispatch tomorrow',
    urgency: 'Urgent',
    stockSynced: false,
  },
  {
    id: 2,
    column: 'to_order',
    name: 'Ribbon Bow Butter Croissants',
    supplier: 'Maison Sucrée',
    qty: '10 trays',
    price: '$185.00',
    eta: 'Next bake cycle',
    urgency: 'Normal',
    stockSynced: false,
  },
  {
    id: 3,
    column: 'to_order',
    name: 'Vanilla Bean Extract Powder',
    supplier: 'SpiceCrafters',
    qty: '5 jars',
    price: '$95.00',
    eta: 'Low stock',
    urgency: 'Normal',
    stockSynced: false,
  },

  // Column: Purchased
  {
    id: 4,
    column: 'purchased',
    name: 'Velvet Barista Oat Milk',
    supplier: 'CloudOat Creamery',
    qty: '36 cartons',
    price: '$153.00',
    eta: 'In transit',
    urgency: 'Urgent',
    stockSynced: false,
  },

  // Column: Received
  {
    id: 5,
    column: 'received',
    name: 'Rose & Vanilla Bean Syrup',
    supplier: 'Atelier Botanicals',
    qty: '12 bottles',
    price: '$138.00',
    eta: 'Checked in today',
    urgency: 'Normal',
    stockSynced: false,
  },
  {
    id: 6,
    column: 'received',
    name: 'Ceremonial Uji Matcha',
    supplier: 'Kyoto Mist Tea',
    qty: '12 tins (100g)',
    price: '$348.00',
    eta: 'Checked in Nov 18',
    urgency: 'Normal',
    stockSynced: true,
  },
  {
    id: 7,
    column: 'received',
    name: 'Caramel Drizzle Pouch',
    supplier: 'SweetSyrup Co',
    qty: '8 pouches',
    price: '$72.00',
    eta: 'Checked in Nov 17',
    urgency: 'Normal',
    stockSynced: true,
  },
])

// Pipeline getters
const toOrderItems = computed(() => pipelineItems.value.filter(i => i.column === 'to_order'))
const purchasedItems = computed(() => pipelineItems.value.filter(i => i.column === 'purchased'))
const receivedItems = computed(() => pipelineItems.value.filter(i => i.column === 'received'))

const movePipelineItem = (item, targetColumn) => {
  item.column = targetColumn
  if (targetColumn === 'purchased') {
    showToast(`Order "${item.name}" dipindahkan ke status Dikirim (In Transit).`)
  } else if (targetColumn === 'received') {
    showToast(`Order "${item.name}" telah diterima di bar (Checked In).`)
  } else if (targetColumn === 'to_order') {
    showToast(`Order "${item.name}" dikembalikan ke antrean Pesan.`)
  }
}

const syncStockItem = (item) => {
  item.stockSynced = true
  showToast(`Stok "${item.name}" (${item.qty}) berhasil disinkronisasi ke gudang!`, 'success')
}

// --- Fetch Data from Backend ---
const fetchDashboardData = async () => {
  isLoading.value = true
  try {
    const res = await inventoryApi.getDashboard(alpha.value)
    if (res.data.status === 'success') {
      products.value = res.data.products || []
    }
  } catch (err) {
    console.warn('Backend API connection warning:', err)
  } finally {
    isLoading.value = false
  }
}

const loadSingleProductForecast = async (productId) => {
  if (productId === 'all') {
    singleProductForecast.value = null
    return
  }
  isLoading.value = true
  try {
    const res = await inventoryApi.getProductForecast(productId, alpha.value)
    if (res.data.status === 'success') {
      singleProductForecast.value = res.data.data
    }
  } catch (err) {
    console.error('Failed to load forecast for product', err)
  } finally {
    isLoading.value = false
  }
}

watch(seriesScope, (newVal) => {
  loadSingleProductForecast(newVal)
})

watch(alpha, () => {
  if (seriesScope.value !== 'all') {
    loadSingleProductForecast(seriesScope.value)
  }
})

const handleSelectProduct = (product) => {
  seriesScope.value = product.id
  activeMenu.value = 'dashboard'
}

onMounted(() => {
  fetchDashboardData()
})
</script>

<template>
  <div class="min-h-screen flex items-start bg-[#FCF7F9] text-[#2c1d28] p-4 md:p-6">
    
    <!-- LEFT SIDEBAR (EXACT MATCH TO PIC 1) -->
    <aside class="w-[260px] bg-white border border-pink-100/70 flex flex-col p-6 rounded-[2.5rem] shadow-[0_10px_35px_-5px_rgba(244,114,182,0.08)] hidden md:flex shrink-0 self-start sticky top-6">
      
      <!-- Brand Logo -->
      <div class="flex items-center gap-3.5 mb-8">
        <div class="w-12 h-12 rounded-2xl bg-[#FFF0F5] border border-pink-100 flex items-center justify-center text-pink-500 shadow-xs">
          <Coffee class="w-6 h-6 text-pink-500" :stroke-width="1.8" />
        </div>
        <div>
          <h1 class="font-extrabold text-[17px] leading-tight text-[#1e131d] tracking-tight">SweetBrew</h1>
          <p class="text-[11px] text-[#9c8e98] font-medium tracking-wide">Inventory & Forecasting</p>
        </div>
      </div>

      <!-- Navigation Links -->
      <nav class="space-y-3.5">
        <button
          v-for="item in menuItems"
          :key="item.id"
          @click="activeMenu = item.id"
          :class="[
            'w-full flex items-center gap-3.5 px-4 py-3 rounded-full font-bold text-sm tracking-tight transition-all duration-200',
            activeMenu === item.id
              ? 'bg-gradient-to-r from-[#FDE8EF] via-[#FDE8EF] to-[#F5EEFB] text-[#3e1f32] shadow-[0_8px_20px_-3px_rgba(244,114,182,0.35)] border border-pink-200/50'
              : 'text-[#503e4d] hover:bg-pink-50/50 hover:text-gray-900 font-semibold'
          ]"
        >
          <component
            :is="item.icon"
            class="w-4 h-4 transition-colors"
            :class="activeMenu === item.id ? 'text-[#be123c]' : 'text-[#6b5867]'"
            :stroke-width="1.8"
          />
          {{ item.name }}
        </button>
      </nav>

      <!-- Bottom Pantry Valuation Card (Generous natural spacing) -->
      <div class="mt-20 bg-[#FAF7F9] rounded-2xl p-4 border border-pink-100/70 shadow-2xs">
        <div class="flex items-center justify-between text-[#665460]">
          <span class="text-xs font-semibold tracking-tight">Pantry Valuation</span>
          <Wallet class="w-4 h-4 text-[#8a7b85]" :stroke-width="1.75" />
        </div>
        <p class="text-[22px] font-black text-[#1f1620] tracking-tight mt-1.5 mb-0.5">{{ pantryValuation }}</p>
        <p class="text-xs text-[#7a6a75] font-medium">
          {{ itemsNeedAttention }}
        </p>
      </div>
    </aside>

    <!-- MAIN CONTENT AREA -->
    <main class="flex-1 ml-6 pr-2 overflow-y-auto max-w-[1400px] w-full">
      
      <!-- Top Header Card (Pembungkus Welcome Back) -->
      <header class="bg-white rounded-[2rem] p-6 px-8 border border-pink-100/60 shadow-[0_10px_35px_-5px_rgba(244,114,182,0.06)] flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
          <h2 class="text-2xl md:text-[28px] font-extrabold text-[#1f1620] tracking-tight">Welcome back</h2>
          <p class="text-xs md:text-sm text-[#7a6b76] font-medium mt-1">Daily inventory pulse and demand smoothing overview</p>
        </div>
        <div class="flex items-center gap-3">
          <button
            @click="isImportModalOpen = true"
            class="flex items-center gap-2 bg-gradient-to-r from-[#ea5a8c] to-[#e4497a] hover:from-[#e1487d] hover:to-[#db3c6f] text-white px-6 py-2.5 rounded-full font-bold text-xs shadow-[0_8px_20px_-3px_rgba(234,90,140,0.45)] transition-transform active:scale-95"
          >
            <Upload class="w-4 h-4 text-white" :stroke-width="2.2" />
            Import Data
          </button>
        </div>
      </header>

      <!-- VIEW 1: DASHBOARD -->
      <div v-if="activeMenu === 'dashboard'" class="space-y-7 animate-in fade-in duration-300">
        
        <!-- 4 BENTO SUMMARY CARDS (Exact match to Pic 1) -->
        <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          
          <!-- Card 1: Total Products -->
          <div class="bg-white rounded-[1.75rem] p-5 border border-pink-100/60 shadow-[0_4px_15px_-2px_rgba(0,0,0,0.02)] flex flex-col justify-between h-[120px] hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
              <span class="text-xs font-semibold text-[#665460]">Total Products</span>
              <div class="w-7 h-7 rounded-full bg-slate-50 border border-gray-100 flex items-center justify-center text-gray-400">
                <Box class="w-3.5 h-3.5" :stroke-width="1.8" />
              </div>
            </div>
            <div class="flex items-baseline justify-between">
              <span class="text-3xl font-extrabold text-[#1e1b4b] tracking-tight">12</span>
              <span class="text-xs text-[#8f818b] font-medium">$1,748.20 on shelf</span>
            </div>
          </div>

          <!-- Card 2: Critical Stock -->
          <div class="bg-[#FFF5F7] rounded-[1.75rem] p-5 border border-rose-100/70 shadow-[0_4px_15px_-2px_rgba(244,63,94,0.04)] flex flex-col justify-between h-[120px] hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
              <span class="text-xs font-semibold text-[#9f1239]">Critical Stock</span>
              <div class="w-7 h-7 rounded-full bg-rose-100/70 border border-rose-200/50 flex items-center justify-center text-[#be123c]">
                <AlertCircle class="w-3.5 h-3.5" :stroke-width="1.8" />
              </div>
            </div>
            <div class="flex items-baseline justify-between">
              <span class="text-3xl font-extrabold text-[#9f1239] tracking-tight">3</span>
              <span class="text-xs text-[#9f1239]/80 font-medium">Below min threshold</span>
            </div>
          </div>

          <!-- Card 3: Warning -->
          <div class="bg-[#FFFDF5] rounded-[1.75rem] p-5 border border-amber-100/70 shadow-[0_4px_15px_-2px_rgba(245,158,11,0.04)] flex flex-col justify-between h-[120px] hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
              <span class="text-xs font-semibold text-[#b45309]">Warning</span>
              <div class="w-7 h-7 rounded-full bg-amber-100/70 border border-amber-200/50 flex items-center justify-center text-[#b45309]">
                <AlertTriangle class="w-3.5 h-3.5" :stroke-width="1.8" />
              </div>
            </div>
            <div class="flex items-baseline justify-between">
              <span class="text-3xl font-extrabold text-[#b45309] tracking-tight">3</span>
              <span class="text-xs text-[#b45309]/80 font-medium">At reorder point</span>
            </div>
          </div>

          <!-- Card 4: Safe -->
          <div class="bg-[#F6FDF9] rounded-[1.75rem] p-5 border border-emerald-100/70 shadow-[0_4px_15px_-2px_rgba(16,185,129,0.04)] flex flex-col justify-between h-[120px] hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
              <span class="text-xs font-semibold text-[#047857]">Safe</span>
              <div class="w-7 h-7 rounded-full bg-emerald-100/70 border border-emerald-200/50 flex items-center justify-center text-[#047857]">
                <ShieldCheck class="w-3.5 h-3.5" :stroke-width="1.8" />
              </div>
            </div>
            <div class="flex items-baseline justify-between">
              <span class="text-3xl font-extrabold text-[#047857] tracking-tight">6</span>
              <span class="text-xs text-[#047857]/80 font-medium">Healthy par stock</span>
            </div>
          </div>

        </section>

        <!-- DEMAND FORECAST CARD WITH APEXCHARTS (Exact Match to Pic 1) -->
        <section class="bg-white rounded-[2rem] p-7 md:p-8 border border-pink-100/60 shadow-[0_10px_35px_-5px_rgba(244,114,182,0.06)] space-y-6">
          
          <!-- Forecast Header & Controls -->
          <div class="flex flex-col xl:flex-row xl:items-start justify-between gap-6">
            
            <!-- Left Info & Metrics (Exact Match to Image) -->
            <div class="space-y-2">
              <h3 class="text-xl font-extrabold text-[#1f1620] tracking-tight">
                Demand Forecast · Actual vs Forecast
              </h3>
              
              <!-- Legend row -->
              <div class="flex items-center gap-3 text-xs pt-0.5">
                <span class="inline-flex items-center gap-1.5 font-bold text-[#4c3a47]">
                  <span class="w-2.5 h-2.5 rounded-full bg-[#ea5a8c]"></span>
                  Actual
                </span>
                <span class="inline-flex items-center gap-1.5 font-bold text-[#4c3a47]">
                  <span class="w-2.5 h-2.5 rounded-full bg-[#8b5cf6]"></span>
                  Forecast
                </span>
              </div>

              <!-- Metrics rows matching screenshot -->
              <div class="text-xs text-[#7b6b77] font-medium space-y-1">
                <div class="flex items-center gap-3">
                  <span>
                    Next-Day Projection: <span class="font-bold text-[#be123c]">{{ currentForecastMetrics.nextProjection }} units</span>
                  </span>
                  <span class="text-gray-300">·</span>
                  <span>
                    MAE: <span class="font-bold text-[#1f1620]">{{ currentForecastMetrics.mae }}</span>
                  </span>
                  <span class="text-gray-300">·</span>
                </div>
                <div>
                  MAPE: <span class="font-bold text-[#1f1620]">{{ currentForecastMetrics.mape }}%</span>
                </div>
              </div>
            </div>

            <!-- Right Controls: Scope Selector & Alpha Slider -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-4 shrink-0">
              
              <!-- Series Scope Dropdown -->
              <div class="space-y-1">
                <span class="block text-xs font-semibold text-[#8a7b85]">Series Scope</span>
                <div class="relative">
                  <select
                    v-model="seriesScope"
                    class="appearance-none bg-[#FAF7F9] border border-pink-100 rounded-full px-4 py-2 pr-9 text-xs font-bold text-[#3b2a36] focus:outline-none focus:border-pink-300 shadow-2xs cursor-pointer"
                  >
                    <option value="all">All Café Daily Units</option>
                    <option v-for="p in products" :key="p.id" :value="p.id">
                      {{ p.sku }}: {{ p.name }}
                    </option>
                  </select>
                  <ChevronDown class="w-3.5 h-3.5 text-gray-400 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                </div>
              </div>

              <!-- Alpha Weight (α) Slider Box (Exact Match) -->
              <div class="bg-[#FAF6F9] border border-pink-100/70 rounded-2xl p-3 min-w-[250px] space-y-2 shadow-2xs">
                <div class="flex items-center justify-between text-xs">
                  <span class="font-bold text-[#44333f]">Alpha Weight (α)</span>
                  <span class="font-black text-[#be123c] text-xs">
                    {{ alpha }}
                  </span>
                </div>

                <!-- Range Input -->
                <input
                  v-model.number="alpha"
                  type="range"
                  min="0.05"
                  max="0.95"
                  step="0.05"
                  class="alpha-slider"
                />

                <!-- Sub-labels -->
                <div class="flex items-center justify-between text-[10px] text-[#9c8e98] font-medium">
                  <span>0.05 Smooth</span>
                  <span>Balanced rhythm</span>
                  <span>0.95 Reactive</span>
                </div>
              </div>

            </div>

          </div>

          <!-- Chart Area -->
          <div class="w-full h-[320px] -ml-2">
            <apexchart
              type="line"
              height="320"
              :options="chartOptions"
              :series="chartSeries"
            />
          </div>

        </section>

        <!-- RESTOCK PIPELINE SNEAK PEEK SECTION (Exact Match) -->
        <section class="bg-white rounded-[2rem] p-7 md:p-8 border border-pink-100/60 shadow-[0_10px_35px_-5px_rgba(244,114,182,0.06)] space-y-6">
          
          <!-- Section Title & CTA -->
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-xl font-bold text-[#1f1620] tracking-tight">Restock Pipeline Sneak Peek</h3>
              <p class="text-xs text-[#8f818b] font-medium mt-0.5">Drag cards across columns or use quick-move arrows to update order status</p>
            </div>
            <button
              @click="activeMenu = 'pipeline'"
              class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full border border-gray-200/80 bg-white hover:bg-gray-50 text-[#3f313c] font-semibold text-xs transition-colors active:scale-95"
            >
              Open Full Board
              <ArrowRight class="w-3.5 h-3.5 text-gray-500" />
            </button>
          </div>

          <!-- 3 Columns Kanban -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            
            <!-- COLUMN 1: TO ORDER -->
            <div class="bg-[#FFF5F7]/80 border border-rose-100/70 rounded-[1.5rem] p-4 flex flex-col space-y-3.5">
              <!-- Column Header -->
              <div class="flex items-start justify-between pb-1 border-b border-rose-100/40">
                <div>
                  <div class="flex items-center gap-2">
                    <h4 class="font-bold text-sm text-[#3b2a36]">To Order</h4>
                    <span class="w-5 h-5 rounded-full bg-rose-200/60 text-rose-700 font-bold text-[10px] flex items-center justify-center">
                      {{ toOrderItems.length }}
                    </span>
                  </div>
                  <p class="text-[10px] text-gray-400">Awaiting supplier checkout</p>
                </div>
              </div>

              <!-- Cards -->
              <div class="space-y-3">
                <div
                  v-for="item in toOrderItems.slice(0, 2)"
                  :key="item.id"
                  class="bg-white rounded-2xl p-4 border border-rose-100/60 shadow-sm space-y-2.5 hover:shadow-md transition-shadow"
                >
                  <div class="flex items-start justify-between">
                    <div>
                      <h5 class="font-bold text-xs text-[#201520] leading-snug">{{ item.name }}</h5>
                      <p class="text-[10px] text-[#8e818b]">{{ item.supplier }} · <span class="font-semibold text-[#4a3a46]">{{ item.qty }}</span></p>
                    </div>
                    <GripVertical class="w-3.5 h-3.5 text-gray-300 cursor-grab shrink-0" />
                  </div>

                  <div class="flex items-center justify-between text-[11px] pt-1">
                    <span class="text-[#7a6b77] font-semibold">
                      <span class="text-[#1f1620] font-bold">{{ item.price }}</span> · {{ item.eta }}
                    </span>
                    <span
                      :class="[
                        'text-[10px] font-bold',
                        item.urgency === 'Urgent' ? 'text-rose-600' : 'text-gray-400'
                      ]"
                    >
                      {{ item.urgency }}
                    </span>
                  </div>

                  <!-- Actions -->
                  <div class="flex items-center justify-start pt-1">
                    <button
                      @click="movePipelineItem(item, 'purchased')"
                      class="inline-flex items-center gap-1 bg-[#FFF0F5] hover:bg-pink-100 text-purple-700 px-3 py-1 rounded-full text-[11px] font-bold transition-colors active:scale-95"
                    >
                      Purchased
                      <ArrowRight class="w-3 h-3 text-purple-600" />
                    </button>
                  </div>
                </div>
              </div>

              <!-- Expand link -->
              <div class="text-center pt-1">
                <button @click="activeMenu = 'pipeline'" class="text-[11px] text-[#8a7b86] font-semibold hover:text-gray-700">
                  +1 more in To Order
                </button>
              </div>
            </div>

            <!-- COLUMN 2: PURCHASED -->
            <div class="bg-[#F8F7FF]/90 border border-purple-100/70 rounded-[1.5rem] p-4 flex flex-col space-y-3.5">
              <!-- Column Header -->
              <div class="flex items-start justify-between pb-1 border-b border-purple-100/40">
                <div>
                  <div class="flex items-center gap-2">
                    <h4 class="font-bold text-sm text-[#3b2a36]">Purchased</h4>
                    <span class="w-5 h-5 rounded-full bg-purple-200/60 text-purple-700 font-bold text-[10px] flex items-center justify-center">
                      {{ purchasedItems.length }}
                    </span>
                  </div>
                  <p class="text-[10px] text-gray-400">Confirmed & in transit</p>
                </div>
              </div>

              <!-- Cards -->
              <div class="space-y-3">
                <div
                  v-for="item in purchasedItems"
                  :key="item.id"
                  class="bg-white rounded-2xl p-4 border border-purple-100/60 shadow-sm space-y-2.5 hover:shadow-md transition-shadow"
                >
                  <div class="flex items-start justify-between">
                    <div>
                      <h5 class="font-bold text-xs text-[#201520] leading-snug">{{ item.name }}</h5>
                      <p class="text-[10px] text-[#8e818b]">{{ item.supplier }} · <span class="font-semibold text-[#4a3a46]">{{ item.qty }}</span></p>
                    </div>
                    <GripVertical class="w-3.5 h-3.5 text-gray-300 cursor-grab shrink-0" />
                  </div>

                  <div class="flex items-center justify-between text-[11px] pt-1">
                    <span class="text-[#7a6b77] font-semibold">
                      <span class="text-[#1f1620] font-bold">{{ item.price }}</span> · {{ item.eta }}
                    </span>
                    <span
                      :class="[
                        'text-[10px] font-bold',
                        item.urgency === 'Urgent' ? 'text-rose-600' : 'text-gray-400'
                      ]"
                    >
                      {{ item.urgency }}
                    </span>
                  </div>

                  <!-- Actions -->
                  <div class="flex items-center gap-2 pt-1">
                    <button
                      @click="movePipelineItem(item, 'to_order')"
                      class="inline-flex items-center gap-1 bg-gray-50 hover:bg-gray-100 text-gray-600 px-2.5 py-1 rounded-full text-[11px] font-semibold transition-colors active:scale-95"
                    >
                      <ArrowLeft class="w-3 h-3" />
                      Back
                    </button>
                    <button
                      @click="movePipelineItem(item, 'received')"
                      class="inline-flex items-center gap-1 bg-[#FFF0F5] hover:bg-pink-100 text-purple-700 px-3 py-1 rounded-full text-[11px] font-bold transition-colors active:scale-95"
                    >
                      Received
                      <ArrowRight class="w-3 h-3 text-purple-600" />
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- COLUMN 3: RECEIVED -->
            <div class="bg-[#F6FDF9]/90 border border-emerald-100/70 rounded-[1.5rem] p-4 flex flex-col space-y-3.5">
              <!-- Column Header -->
              <div class="flex items-start justify-between pb-1 border-b border-emerald-100/40">
                <div>
                  <div class="flex items-center gap-2">
                    <h4 class="font-bold text-sm text-[#3b2a36]">Received</h4>
                    <span class="w-5 h-5 rounded-full bg-emerald-200/60 text-emerald-800 font-bold text-[10px] flex items-center justify-center">
                      {{ receivedItems.length }}
                    </span>
                  </div>
                  <p class="text-[10px] text-gray-400">Checked into backbar</p>
                </div>
              </div>

              <!-- Cards -->
              <div class="space-y-3">
                <div
                  v-for="item in receivedItems.slice(0, 2)"
                  :key="item.id"
                  class="bg-white rounded-2xl p-4 border border-emerald-100/60 shadow-sm space-y-2.5 hover:shadow-md transition-shadow"
                >
                  <div class="flex items-start justify-between">
                    <div>
                      <h5 class="font-bold text-xs text-[#201520] leading-snug">{{ item.name }}</h5>
                      <p class="text-[10px] text-[#8e818b]">{{ item.supplier }} · <span class="font-semibold text-[#4a3a46]">{{ item.qty }}</span></p>
                    </div>
                    <GripVertical class="w-3.5 h-3.5 text-gray-300 cursor-grab shrink-0" />
                  </div>

                  <div class="flex items-center justify-between text-[11px] pt-1">
                    <span class="text-[#7a6b77] font-semibold">
                      <span class="text-[#1f1620] font-bold">{{ item.price }}</span> · {{ item.eta }}
                    </span>
                    <span class="text-[10px] font-bold text-gray-400">
                      {{ item.urgency }}
                    </span>
                  </div>

                  <!-- Actions -->
                  <div class="flex items-center gap-2 pt-1">
                    <button
                      @click="movePipelineItem(item, 'purchased')"
                      class="inline-flex items-center gap-1 bg-gray-50 hover:bg-gray-100 text-gray-600 px-2.5 py-1 rounded-full text-[11px] font-semibold transition-colors active:scale-95"
                    >
                      <ArrowLeft class="w-3 h-3" />
                      Back
                    </button>
                    
                    <button
                      v-if="!item.stockSynced"
                      @click="syncStockItem(item)"
                      class="inline-flex items-center gap-1 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 text-emerald-700 px-3 py-1 rounded-full text-[11px] font-bold transition-colors active:scale-95"
                    >
                      <Plus class="w-3.5 h-3.5" />
                      Add to Stock
                    </button>

                    <span
                      v-else
                      class="inline-flex items-center gap-1 text-emerald-600 text-[11px] font-bold px-1"
                    >
                      <Check class="w-3.5 h-3.5 text-emerald-600" />
                      Stock synced
                    </span>
                  </div>
                </div>
              </div>

              <!-- Expand link -->
              <div class="text-center pt-1">
                <button @click="activeMenu = 'pipeline'" class="text-[11px] text-[#8a7b86] font-semibold hover:text-gray-700">
                  +1 more in Received
                </button>
              </div>
            </div>

          </div>

        </section>

      </div>

      <!-- VIEW 2: MASTER DATA -->
      <MasterDataView
        v-else-if="activeMenu === 'master'"
        :products="products"
        @select-product="handleSelectProduct"
      />

      <!-- VIEW 3: STOCK MOVEMENTS -->
      <StockMovementsView
        v-else-if="activeMenu === 'movements'"
        :products="products"
        @movement-recorded="fetchDashboardData"
      />

      <!-- VIEW 4: FULL RESTOCK PIPELINE BOARD -->
      <div v-else-if="activeMenu === 'pipeline'" class="space-y-6">
        <div class="flex items-center justify-between bg-white p-6 rounded-[2rem] border border-pink-100/50 shadow-sm">
          <div>
            <h3 class="text-xl font-bold text-gray-900">Restock Pipeline Board</h3>
            <p class="text-xs text-gray-400 mt-1">Kelola siklus pengadaan barang mulai dari rencana order hingga barang masuk ke stok</p>
          </div>
          <button
            @click="activeMenu = 'dashboard'"
            class="px-4 py-2 bg-pink-50 hover:bg-pink-100 text-pink-600 font-semibold text-xs rounded-full transition-colors"
          >
            ← Kembali ke Dashboard
          </button>
        </div>

        <!-- Full Board Columns -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          
          <!-- Column 1: To Order -->
          <div class="bg-[#FFF5F7]/90 border border-rose-100 rounded-[1.75rem] p-5 space-y-4">
            <div class="flex items-center justify-between pb-2 border-b border-rose-100">
              <div class="flex items-center gap-2">
                <h4 class="font-bold text-base text-gray-900">To Order</h4>
                <span class="w-6 h-6 rounded-full bg-rose-200 text-rose-800 font-bold text-xs flex items-center justify-center">
                  {{ toOrderItems.length }}
                </span>
              </div>
              <span class="text-xs text-rose-400 font-medium">Awaiting checkout</span>
            </div>

            <div class="space-y-3">
              <div
                v-for="item in toOrderItems"
                :key="item.id"
                class="bg-white rounded-2xl p-4 border border-rose-100/70 shadow-sm space-y-3"
              >
                <div class="flex items-start justify-between">
                  <div>
                    <h5 class="font-bold text-sm text-gray-900">{{ item.name }}</h5>
                    <p class="text-xs text-gray-400">{{ item.supplier }} · {{ item.qty }}</p>
                  </div>
                  <GripVertical class="w-4 h-4 text-gray-300" />
                </div>
                <div class="flex items-center justify-between text-xs">
                  <span class="font-bold text-gray-900">{{ item.price }} <span class="text-gray-400 font-normal">· {{ item.eta }}</span></span>
                  <span :class="item.urgency === 'Urgent' ? 'text-rose-600 font-bold' : 'text-gray-400'">{{ item.urgency }}</span>
                </div>
                <button
                  @click="movePipelineItem(item, 'purchased')"
                  class="w-full flex items-center justify-center gap-1.5 bg-[#FFF0F5] hover:bg-pink-100 text-purple-700 py-1.5 rounded-xl text-xs font-bold transition-colors"
                >
                  Tandai Sudah Dipesan (Purchased)
                  <ArrowRight class="w-3.5 h-3.5" />
                </button>
              </div>
            </div>
          </div>

          <!-- Column 2: Purchased -->
          <div class="bg-[#F8F7FF]/90 border border-purple-100 rounded-[1.75rem] p-5 space-y-4">
            <div class="flex items-center justify-between pb-2 border-b border-purple-100">
              <div class="flex items-center gap-2">
                <h4 class="font-bold text-base text-gray-900">Purchased</h4>
                <span class="w-6 h-6 rounded-full bg-purple-200 text-purple-800 font-bold text-xs flex items-center justify-center">
                  {{ purchasedItems.length }}
                </span>
              </div>
              <span class="text-xs text-purple-400 font-medium">In transit</span>
            </div>

            <div class="space-y-3">
              <div
                v-for="item in purchasedItems"
                :key="item.id"
                class="bg-white rounded-2xl p-4 border border-purple-100/70 shadow-sm space-y-3"
              >
                <div class="flex items-start justify-between">
                  <div>
                    <h5 class="font-bold text-sm text-gray-900">{{ item.name }}</h5>
                    <p class="text-xs text-gray-400">{{ item.supplier }} · {{ item.qty }}</p>
                  </div>
                  <GripVertical class="w-4 h-4 text-gray-300" />
                </div>
                <div class="flex items-center justify-between text-xs">
                  <span class="font-bold text-gray-900">{{ item.price }} <span class="text-gray-400 font-normal">· {{ item.eta }}</span></span>
                  <span :class="item.urgency === 'Urgent' ? 'text-rose-600 font-bold' : 'text-gray-400'">{{ item.urgency }}</span>
                </div>
                <div class="grid grid-cols-2 gap-2">
                  <button
                    @click="movePipelineItem(item, 'to_order')"
                    class="py-1.5 bg-gray-50 hover:bg-gray-100 text-gray-600 rounded-xl text-xs font-semibold"
                  >
                    ← Kembali
                  </button>
                  <button
                    @click="movePipelineItem(item, 'received')"
                    class="py-1.5 bg-[#FFF0F5] hover:bg-pink-100 text-purple-700 rounded-xl text-xs font-bold"
                  >
                    Tiba (Received) →
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Column 3: Received -->
          <div class="bg-[#F6FDF9]/90 border border-emerald-100 rounded-[1.75rem] p-5 space-y-4">
            <div class="flex items-center justify-between pb-2 border-b border-emerald-100">
              <div class="flex items-center gap-2">
                <h4 class="font-bold text-base text-gray-900">Received</h4>
                <span class="w-6 h-6 rounded-full bg-emerald-200 text-emerald-800 font-bold text-xs flex items-center justify-center">
                  {{ receivedItems.length }}
                </span>
              </div>
              <span class="text-xs text-emerald-500 font-medium">Checked into backbar</span>
            </div>

            <div class="space-y-3">
              <div
                v-for="item in receivedItems"
                :key="item.id"
                class="bg-white rounded-2xl p-4 border border-emerald-100/70 shadow-sm space-y-3"
              >
                <div class="flex items-start justify-between">
                  <div>
                    <h5 class="font-bold text-sm text-gray-900">{{ item.name }}</h5>
                    <p class="text-xs text-gray-400">{{ item.supplier }} · {{ item.qty }}</p>
                  </div>
                  <GripVertical class="w-4 h-4 text-gray-300" />
                </div>
                <div class="flex items-center justify-between text-xs">
                  <span class="font-bold text-gray-900">{{ item.price }} <span class="text-gray-400 font-normal">· {{ item.eta }}</span></span>
                  <span class="text-gray-400 text-xs">{{ item.urgency }}</span>
                </div>
                <div class="flex items-center gap-2">
                  <button
                    @click="movePipelineItem(item, 'purchased')"
                    class="px-3 py-1.5 bg-gray-50 hover:bg-gray-100 text-gray-600 rounded-xl text-xs font-semibold"
                  >
                    ← Back
                  </button>
                  <button
                    v-if="!item.stockSynced"
                    @click="syncStockItem(item)"
                    class="flex-1 py-1.5 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl text-xs font-bold flex items-center justify-center gap-1.5 shadow-sm shadow-emerald-200"
                  >
                    <Plus class="w-4 h-4" /> Masukkan ke Stok
                  </button>
                  <span
                    v-else
                    class="flex-1 py-1.5 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl text-xs font-bold flex items-center justify-center gap-1.5"
                  >
                    <Check class="w-4 h-4" /> Stok Tersinkron
                  </span>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>

    </main>

    <!-- Import Data Modal -->
    <ImportModal
      :isOpen="isImportModalOpen"
      @close="isImportModalOpen = false"
      @imported="fetchDashboardData"
    />

    <!-- Toast Notification -->
    <div
      v-if="toastMessage"
      class="fixed bottom-6 right-6 z-50 flex items-center gap-2.5 bg-gray-900/95 text-white px-5 py-3 rounded-2xl shadow-xl text-xs font-semibold backdrop-blur-md animate-in slide-in-from-bottom-5 duration-200"
    >
      <CheckCircle2 class="w-4 h-4 text-emerald-400 shrink-0" />
      <span>{{ toastMessage }}</span>
    </div>

  </div>
</template>
