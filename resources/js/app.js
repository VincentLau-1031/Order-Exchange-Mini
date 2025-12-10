import './bootstrap';
import { createApp } from 'vue';

const app = createApp({});

const mountElement = document.getElementById('app');
if (mountElement) {
    app.mount('#app');
}
