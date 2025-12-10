<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Header -->
        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex justify-between items-center">
                    <h1 class="text-2xl font-bold text-gray-900">Order Exchange</h1>
                    <div class="flex items-center gap-4">
                        <span v-if="user" class="text-sm text-gray-600">
                            {{ user.name }}
                        </span>
                        <button
                            @click="handleLogout"
                            class="text-sm text-gray-600 hover:text-gray-900"
                        >
                            Logout
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column: Order Form -->
                <div class="lg:col-span-1">
                    <OrderForm @order-created="handleOrderCreated" />
                </div>

                <!-- Center Column: Orderbook -->
                <div class="lg:col-span-1">
                    <Orderbook :key="orderbookKey" ref="orderbookRef" />
                </div>

                <!-- Right Column: Wallet & Orders -->
                <div class="lg:col-span-1 space-y-6">
                    <WalletOverview ref="walletRef" />
                    <OrdersList ref="ordersListRef" />
                </div>
            </div>
        </main>

        <!-- Toast Notifications -->
        <ToastContainer />
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useAuth } from '../composables/useAuth';
import { usePusher } from '../composables/usePusher';
import { useOrders } from '../composables/useOrders';
import { useToast } from '../composables/useToast';
import OrderForm from '../components/OrderForm.vue';
import WalletOverview from '../components/WalletOverview.vue';
import OrdersList from '../components/OrdersList.vue';
import Orderbook from '../components/Orderbook.vue';
import ToastContainer from '../components/ToastContainer.vue';

const router = useRouter();
const { user, logout, fetchProfile } = useAuth();
const { listenToOrderMatched, cleanup } = usePusher();
const { fetchOrders, updateOrder } = useOrders();
const toast = useToast();

const orderbookKey = ref(0);
const orderbookRef = ref(null);
const walletRef = ref(null);
const ordersListRef = ref(null);
let unsubscribe = null;

const handleLogout = async () => {
    await logout();
    router.push({ name: 'login' });
};

const handleOrderCreated = () => {
    // Refresh orderbook when new order is created
    orderbookKey.value++;
    // Refresh orders list
    fetchOrders();
    // Refresh profile to update balances
    fetchProfile();
    toast.success('Order placed successfully!');
};

const handleMatchedEvent = async (event) => {
    const isBuyer = user.value?.id === event.buy_order?.user_id;
    const isSeller = user.value?.id === event.sell_order?.user_id;

    // Update order status if it's one of our orders (instant update without refresh)
    if (event.buy_order) {
        updateOrder(event.buy_order.id, { status: event.buy_order.status });
    }
    if (event.sell_order) {
        updateOrder(event.sell_order.id, { status: event.sell_order.status });
    }

    // Update orderbook instantly (remove matched orders)
    orderbookRef.value?.handleOrderMatched?.(event);
    orderbookKey.value++; // Force orderbook refresh

    // Update wallet and orders list instantly (without full page refresh)
    walletRef.value?.refresh?.();
    ordersListRef.value?.refresh?.();

    // Show toast notification
    if (isBuyer || isSeller) {
        const side = isBuyer ? 'Buy' : 'Sell';
        const symbol = event.buy_order?.symbol || event.sell_order?.symbol;
        const amount = event.buy_order?.amount || event.sell_order?.amount;
        const price = event.sell_order?.price || event.buy_order?.price;
        toast.success(
            `${side} order matched! ${amount} ${symbol} @ $${price.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
        );
    }
};

// Subscribe when user is ready
watch(user, (u) => {
    if (unsubscribe) {
        unsubscribe();
        unsubscribe = null;
    }
    if (u?.id) {
        const channel = listenToOrderMatched(u.id, handleMatchedEvent);
        // Track unsubscribe
        unsubscribe = () => {
            channel && channel.stopListening('.order.matched');
        };
    }
}, { immediate: true });

onUnmounted(() => {
    if (unsubscribe) {
        unsubscribe();
    }
    cleanup();
});
</script>

