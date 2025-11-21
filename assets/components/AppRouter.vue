<template>
    <component :is="layoutComponent">
        <component :is="pageComponent" v-bind="pageProps" />
    </component>
</template>

<script setup>
import { computed, defineAsyncComponent } from 'vue';

const props = defineProps({
    layout: {
        type: String,
        default: 'default'
    },
    component: {
        type: String,
        required: true
    },
    props: {
        type: Object,
        default: () => ({})
    }
});

const layoutComponent = computed(() => {
    switch (props.layout) {
        case 'admin':
            return 'AdminLayout';
        case 'employee':
            return 'EmployeeLayout';
        default:
            return 'div'; // Fallback
    }
});

// Dynamic import of page components
// Note: Vite requires explicit paths or glob imports for dynamic imports to work reliably
const pageComponent = computed(() => {
    // We assume components are located in assets/components/pages/
    // and the passed 'component' prop is the relative path, e.g., 'admin/Dashboard'
    return defineAsyncComponent(() => import(`../../components/pages/${props.component}.vue`));
});

const pageProps = computed(() => props.props);
</script>
