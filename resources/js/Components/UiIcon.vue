<script setup>
const props = defineProps({
    name: {
        type: String,
        required: true,
    },
    class: {
        type: String,
        default: 'h-6 w-6',
    },
});
</script>

<template>
    <!-- Dashboard / grille -->
    <svg
        v-if="name === 'dashboard'"
        :class="props.class"
        fill="none"
        viewBox="0 0 24 24"
        stroke-width="1.5"
        stroke="currentColor"
    >
        <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"
        />
    </svg>
    <!-- Athlètes -->
    <svg
        v-else-if="name === 'users'"
        :class="props.class"
        fill="none"
        viewBox="0 0 24 24"
        stroke-width="1.5"
        stroke="currentColor"
    >
        <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"
        />
    </svg>
    <!-- Programmes -->
    <svg
        v-else-if="name === 'clipboard'"
        :class="props.class"
        fill="none"
        viewBox="0 0 24 24"
        stroke-width="1.5"
        stroke="currentColor"
    >
        <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664M6.75 7a.75.75 0 01-.75-.75v-1.5A2.25 2.25 0 019 3h6a2.25 2.25 0 012.25 2.25v1.5a.75.75 0 01-.75.75M6.75 7h12M6.75 7l-.621 9.967a2.25 2.25 0 01-2.244 2.077H8.622a2.25 2.25 0 01-2.244-2.077L6.75 7z"
        />
    </svg>
    <!-- Messagerie (bulle) -->
    <svg
        v-else-if="name === 'chat'"
        :class="props.class"
        fill="none"
        viewBox="0 0 24 24"
        stroke-width="1.5"
        stroke="currentColor"
    >
        <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M7.5 8.25h9m-9 3H12m-4.5 3.75h9m-9-9h13.5a2.25 2.25 0 012.25 2.25v9a2.25 2.25 0 01-2.25 2.25H5.25l-3 3v-15a2.25 2.25 0 012.25-2.25z"
        />
    </svg>
    <!-- Profil -->
    <svg
        v-else-if="name === 'user-circle'"
        :class="props.class"
        fill="none"
        viewBox="0 0 24 24"
        stroke-width="1.5"
        stroke="currentColor"
    >
        <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z"
        />
    </svg>
    <!-- Déconnexion -->
    <svg
        v-else-if="name === 'logout'"
        :class="props.class"
        fill="none"
        viewBox="0 0 24 24"
        stroke-width="1.5"
        stroke="currentColor"
    >
        <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"
        />
    </svg>
    <!-- Calendrier compétitions -->
    <svg
        v-else-if="name === 'calendar'"
        :class="props.class"
        fill="none"
        viewBox="0 0 24 24"
        stroke-width="1.5"
        stroke="currentColor"
    >
        <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5a2.25 2.25 0 002.25-2.25m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5a2.25 2.25 0 012.25 2.25v7.5"
        />
    </svg>
    <!-- Trophée / PR -->
    <svg
        v-else-if="name === 'trophy'"
        :class="props.class"
        fill="none"
        viewBox="0 0 24 24"
        stroke-width="1.5"
        stroke="currentColor"
    >
        <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M16.5 18.75h-9m9 0a3 3 0 003-3h-15a3 3 0 003 3m9 0v.008c0 .414-.168.75-.375.75H9.375a.375.375 0 01-.375-.375V18.75zM4.875 15.75h14.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H4.875c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125zM6.75 7.5V6a2.25 2.25 0 012.25-2.25h6A2.25 2.25 0 0117.25 6v1.5M6.75 7.5h10.5v3a3 3 0 01-3 3h-4.5a3 3 0 01-3-3v-3z"
        />
    </svg>
    <!-- Liste / tâches -->
    <svg
        v-else-if="name === 'list'"
        :class="props.class"
        fill="none"
        viewBox="0 0 24 24"
        stroke-width="1.5"
        stroke="currentColor"
    >
        <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"
        />
    </svg>
    <!-- Video -->
    <svg
        v-else-if="name === 'video'"
        :class="props.class"
        fill="none"
        viewBox="0 0 24 24"
        stroke-width="1.5"
        stroke="currentColor"
    >
        <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z"
        />
    </svg>
    <!-- Point non lu (messagerie) -->
    <svg
        v-else-if="name === 'chat-dot'"
        :class="props.class"
        viewBox="0 0 24 24"
        fill="currentColor"
    >
        <circle cx="12" cy="12" r="6" />
    </svg>
    <!-- Alerte -->
    <svg
        v-else-if="name === 'alert'"
        :class="props.class"
        fill="none"
        viewBox="0 0 24 24"
        stroke-width="1.5"
        stroke="currentColor"
    >
        <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"
        />
    </svg>
    <!-- Tendance haussière -->
    <svg
        v-else-if="name === 'arrow-trend-up'"
        :class="props.class"
        fill="none"
        viewBox="0 0 24 24"
        stroke-width="2"
        stroke="currentColor"
    >
        <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M2.25 18L9 11.25l4.306 4.307a1.125 1.125 0 001.591 0L21.75 9M21.75 9h-5.25M21.75 9v5.25"
        />
    </svg>
    <!-- Barbell / force -->
    <svg
        v-else-if="name === 'bolt'"
        :class="props.class"
        fill="none"
        viewBox="0 0 24 24"
        stroke-width="1.5"
        stroke="currentColor"
    >
        <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"
        />
    </svg>
    <span v-else :class="['inline-block rounded bg-slate-700/60', props.class]" />
</template>
