import { createRouter, createWebHistory } from 'vue-router';

// Lazy load components
const Login = () => import('./pages/Login.vue');
const Register = () => import('./pages/Register.vue');
const Trading = () => import('./pages/Trading.vue');

const routes = [
    {
        path: '/',
        redirect: '/trading',
    },
    {
        path: '/login',
        name: 'login',
        component: Login,
        meta: { requiresGuest: true },
    },
    {
        path: '/register',
        name: 'register',
        component: Register,
        meta: { requiresGuest: true },
    },
    {
        path: '/trading',
        name: 'trading',
        component: Trading,
        meta: { requiresAuth: true },
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

// Navigation guard for authentication
router.beforeEach((to, from, next) => {
    const token = localStorage.getItem('auth_token');
    const isAuthenticated = !!token;

    if (to.meta.requiresAuth && !isAuthenticated) {
        next({ name: 'login' });
    } else if (to.meta.requiresGuest && isAuthenticated) {
        next({ name: 'trading' });
    } else {
        next();
    }
});

export default router;

