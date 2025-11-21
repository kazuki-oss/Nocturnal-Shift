<template>
  <v-app>
    <v-app-bar app color="secondary" dark>
      <v-toolbar-title>Nocturnal Shift</v-toolbar-title>
      <v-spacer></v-spacer>
      <v-btn icon href="/employee/logout">
        <v-icon>mdi-logout</v-icon>
      </v-btn>
    </v-app-bar>

    <v-main>
      <v-container fluid>
        <slot></slot>
      </v-container>
    </v-main>

    <v-bottom-navigation grow color="secondary">
      <v-btn href="/employee/dashboard">
        <v-icon>mdi-view-dashboard</v-icon>
        <span>Home</span>
      </v-btn>

      <v-btn href="/employee/shifts">
        <v-icon>mdi-calendar-clock</v-icon>
        <span>Shifts</span>
      </v-btn>

      <v-btn href="/employee/requests">
        <v-icon>mdi-file-document-edit</v-icon>
        <span>Requests</span>
      </v-btn>
    </v-bottom-navigation>
  </v-app>
</template>

<script setup>
// Employee layout logic
</script>
