import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Configure axios to include auth token in requests
const token = localStorage.getItem('auth_token');
if (token) {
    window.axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
}

// Add axios interceptor to update token when it changes
window.axios.interceptors.request.use((config) => {
    const token = localStorage.getItem('auth_token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

// Laravel Echo and Pusher setup
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

// Get CSRF token from meta tag
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

// Get Pusher configuration from environment variables
const pusherKey = import.meta.env.VITE_PUSHER_APP_KEY;
const pusherCluster = import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1';
const pusherHost = import.meta.env.VITE_PUSHER_HOST;
const pusherPort = import.meta.env.VITE_PUSHER_PORT;
const pusherScheme = import.meta.env.VITE_PUSHER_SCHEME ?? 'https';

// Build WebSocket host - use custom host if provided, otherwise use cluster-based default
const wsHost = pusherHost 
    ? pusherHost.replace(/^https?:\/\//, '').replace(/^wss?:\/\//, '')
    : `ws-${pusherCluster}.pusher.com`;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: pusherKey,
    cluster: pusherCluster,
    wsHost: wsHost,
    wsPort: pusherPort ? parseInt(pusherPort) : (pusherScheme === 'https' ? 443 : 80),
    wssPort: pusherPort ? parseInt(pusherPort) : 443,
    forceTLS: pusherScheme === 'https',
    enabledTransports: ['ws', 'wss'],
    authEndpoint: '/broadcasting/auth',
    auth: {
        headers: {
            'X-CSRF-TOKEN': csrfToken || '',
            'Authorization': `Bearer ${localStorage.getItem('auth_token') || ''}`,
        },
    },
});
