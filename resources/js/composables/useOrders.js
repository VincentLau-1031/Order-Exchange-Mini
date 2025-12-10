import { ref } from 'vue';
import api from '../services/api';

/**
 * Orders composable
 */
export function useOrders() {
    const orders = ref([]);
    const loading = ref(false);
    const error = ref(null);

    /**
     * Fetch orders with optional filters
     */
    const fetchOrders = async (filters = {}) => {
        loading.value = true;
        error.value = null;

        try {
            const params = new URLSearchParams();
            if (filters.symbol) params.append('symbol', filters.symbol);
            if (filters.status) params.append('status', filters.status);
            if (filters.side) params.append('side', filters.side);

            const response = await api.get(`/orders?${params.toString()}`);
            orders.value = response.data;
            return { success: true, data: response.data };
        } catch (err) {
            error.value = err.response?.data?.message || 'Failed to fetch orders';
            return { success: false, error: error.value };
        } finally {
            loading.value = false;
        }
    };

    /**
     * Create a new order
     */
    const createOrder = async (orderData) => {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.post('/orders', orderData);
            // Add new order to the list
            orders.value.unshift(response.data);
            return { success: true, data: response.data };
        } catch (err) {
            error.value = err.response?.data?.message || 'Failed to create order';
            return { success: false, error: error.value };
        } finally {
            loading.value = false;
        }
    };

    /**
     * Cancel an order
     */
    const cancelOrder = async (orderId) => {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.post(`/orders/${orderId}/cancel`);
            // Update order in the list
            const index = orders.value.findIndex(o => o.id === orderId);
            if (index !== -1) {
                orders.value[index] = response.data;
            }
            return { success: true, data: response.data };
        } catch (err) {
            error.value = err.response?.data?.message || 'Failed to cancel order';
            return { success: false, error: error.value };
        } finally {
            loading.value = false;
        }
    };

    /**
     * Update order in local state (for real-time updates)
     */
    const updateOrder = (orderId, updates) => {
        const index = orders.value.findIndex(o => o.id === orderId);
        if (index !== -1) {
            orders.value[index] = { ...orders.value[index], ...updates };
        }
    };

    /**
     * Add order to local state (for real-time updates)
     */
    const addOrder = (order) => {
        orders.value.unshift(order);
    };

    return {
        orders,
        loading,
        error,
        fetchOrders,
        createOrder,
        cancelOrder,
        updateOrder,
        addOrder,
    };
}

