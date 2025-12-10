<template>
    <div class="bg-white shadow rounded-lg p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Wallet</h2>

        <div v-if="loading" class="flex justify-center py-8">
            <svg
                class="animate-spin h-8 w-8 text-indigo-600"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
            >
                <circle
                    class="opacity-25"
                    cx="12"
                    cy="12"
                    r="10"
                    stroke="currentColor"
                    stroke-width="4"
                ></circle>
                <path
                    class="opacity-75"
                    fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                ></path>
            </svg>
        </div>

        <div v-else class="space-y-4">
            <!-- USD Balance -->
            <div class="border-b pb-4">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-sm font-medium text-gray-600">USD Balance</span>
                    <span class="text-2xl font-bold text-gray-900">
                        ${{ formatCurrency(profile?.balance || 0) }}
                    </span>
                </div>
                <button
                    @click="addTestBalance"
                    :disabled="addingBalance"
                    class="w-full mt-2 px-3 py-1.5 text-xs font-medium text-indigo-600 bg-indigo-50 rounded-md hover:bg-indigo-100 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                >
                    {{ addingBalance ? 'Adding...' : '+ Add $1,000 (Test)' }}
                </button>
            </div>

            <!-- Asset Balances -->
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <h3 class="text-sm font-medium text-gray-700">Assets</h3>
                    <div class="flex gap-1">
                        <button
                            @click="addTestAsset('BTC')"
                            :disabled="addingAsset"
                            class="px-2 py-1 text-xs font-medium text-indigo-600 bg-indigo-50 rounded hover:bg-indigo-100 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                            title="Add 1 BTC (Test)"
                        >
                            +BTC
                        </button>
                        <button
                            @click="addTestAsset('ETH')"
                            :disabled="addingAsset"
                            class="px-2 py-1 text-xs font-medium text-indigo-600 bg-indigo-50 rounded hover:bg-indigo-100 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                            title="Add 1 ETH (Test)"
                        >
                            +ETH
                        </button>
                    </div>
                </div>
                
                <div
                    v-for="asset in assets"
                    :key="asset.symbol"
                    class="border rounded-lg p-3"
                >
                    <div class="flex justify-between items-start mb-2">
                        <span class="text-sm font-semibold text-gray-900">
                            {{ asset.symbol }}
                        </span>
                        <span class="text-sm font-medium text-gray-900">
                            {{ formatAmount(asset.amount) }}
                        </span>
                    </div>
                    <div class="flex justify-between text-xs text-gray-500">
                        <span>Available: {{ formatAmount(getAvailableAmount(asset)) }}</span>
                        <span v-if="asset.locked_amount > 0" class="text-orange-600">
                            Locked: {{ formatAmount(asset.locked_amount) }}
                        </span>
                    </div>
                </div>

                <div v-if="assets.length === 0" class="text-sm text-gray-500 text-center py-4">
                    No assets yet
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import { useAuth } from '../composables/useAuth';
import { useToast } from '../composables/useToast';
import api from '../services/api';

const { user, fetchProfile } = useAuth();
const toast = useToast();
const loading = ref(false);
const addingBalance = ref(false);
const addingAsset = ref(false);
const profile = ref(null);
const assets = ref([]);

const formatCurrency = (value) => {
    return parseFloat(value || 0).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
};

const formatAmount = (value) => {
    return parseFloat(value || 0).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 8,
    });
};

const getAvailableAmount = (asset) => {
    if (!asset) return 0;
    return Math.max(0, parseFloat(asset.amount || 0) - parseFloat(asset.locked_amount || 0));
};

const loadProfile = async () => {
    loading.value = true;
    try {
        const result = await fetchProfile();
        if (result.success) {
            profile.value = result.user;
            // Extract assets from profile
            assets.value = result.user.assets || [];
        }
    } catch (error) {
        console.error('Failed to load profile:', error);
    } finally {
        loading.value = false;
    }
};

const addTestBalance = async () => {
    addingBalance.value = true;
    try {
        const response = await api.post('/profile/add-balance', {
            amount: 1000,
        });
        if (response.data.user) {
            profile.value = response.data.user;
            assets.value = response.data.user.assets || [];
            toast.success('$1,000 added to your account');
        }
    } catch (error) {
        console.error('Failed to add balance:', error);
        toast.error('Failed to add balance. Please try again.');
    } finally {
        addingBalance.value = false;
    }
};

const addTestAsset = async (symbol) => {
    addingAsset.value = true;
    try {
        const response = await api.post('/profile/add-asset', {
            symbol: symbol,
            amount: 1, // Add 1 BTC or 1 ETH for testing
        });
        if (response.data.user) {
            profile.value = response.data.user;
            assets.value = response.data.user.assets || [];
            toast.success(`1 ${symbol} added to your account`);
        }
    } catch (error) {
        console.error('Failed to add asset:', error);
        toast.error(`Failed to add ${symbol}. Please try again.`);
    } finally {
        addingAsset.value = false;
    }
};

// Watch for user changes (from Pusher events or auth updates)
watch(user, (newUser) => {
    if (newUser) {
        profile.value = newUser;
        assets.value = newUser.assets || [];
    }
}, { deep: true, immediate: true });

// Expose refresh method for parent components
const refresh = () => {
    loadProfile();
};

defineExpose({
    refresh,
});

onMounted(() => {
    loadProfile();
});
</script>

