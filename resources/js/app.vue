<template>
    <div id="app" class="min-h-screen bg-gray-50">
        <router-view />
    </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useAuth } from './composables/useAuth';

const { checkAuth } = useAuth();

// Check authentication on app mount
onMounted(async () => {
    await checkAuth();
});
</script>

<style>
/* Global styles if needed */
</style>

