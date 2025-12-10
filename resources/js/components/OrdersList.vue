<template>
    <div class="bg-white shadow rounded-lg p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold text-gray-900">My Orders</h2>
            <div class="flex gap-2">
                <!-- Symbol Filter -->
                <select
                    v-model="filters.symbol"
                    @change="applyFilters"
                    class="text-xs rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                    <option value="">All Symbols</option>
                    <option value="BTC">BTC</option>
                    <option value="ETH">ETH</option>
                </select>
                <!-- Status Filter -->
                <select
                    v-model="filters.status"
                    @change="applyFilters"
                    class="text-xs rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                    <option value="">All Status</option>
                    <option value="1">Open</option>
                    <option value="2">Filled</option>
                    <option value="3">Cancelled</option>
                </select>
            </div>
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

        <div v-else>
            <div v-if="orders.length === 0" class="text-center py-8 text-gray-500">
                No orders found
            </div>

            <div v-else class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Symbol
                            </th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Side
                            </th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Price
                            </th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Amount
                            </th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Created
                            </th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Action
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="order in orders" :key="order.id">
                            <td class="px-3 py-2 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ order.symbol }}
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap">
                                <span
                                    :class="[
                                        'px-2 py-1 text-xs font-semibold rounded-full',
                                        order.side === 'buy'
                                            ? 'bg-green-100 text-green-800'
                                            : 'bg-red-100 text-red-800'
                                    ]"
                                >
                                    {{ order.side.toUpperCase() }}
                                </span>
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">
                                ${{ formatCurrency(order.price) }}
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">
                                {{ formatAmount(order.amount) }}
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap">
                                <span
                                    :class="[
                                        'px-2 py-1 text-xs font-semibold rounded-full',
                                        getStatusClass(order.status)
                                    ]"
                                >
                                    {{ getStatusText(order.status) }}
                                </span>
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-500">
                                {{ formatDate(order.created_at) }}
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap text-sm">
                                <button
                                    v-if="order.status === 1"
                                    @click="handleCancel(order.id)"
                                    class="text-red-600 hover:text-red-900"
                                >
                                    Cancel
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import { useOrders } from '../composables/useOrders';

const { orders, loading, fetchOrders, cancelOrder } = useOrders();

const filters = ref({
    symbol: '',
    status: '',
});

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

const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getStatusText = (status) => {
    const statusMap = {
        1: 'Open',
        2: 'Filled',
        3: 'Cancelled',
    };
    return statusMap[status] || 'Unknown';
};

const getStatusClass = (status) => {
    const classMap = {
        1: 'bg-blue-100 text-blue-800',
        2: 'bg-green-100 text-green-800',
        3: 'bg-gray-100 text-gray-800',
    };
    return classMap[status] || 'bg-gray-100 text-gray-800';
};

const applyFilters = () => {
    const filterParams = {};
    if (filters.value.symbol) filterParams.symbol = filters.value.symbol;
    if (filters.value.status) filterParams.status = filters.value.status;
    fetchOrders(filterParams);
};

const handleCancel = async (orderId) => {
    if (confirm('Are you sure you want to cancel this order?')) {
        await cancelOrder(orderId);
        applyFilters(); // Refresh list
    }
};

onMounted(() => {
    fetchOrders();
});

// Watch for order updates (from Pusher events)
watch(orders, () => {
    // Orders are automatically updated via the composable
}, { deep: true });
</script>

