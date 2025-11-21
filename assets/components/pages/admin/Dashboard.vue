<template>
    <div>
        <h1 class="text-h4 mb-4">Dashboard</h1>
        
        <v-row>
            <v-col cols="12" md="4">
                <v-card color="primary" dark>
                    <v-card-title>Employees</v-card-title>
                    <v-card-text class="text-h2">
                        {{ stats.employees }}
                    </v-card-text>
                    <v-card-actions>
                        <v-btn variant="text" href="/admin/employees">Manage</v-btn>
                    </v-card-actions>
                </v-card>
            </v-col>

            <v-col cols="12" md="4">
                <v-card color="success" dark>
                    <v-card-title>Shifts Today</v-card-title>
                    <v-card-text class="text-h2">
                        {{ stats.shifts_today }}
                    </v-card-text>
                    <v-card-actions>
                        <v-btn variant="text" href="/admin/schedule">View Schedule</v-btn>
                    </v-card-actions>
                </v-card>
            </v-col>

            <v-col cols="12" md="4">
                <v-card color="warning" dark>
                    <v-card-title>Pending Requests</v-card-title>
                    <v-card-text class="text-h2">
                        {{ stats.pending_requests }}
                    </v-card-text>
                    <v-card-actions>
                        <v-btn variant="text" href="/admin/requests">Review</v-btn>
                    </v-card-actions>
                </v-card>
            </v-col>
        </v-row>

        <v-row class="mt-6">
            <v-col cols="12">
                <v-card>
                    <v-card-title>Recent Activity</v-card-title>
                    <v-card-text>
                        <v-list>
                            <v-list-item title="John Doe submitted a shift request" subtitle="2 hours ago"></v-list-item>
                            <v-divider></v-divider>
                            <v-list-item title="Schedule published for next week" subtitle="5 hours ago"></v-list-item>
                        </v-list>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
    </div>
</template>

<script setup>
defineProps({
    stats: {
        type: Object,
        required: true
    }
});
</script>
