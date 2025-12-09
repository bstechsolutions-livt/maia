<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 40 40"
        :class="className"
        v-bind="$attrs"
    >
        <!-- Logo Maia - M estilizado com curvas modernas -->
        <path
            fill="currentColor"
            d="M4 32V12c0-1.1.9-2 2-2h2c.7 0 1.4.4 1.7 1l10.3 16 10.3-16c.3-.6 1-1 1.7-1h2c1.1 0 2 .9 2 2v20c0 1.1-.9 2-2 2h-2c-1.1 0-2-.9-2-2V20l-7.3 11.4c-.6 1-2.1 1-2.8 0L12 20v12c0 1.1-.9 2-2 2H8c-1.1 0-2-.9-2-2z"
        />
    </svg>
</template>
