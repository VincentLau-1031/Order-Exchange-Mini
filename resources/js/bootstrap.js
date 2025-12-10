import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const token = localStorage.getItem('auth_token');
if (token) {
    window.axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
}

window.axios.interceptors.request.use((config) => {
    const token = localStorage.getItem('auth_token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

const pusherKey = import.meta.env.VITE_PUSHER_APP_KEY;
const pusherCluster = import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1';
const pusherHost = import.meta.env.VITE_PUSHER_HOST;
const pusherPort = import.meta.env.VITE_PUSHER_PORT;
const pusherScheme = import.meta.env.VITE_PUSHER_SCHEME ?? 'https';

const wsHost = pusherHost 
    ? pusherHost.replace(/^https?:\/\//, '').replace(/^wss?:\/\//, '')
    : `ws-${pusherCluster}.pusher.com`;

const getAuthToken = () => {
    return localStorage.getItem('auth_token') || '';
};

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: pusherKey,
    cluster: pusherCluster,
    wsHost: wsHost,
    wsPort: pusherPort ? parseInt(pusherPort) : (pusherScheme === 'https' ? 443 : 80),
    wssPort: pusherPort ? parseInt(pusherPort) : 443,
    forceTLS: pusherScheme === 'https',
    enabledTransports: ['ws', 'wss'],
    authEndpoint: '/api/broadcasting/auth',
    authorizer: (channel, options) => {
        return {
            authorize: (socketId, callback) => {
                const token = getAuthToken();
                if (!token) {
                    callback(new Error('No authentication token available'), false);
                    return;
                }

                window.axios.post('/api/broadcasting/auth', {
                    socket_id: socketId,
                    channel_name: channel.name,
                }, {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'X-CSRF-TOKEN': csrfToken || '',
                    },
                })
                .then(response => {
                    callback(null, response.data);
                })
                .catch(error => {
                    callback(error, false);
                });
            },
        };
    },
});
