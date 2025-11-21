<template>
    <div class="login-container fill-height">
        <div class="background-animation"></div>
        
        <v-container class="fill-height justify-center align-center" fluid>
            <v-row justify="center">
                <v-col cols="12" sm="8" md="6" lg="4">
                    <v-card class="login-card elevation-24" :class="themeClass">
                        <div class="card-header text-center pt-8 pb-4">
                            <v-icon size="64" :color="accentColor" class="mb-4 icon-glow">mdi-moon-waning-crescent</v-icon>
                            <h1 class="text-h4 font-weight-bold mb-1 text-white tracking-wide">
                                {{ title }}
                            </h1>
                            <div class="text-subtitle-1 text-medium-emphasis text-white opacity-70">
                                {{ tenantName }}
                            </div>
                        </div>

                        <v-card-text class="px-8 pb-8">
                            <v-alert
                                v-if="error"
                                type="error"
                                variant="tonal"
                                class="mb-6 border-error"
                                icon="mdi-alert-circle"
                            >
                                {{ error }}
                            </v-alert>

                            <form method="post" :action="actionUrl">
                                <v-text-field
                                    label="Email Address"
                                    name="email"
                                    :model-value="lastUsername"
                                    prepend-inner-icon="mdi-email-outline"
                                    variant="outlined"
                                    color="white"
                                    bg-color="rgba(255,255,255,0.05)"
                                    class="mb-2 input-field"
                                    theme="dark"
                                ></v-text-field>

                                <v-text-field
                                    label="Password"
                                    name="password"
                                    type="password"
                                    prepend-inner-icon="mdi-lock-outline"
                                    variant="outlined"
                                    color="white"
                                    bg-color="rgba(255,255,255,0.05)"
                                    class="mb-6 input-field"
                                    theme="dark"
                                ></v-text-field>

                                <input type="hidden" name="_csrf_token" :value="csrfToken">

                                <div class="d-flex justify-space-between align-center mb-6">
                                    <v-checkbox
                                        label="Remember me"
                                        name="_remember_me"
                                        hide-details
                                        density="compact"
                                        color="white"
                                        theme="dark"
                                        class="remember-checkbox"
                                    ></v-checkbox>
                                    <a href="#" class="text-caption text-white text-decoration-none opacity-70 hover-glow">Forgot password?</a>
                                </div>

                                <v-btn
                                    type="submit"
                                    block
                                    size="x-large"
                                    :color="accentColor"
                                    class="login-btn text-white font-weight-bold elevation-8"
                                >
                                    Sign In
                                    <v-icon end icon="mdi-arrow-right" class="ml-2"></v-icon>
                                </v-btn>
                            </form>
                        </v-card-text>
                    </v-card>
                </v-col>
            </v-row>
        </v-container>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    title: String,
    tenantName: String,
    lastUsername: String,
    error: String,
    csrfToken: String,
    type: {
        type: String,
        default: 'admin' // 'admin' or 'employee'
    }
});

const accentColor = computed(() => props.type === 'admin' ? 'deep-purple-accent-2' : 'cyan-accent-2');
const themeClass = computed(() => props.type === 'admin' ? 'theme-admin' : 'theme-employee');
const actionUrl = computed(() => props.type === 'admin' ? '/admin/login' : '/employee/login');

</script>

<style scoped lang="scss">
.login-container {
    position: relative;
    background-color: #0f172a;
    overflow: hidden;
    min-height: 100vh;
}

.background-animation {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: radial-gradient(circle at 50% 0%, #1e1b4b 0%, #0f172a 60%);
    z-index: 0;
    
    &::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle at center, rgba(99, 102, 241, 0.15) 0%, transparent 50%);
        animation: rotate 20s linear infinite;
    }
}

@keyframes rotate {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.login-card {
    background: rgba(30, 41, 59, 0.7) !important;
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 24px !important;
    position: relative;
    z-index: 1;
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;

    &:hover {
        transform: translateY(-5px);
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5) !important;
    }
}

.icon-glow {
    filter: drop-shadow(0 0 10px currentColor);
}

.tracking-wide {
    letter-spacing: 0.05em;
}

.border-error {
    border: 1px solid rgba(239, 68, 68, 0.5);
    background: rgba(239, 68, 68, 0.1) !important;
    color: #fca5a5 !important;
}

.input-field :deep(.v-field__outline__start),
.input-field :deep(.v-field__outline__end),
.input-field :deep(.v-field__outline__notch) {
    border-color: rgba(255, 255, 255, 0.2) !important;
}

.input-field :deep(.v-field--focused .v-field__outline__start),
.input-field :deep(.v-field--focused .v-field__outline__end),
.input-field :deep(.v-field--focused .v-field__outline__notch) {
    border-color: currentColor !important;
}

.login-btn {
    background: linear-gradient(135deg, rgba(255,255,255,0.1), rgba(255,255,255,0)) !important;
    border: 1px solid rgba(255,255,255,0.2);
    text-transform: none;
    letter-spacing: 1px;
    transition: all 0.3s ease;

    &:hover {
        filter: brightness(1.2);
        box-shadow: 0 0 20px currentColor;
    }
}

.hover-glow:hover {
    text-shadow: 0 0 8px rgba(255,255,255,0.5);
    opacity: 1 !important;
}

/* Theme Specifics */
.theme-admin {
    border-top: 4px solid #7c4dff;
}

.theme-employee {
    border-top: 4px solid #18ffff;
}
</style>
