import './bootstrap';
import { createApp } from 'vue';
import App from './app.vue';
import router from './router';

const app = createApp(App);

app.use(router);

const mountElement = document.getElementById('app');
if (mountElement) {
    app.mount('#app');
}
