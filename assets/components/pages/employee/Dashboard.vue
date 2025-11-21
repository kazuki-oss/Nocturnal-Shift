<template>
    <div>
        <h1 class="text-h5 mb-4">Welcome back!</h1>
        
        <v-card class="mb-4" color="primary" dark>
            <v-card-title>Next Shift</v-card-title>
            <v-card-text>
                <div class="text-h4">{{ next_shift.date }}</div>
                <div class="text-h5">{{ next_shift.time }}</div>
                <div class="text-subtitle-1">{{ next_shift.role }}</div>
            </v-card-text>
        </v-card>

        <v-row>
            <v-col cols="6">
                <v-card>
                    <v-card-subtitle class="pt-4">Hours (Nov)</v-card-subtitle>
                    <v-card-text class="text-h4">
                        {{ hours_this_month }}
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="6">
                <v-card>
                    <v-card-subtitle class="pt-4">Est. Earnings</v-card-subtitle>
                    <v-card-text class="text-h5">
                        ¥{{ estimated_earnings.toLocaleString() }}
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>

        <v-btn block color="secondary" size="x-large" class="mt-6" href="/employee/shifts/submit">
            Submit Shift Request
        </v-btn>
    </div>
</template>

<script setup>
defineProps({
    next_shift: Object,
    hours_this_month: Number,
    estimated_earnings: Number
});
</script>
