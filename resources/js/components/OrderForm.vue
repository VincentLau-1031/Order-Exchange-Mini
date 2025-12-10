<template>
    <div class="bg-white shadow rounded-lg p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Place Order</h2>

        <form @submit.prevent="handleSubmit" class="space-y-4">
            <!-- Error Message -->
            <div v-if="error" class="rounded-md bg-red-50 p-3">
                <p class="text-sm text-red-800">{{ error }}</p>
            </div>

            <!-- Success Message -->
            <div v-if="successMessage" class="rounded-md bg-green-50 p-3">
                <p class="text-sm text-green-800">{{ successMessage }}</p>
            </div>

            <!-- Symbol Dropdown -->
            <div>
                <label for="symbol" class="block text-sm font-medium text-gray-700 mb-1">
                    Symbol
                </label>
                <select
                    id="symbol"
                    v-model="form.symbol"
                    required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                >
                    <option value="">Select Symbol</option>
                    <option value="BTC">BTC</option>
                    <option value="ETH">ETH</option>
                </select>
            </div>

            <!-- Side Toggle (Buy/Sell) -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Side
                </label>
                <div class="flex rounded-md shadow-sm" role="group">
                    <button
                        type="button"
                        :class="[
                            'flex-1 px-4 py-2 text-sm font-medium border rounded-l-lg focus:z-10 focus:ring-2 focus:ring-indigo-500',
                            form.side === 'buy'
                                ? 'bg-green-600 text-white border-green-600'
                                : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
                        ]"
                        @click="form.side = 'buy'"
                    >
                        Buy
                    </button>
                    <button
                        type="button"
                        :class="[
                            'flex-1 px-4 py-2 text-sm font-medium border rounded-r-lg focus:z-10 focus:ring-2 focus:ring-indigo-500',
                            form.side === 'sell'
                                ? 'bg-red-600 text-white border-red-600'
                                : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
                        ]"
                        @click="form.side = 'sell'"
                    >
                        Sell
                    </button>
                </div>
            </div>

            <!-- Price Input -->
            <div>
                <label for="price" class="block text-sm font-medium text-gray-700 mb-1">
                    Price (USD)
                </label>
                <input
                    id="price"
                    v-model.number="form.price"
                    type="number"
                    step="0.01"
                    min="0.01"
                    required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    placeholder="0.00"
                />
            </div>

            <!-- Amount Input -->
            <div>
                <label for="amount" class="block text-sm font-medium text-gray-700 mb-1">
                    Amount
                </label>
                <input
                    id="amount"
                    v-model.number="form.amount"
                    type="number"
                    step="0.00000001"
                    min="0.00000001"
                    required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    placeholder="0.00000000"
                />
            </div>

            <!-- Order Summary -->
            <div v-if="form.price && form.amount" class="bg-gray-50 rounded-md p-3 space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Total Value:</span>
                    <span class="font-medium text-gray-900">
                        ${{ totalValue.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                    </span>
                </div>
                <div class="flex justify-between text-xs text-gray-500 pt-1 border-t border-gray-200">
                    <span>Volume:</span>
                    <span>{{ form.amount }} {{ form.symbol || '' }}</span>
                </div>
                <div v-if="form.side === 'buy'" class="flex justify-between text-xs text-gray-500">
                    <span>Commission (1.5%):</span>
                    <span class="text-orange-600">
                        ${{ commission.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                    </span>
                </div>
            </div>

            <!-- Submit Button -->
            <button
                type="submit"
                :disabled="loading || !isFormValid"
                class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <span v-if="loading" class="flex items-center">
                    <svg
                        class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
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
                    Placing Order...
                </span>
                <span v-else>Place Order</span>
            </button>
        </form>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useOrders } from '../composables/useOrders';
import { useToast } from '../composables/useToast';

const emit = defineEmits(['order-created']);

const { createOrder, loading, error: orderError } = useOrders();
const toast = useToast();

const form = ref({
    symbol: '',
    side: 'buy',
    price: null,
    amount: null,
});

const error = ref(null);
const successMessage = ref(null);

const totalValue = computed(() => {
    if (!form.value.price || !form.value.amount) {
        return 0;
    }
    return form.value.price * form.value.amount;
});

const commission = computed(() => {
    if (!form.value.price || !form.value.amount || form.value.side !== 'buy') {
        return 0;
    }
    return totalValue.value * 0.015;
});

const isFormValid = computed(() => {
    return (
        form.value.symbol &&
        form.value.side &&
        form.value.price > 0 &&
        form.value.amount > 0
    );
});

const handleSubmit = async () => {
    error.value = null;
    successMessage.value = null;

    if (!isFormValid.value) {
        error.value = 'Please fill in all fields correctly';
        return;
    }

    try {
        const result = await createOrder({
            symbol: form.value.symbol,
            side: form.value.side,
            price: parseFloat(form.value.price),
            amount: parseFloat(form.value.amount),
        });

        if (result.success) {
            successMessage.value = `Order placed successfully!`;
            toast.success('Order placed successfully!');
            // Emit event for parent component
            emit('order-created', result.data);
            // Reset form
            form.value = {
                symbol: '',
                side: 'buy',
                price: null,
                amount: null,
            };
            // Clear success message after 3 seconds
            setTimeout(() => {
                successMessage.value = null;
            }, 3000);
        } else {
            error.value = result.error || 'Failed to place order';
            toast.error(result.error || 'Failed to place order');
        }
    } catch (err) {
        error.value = 'An unexpected error occurred';
    }
};

// Watch for errors from useOrders composable
watch(orderError, (newError) => {
    if (newError) {
        error.value = newError;
    }
});
</script>

