import { ref, computed } from 'vue';
import api from '../services/api';

const user = ref(null);
const isAuthenticated = computed(() => !!user.value);

/**
 * Authentication composable
 */
export function useAuth() {
    /**
     * Login user
     */
    const login = async (email, password) => {
        try {
            const response = await api.post('/login', {
                email,
                password,
            });

            const { token, user: userData } = response.data;

            localStorage.setItem('auth_token', token);
            
            user.value = userData;

            api.defaults.headers.common['Authorization'] = `Bearer ${token}`;

            return { success: true, user: userData };
        } catch (error) {
            return {
                success: false,
                error: error.response?.data?.message || 'Login failed',
            };
        }
    };

    /**
     * Register user
     */
    const register = async (name, email, password, password_confirmation) => {
        try {
            const response = await api.post('/register', {
                name,
                email,
                password,
                password_confirmation,
            });

            const { token, user: userData } = response.data;

            localStorage.setItem('auth_token', token);
            
            user.value = userData;

            api.defaults.headers.common['Authorization'] = `Bearer ${token}`;

            return { success: true, user: userData };
        } catch (error) {
            return {
                success: false,
                error: error.response?.data?.message || 'Registration failed',
            };
        }
    };

    /**
     * Logout user
     */
    const logout = async () => {
        try {
            await api.post('/logout');
        } catch (error) {
            console.error('Logout error:', error);
        } finally {
            localStorage.removeItem('auth_token');
            user.value = null;
            delete api.defaults.headers.common['Authorization'];
        }
    };

    /**
     * Get current user profile
     */
    const fetchProfile = async () => {
        try {
            const response = await api.get('/profile');
            user.value = response.data;
            return { success: true, user: response.data };
        } catch (error) {
            return {
                success: false,
                error: error.response?.data?.message || 'Failed to fetch profile',
            };
        }
    };

    /**
     * Check if user is authenticated and fetch profile
     */
    const checkAuth = async () => {
        const token = localStorage.getItem('auth_token');
        if (token) {
            api.defaults.headers.common['Authorization'] = `Bearer ${token}`;
            await fetchProfile();
        }
    };

    return {
        user,
        isAuthenticated,
        login,
        register,
        logout,
        fetchProfile,
        checkAuth,
    };
}

