<template>
    <div class="bg-white shadow rounded-lg p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold text-gray-900">Orderbook</h2>
            <select
                v-model="selectedSymbol"
                @change="loadOrderbook"
                class="text-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
                <option value="BTC">BTC</option>
                <option value="ETH">ETH</option>
            </select>
        </div>

        <div v-if="loading" class="flex justify-center py-8">
            <svg
                class="animate-spin h-8 w-8 text-indigo-600"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
            >
                <circle
                    class="opacity-25"
                    cx="12"
                    cy="12"
                    r="10"
                    stroke="currentColor"
                    stroke-width="4"
                ></circle>
                <path
                    class="opacity-75"
                    fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                ></path>
            </svg>
        </div>

        <div v-else class="grid grid-cols-2 gap-4">
            <!-- Sell Orders (Asks) -->
            <div>
                <h3 class="text-sm font-semibold text-red-600 mb-2">Sell Orders (Asks)</h3>
                <div class="space-y-1 max-h-96 overflow-y-auto">
                    <div
                        v-for="order in asks"
                        :key="order.id"
                        class="flex justify-between items-center p-2 bg-red-50 rounded text-sm"
                    >
                        <div class="flex-1">
                            <div class="font-medium text-gray-900">
                                ${{ formatCurrency(order.price) }}
                            </div>
                            <div class="text-xs text-gray-500">
                                {{ formatAmount(order.amount) }}
                            </div>
                        </div>
                        <div class="text-xs text-gray-600">
                            ${{ formatCurrency(order.price * order.amount) }}
                        </div>
                    </div>
                    <div v-if="asks.length === 0" class="text-center py-4 text-gray-500 text-sm">
                        No sell orders
                    </div>
                </div>
            </div>

            <!-- Buy Orders (Bids) -->
            <div>
                <h3 class="text-sm font-semibold text-green-600 mb-2">Buy Orders (Bids)</h3>
                <div class="space-y-1 max-h-96 overflow-y-auto">
                    <div
                        v-for="order in bids"
                        :key="order.id"
                        class="flex justify-between items-center p-2 bg-green-50 rounded text-sm"
                    >
                        <div class="flex-1">
                            <div class="font-medium text-gray-900">
                                ${{ formatCurrency(order.price) }}
                            </div>
                            <div class="text-xs text-gray-500">
                                {{ formatAmount(order.amount) }}
                            </div>
                        </div>
                        <div class="text-xs text-gray-600">
                            ${{ formatCurrency(order.price * order.amount) }}
                        </div>
                    </div>
                    <div v-if="bids.length === 0" class="text-center py-4 text-gray-500 text-sm">
                        No buy orders
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import api from '../services/api';

const selectedSymbol = ref('BTC');
const orders = ref([]);
const loading = ref(false);
let refreshTimer = null;

const formatCurrency = (value) => {
    return parseFloat(value || 0).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
};

const formatAmount = (value) => {
    return parseFloat(value || 0).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 8,
    });
};

// Separate orders into bids and asks
const bids = computed(() => {
    return orders.value
        .filter(order => order.side === 'buy' && order.status === 1)
        .sort((a, b) => b.price - a.price); // Sort by price DESC
});

const asks = computed(() => {
    return orders.value
        .filter(order => order.side === 'sell' && order.status === 1)
        .sort((a, b) => a.price - b.price); // Sort by price ASC
});

const loadOrderbook = async () => {
    loading.value = true;
    try {
        const response = await api.get(`/orders?symbol=${selectedSymbol.value}`);
        orders.value = response.data;
    } catch (error) {
        console.error('Failed to load orderbook:', error);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    loadOrderbook();
    // Refresh orderbook every 5 seconds
    refreshTimer = setInterval(loadOrderbook, 5000);
});

// Watch for symbol changes
watch(selectedSymbol, () => {
    loadOrderbook();
});

// Handle real-time order matched events (optional integration hook)
const handleOrderMatched = (event) => {
    const filledIds = [];
    if (event.buy_order) filledIds.push(event.buy_order.id);
    if (event.sell_order) filledIds.push(event.sell_order.id);
    if (filledIds.length === 0) return;

    // Remove filled orders from local orderbook
    orders.value = orders.value.filter((o) => !filledIds.includes(o.id));
};

// Expose a hook so parent can push real-time updates
defineExpose({
    handleOrderMatched,
});

onUnmounted(() => {
    if (refreshTimer) {
        clearInterval(refreshTimer);
    }
});
</script>

