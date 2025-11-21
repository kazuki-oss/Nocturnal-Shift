<template>
  <v-app>
    <v-navigation-drawer v-model="drawer" app>
      <v-list>
        <v-list-item title="Nocturnal Shift Admin" subtitle="Admin Portal"></v-list-item>
        <v-divider></v-divider>
        <v-list-item
          v-for="item in items"
          :key="item.title"
          :href="item.href"
          link
        >
            <template v-slot:prepend>
                <v-icon :icon="item.icon"></v-icon>
            </template>
            <v-list-item-title>{{ item.title }}</v-list-item-title>
        </v-list-item>
      </v-list>
    </v-navigation-drawer>

    <v-app-bar app color="primary" dark>
      <v-app-bar-nav-icon @click="drawer = !drawer"></v-app-bar-nav-icon>
      <v-toolbar-title>{{ title }}</v-toolbar-title>
      <v-spacer></v-spacer>
      <v-btn icon href="/admin/logout">
        <v-icon>mdi-logout</v-icon>
      </v-btn>
    </v-app-bar>

    <v-main>
      <v-container fluid>
        <slot></slot>
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
    title: {
        type: String,
        default: 'Dashboard'
    }
});

const drawer = ref(true);
const items = ref([
  { title: 'Dashboard', icon: 'mdi-view-dashboard', href: '/admin/dashboard' },
  { title: 'Schedule', icon: 'mdi-calendar', href: '/admin/schedule' },
  { title: 'Employees', icon: 'mdi-account-group', href: '/admin/employees' },
  { title: 'Settings', icon: 'mdi-cog', href: '/admin/settings' },
]);
</script>
