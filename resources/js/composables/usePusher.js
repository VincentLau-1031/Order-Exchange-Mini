import { ref, onMounted, onUnmounted } from 'vue';

/**
 * Pusher real-time composable
 */
export function usePusher() {
    const isConnected = ref(false);
    const channels = ref([]);

    /**
     * Subscribe to a private channel
     */
    const subscribe = (channelName, eventName, callback) => {
        if (!window.Echo) {
            console.error('Echo is not initialized');
            return null;
        }

        const channel = window.Echo.private(channelName);
        
        channel.listen(eventName, callback);

        channels.value.push({ name: channelName, channel });

        return channel;
    };

    /**
     * Unsubscribe from a channel
     */
    const unsubscribe = (channelName) => {
        const index = channels.value.findIndex(c => c.name === channelName);
        if (index !== -1) {
            const { channel } = channels.value[index];
            window.Echo?.leave(channelName);
            channels.value.splice(index, 1);
        }
    };

    /**
     * Listen for order matched events
     */
    const listenToOrderMatched = (userId, callback) => {
        return subscribe(`user.${userId}`, '.order.matched', callback);
    };

    /**
     * Cleanup all subscriptions
     */
    const cleanup = () => {
        channels.value.forEach(({ name }) => {
            window.Echo?.leave(name);
        });
        channels.value = [];
    };

    // Cleanup on unmount
    onUnmounted(() => {
        cleanup();
    });

    return {
        isConnected,
        subscribe,
        unsubscribe,
        listenToOrderMatched,
        cleanup,
    };
}

