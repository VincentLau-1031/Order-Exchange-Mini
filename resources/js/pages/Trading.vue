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
                    <WalletOverview />
                    <OrdersList />
                </div>
            </div>
        </main>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useAuth } from '../composables/useAuth';
import { usePusher } from '../composables/usePusher';
import { useOrders } from '../composables/useOrders';
import OrderForm from '../components/OrderForm.vue';
import WalletOverview from '../components/WalletOverview.vue';
import OrdersList from '../components/OrdersList.vue';
import Orderbook from '../components/Orderbook.vue';

const router = useRouter();
const { user, logout, fetchProfile } = useAuth();
const { listenToOrderMatched, cleanup } = usePusher();
const { fetchOrders, updateOrder } = useOrders();

const orderbookKey = ref(0);
const orderbookRef = ref(null);
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
};

const handleMatchedEvent = (event) => {
    // Update order status if it's one of our orders
    if (event.buy_order) {
        updateOrder(event.buy_order.id, { status: event.buy_order.status });
    }
    if (event.sell_order) {
        updateOrder(event.sell_order.id, { status: event.sell_order.status });
    }

    // Refresh data
    fetchProfile();
    fetchOrders();
    orderbookKey.value++; // Refresh orderbook

    // Push real-time removal to orderbook component
    orderbookRef.value?.handleOrderMatched?.(event);
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

