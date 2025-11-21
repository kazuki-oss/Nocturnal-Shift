import { registerVueControllerComponents } from 'symfony-ux-vue';
import { createApp } from 'vue';
import { createVuetify } from 'vuetify';
import * as components from 'vuetify/components';
import * as directives from 'vuetify/directives';
import 'vuetify/styles';
import '@mdi/font/css/materialdesignicons.css';
import './styles/app.scss';

// Import Layouts
import AdminLayout from './components/admin/AdminLayout.vue';
import EmployeeLayout from './components/employee/EmployeeLayout.vue';
import AppRouter from './components/AppRouter.vue';

const vuetify = createVuetify({
    components,
    directives,
});

const app = createApp({
    components: {
        AdminLayout,
        EmployeeLayout,
        AppRouter
    }
});

app.use(vuetify);
app.mount('#app');
