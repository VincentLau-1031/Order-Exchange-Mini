import { ref } from 'vue';

const toasts = ref([]);

/**
 * Toast notification composable
 */
export function useToast() {
    /**
     * Show a toast notification
     */
    const showToast = (message, type = 'info', duration = 3000) => {
        const id = Date.now() + Math.random();
        const toast = {
            id,
            message,
            type, // 'success', 'error', 'info', 'warning'
            duration,
        };

        toasts.value.push(toast);

        // Auto remove after duration
        if (duration > 0) {
            setTimeout(() => {
                removeToast(id);
            }, duration);
        }

        return id;
    };

    /**
     * Remove a toast
     */
    const removeToast = (id) => {
        const index = toasts.value.findIndex(t => t.id === id);
        if (index !== -1) {
            toasts.value.splice(index, 1);
        }
    };

    /**
     * Helper methods for different toast types
     */
    const success = (message, duration) => showToast(message, 'success', duration);
    const error = (message, duration) => showToast(message, 'error', duration);
    const info = (message, duration) => showToast(message, 'info', duration);
    const warning = (message, duration) => showToast(message, 'warning', duration);

    return {
        toasts,
        showToast,
        removeToast,
        success,
        error,
        info,
        warning,
    };
}

