<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import MessageThreadUnreadBadge from '../Components/MessageThreadUnreadBadge.vue';
import UiIcon from '../Components/UiIcon.vue';
import { useTheme } from '../composables/useTheme';
import { echo } from '../echo';

const page = usePage();
const user = computed(() => page.props.auth?.user ?? null);
const flash = computed(() => page.props.flash ?? {});
const isSidebarCollapsed = ref(false);
const { isLight, toggleTheme } = useTheme();

const isCoach = computed(() => user.value?.role === 'coach');
const messagingInbox = computed(() => page.props.messagingInbox ?? null);
let messagingPollTimer = null;
let layoutEchoChannel = null;

const coachNav = [
    { label: 'Dashboard', href: '/dashboard', pattern: '/dashboard', icon: 'dashboard' },
    { label: 'Athlètes', href: '/athletes', pattern: '/athletes', icon: 'users' },
    { label: 'Programmes', href: '/program-builder', pattern: '/program-builder', icon: 'clipboard' },
    { label: 'Retours', href: '/feedbacks', pattern: '/feedbacks', icon: 'video' },
    { label: 'Messagerie', href: '/messaging', pattern: '/messaging', icon: 'chat' },
];

const navItems = computed(() => {
    if (!user.value) {
        return [];
    }
    if (!isCoach.value) {
        return [
            {
                label: 'Accueil',
                href: '/athlete/dashboard',
                pattern: '/athlete/dashboard',
                icon: 'dashboard',
            },
            {
                label: 'Programme',
                href: '/athlete/program',
                pattern: '/athlete/program',
                icon: 'clipboard',
            },
            {
                label: 'Mon profil',
                href: `/athletes/${user.value.id}`,
                pattern: '/athletes',
                icon: 'user-circle',
            },
            {
                label: 'Retours',
                href: '/feedbacks',
                pattern: '/feedbacks',
                icon: 'video',
            },
            {
                label: 'Messagerie',
                href: messagingInbox.value?.thread_id
                    ? `/messaging?thread=${messagingInbox.value.thread_id}`
                    : '/messaging',
                pattern: '/messaging',
                icon: 'chat',
                unreadCount: messagingInbox.value?.unread_count ?? 0,
            },
        ];
    }
    return coachNav;
});

function navActive(pattern) {
    const url = page.url.split('?')[0];
    if (pattern === '/athletes') {
        return url === '/athletes' || url.startsWith('/athletes/');
    }
    if (pattern === '/feedbacks') {
        return url === '/feedbacks' || url.startsWith('/feedbacks/');
    }
    return url === pattern || url.startsWith(`${pattern}/`);
}

const sidebarClasses = computed(() =>
    isSidebarCollapsed.value
        ? 'w-20 px-2.5 lg:w-20 lg:px-2.5'
        : 'w-56 px-3 lg:w-64 lg:px-4',
);

const contentPaddingClasses = computed(() =>
    isSidebarCollapsed.value ? 'pl-20 lg:pl-20' : 'pl-56 lg:pl-64',
);

const contentWidthClasses = computed(() => {
    const url = page.url.split('?')[0];

    if (
        url === '/program-builder' ||
        url.startsWith('/program-builder/') ||
        url === '/athlete/program' ||
        url.startsWith('/athlete/program/') ||
        url === '/athletes'
    ) {
        return 'max-w-[112rem]';
    }

    return 'max-w-6xl';
});

function toggleSidebar() {
    isSidebarCollapsed.value = !isSidebarCollapsed.value;
}

function setupAthleteMessagingRealtime() {
    if (isCoach.value || typeof window === 'undefined') {
        return;
    }

    const threadId = messagingInbox.value?.thread_id;

    if (echo && threadId) {
        layoutEchoChannel = threadId;
        echo.private(`threads.${threadId}`).listen('.message.sent', () => {
            router.reload({
                only: ['messagingInbox'],
                preserveScroll: true,
                preserveState: true,
            });
        });

        return;
    }

    messagingPollTimer = window.setInterval(() => {
        router.reload({
            only: ['messagingInbox'],
            preserveScroll: true,
            preserveState: true,
        });
    }, 60000);
}

onMounted(() => {
    if (typeof window === 'undefined') {
        return;
    }

    isSidebarCollapsed.value = window.localStorage.getItem('tc-sidebar-collapsed') === 'true';
    setupAthleteMessagingRealtime();
});

onUnmounted(() => {
    if (messagingPollTimer) {
        window.clearInterval(messagingPollTimer);
    }

    if (echo && layoutEchoChannel) {
        echo.leave(`private-threads.${layoutEchoChannel}`);
    }
});

watch(isSidebarCollapsed, (value) => {
    if (typeof window === 'undefined') {
        return;
    }

    window.localStorage.setItem('tc-sidebar-collapsed', value ? 'true' : 'false');
});
</script>

<template>
    <div class="h-screen overflow-hidden bg-slate-950 text-slate-200">
        <aside
            class="fixed inset-y-0 left-0 z-40 flex flex-col border-r border-slate-800/90 bg-slate-900/95 py-5 transition-all duration-200"
            :class="sidebarClasses"
        >
            <div class="flex items-center gap-2">
                <Link
                    :href="isCoach ? '/dashboard' : '/athlete/dashboard'"
                    class="flex min-w-0 flex-1 items-center gap-2.5 rounded-xl border border-slate-700/80 bg-slate-800/40 px-3 py-2.5 transition hover:border-blue-500/50 hover:bg-slate-800/70"
                    :class="isSidebarCollapsed ? 'justify-center px-2.5' : ''"
                    :title="isSidebarCollapsed ? 'Track Coach' : undefined"
                >
                    <span
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-600/25 text-blue-300"
                    >
                        <UiIcon name="bolt" class="h-5 w-5" />
                    </span>
                    <span v-if="!isSidebarCollapsed" class="truncate text-base font-bold tracking-tight text-white">
                        Track Coach
                    </span>
                </Link>

                <button
                    type="button"
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-700/80 bg-slate-800/40 text-slate-300 transition hover:border-slate-600 hover:bg-slate-800/70 hover:text-white"
                    :title="isSidebarCollapsed ? 'Déplier la sidebar' : 'Replier la sidebar'"
                    @click="toggleSidebar"
                >
                    <span class="text-base leading-none">{{ isSidebarCollapsed ? '›' : '‹' }}</span>
                </button>
            </div>

            <div
                v-if="user"
                class="mt-5 flex gap-2.5 rounded-xl border border-slate-700/80 bg-slate-950/50 p-3"
                :class="isSidebarCollapsed ? 'justify-center px-2 py-3' : ''"
            >
                <span
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-800 text-slate-300"
                >
                    <UiIcon name="user-circle" class="h-5 w-5" />
                </span>
                <div v-if="!isSidebarCollapsed" class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold text-white">{{ user.name }}</p>
                    <p class="truncate text-xs text-slate-400">{{ user.email }}</p>
                    <p
                        class="mt-1.5 inline-flex rounded-full bg-slate-800 px-2 py-0.5 text-xs font-medium text-slate-300"
                    >
                        {{ user.role === 'coach' ? 'Coach' : 'Athlète' }}
                    </p>
                </div>
            </div>

            <nav class="mt-6 flex flex-1 flex-col gap-1">
                <p
                    v-if="!isSidebarCollapsed"
                    class="mb-1 px-1 text-[10px] font-semibold uppercase tracking-widest text-slate-500"
                >
                    Menu
                </p>
                <Link
                    v-for="item in navItems"
                    :key="item.href"
                    :href="item.href"
                    class="relative flex items-center gap-2.5 rounded-xl border px-2.5 py-2 transition"
                    :class="
                        [
                            isSidebarCollapsed ? 'justify-center px-2' : '',
                            navActive(item.pattern)
                                ? 'border-blue-500/60 bg-blue-600/20 text-white shadow-md shadow-blue-900/20'
                                : 'border-transparent text-slate-200 hover:border-slate-700 hover:bg-slate-800/50',
                        ]
                    "
                    :title="isSidebarCollapsed ? item.label : undefined"
                >
                    <span
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-slate-700/60 bg-slate-900/80"
                        :class="
                            navActive(item.pattern)
                                ? 'border-blue-400/40 bg-blue-600/30 text-white'
                                : 'text-blue-400'
                        "
                    >
                        <UiIcon :name="item.icon" class="h-4 w-4" />
                    </span>
                    <span v-if="!isSidebarCollapsed" class="text-sm font-medium">{{ item.label }}</span>
                    <MessageThreadUnreadBadge
                        v-if="item.unreadCount > 0"
                        :count="item.unreadCount"
                    />
                </Link>
            </nav>

            <div class="mt-auto space-y-2 border-t border-slate-800 pt-4">
                <button
                    type="button"
                    class="flex w-full items-center justify-center gap-2 rounded-xl border border-slate-700/80 bg-slate-800/40 px-3 py-2 text-sm font-medium text-slate-200 transition hover:border-slate-600 hover:bg-slate-800/70 hover:text-white"
                    :class="isSidebarCollapsed ? 'px-2' : ''"
                    :title="isLight ? 'Passer au thème sombre' : 'Passer au thème clair'"
                    @click="toggleTheme"
                >
                    <UiIcon :name="isLight ? 'moon' : 'sun'" class="h-4 w-4" />
                    <span v-if="!isSidebarCollapsed">{{ isLight ? 'Thème sombre' : 'Thème clair' }}</span>
                </button>

                <Link
                    href="/logout"
                    method="post"
                    as="button"
                    class="flex w-full items-center justify-center gap-2 rounded-xl border border-slate-600 bg-slate-800/50 px-3 py-2 text-sm font-medium text-slate-100 transition hover:bg-slate-800"
                    :class="isSidebarCollapsed ? 'px-2' : ''"
                    :title="isSidebarCollapsed ? 'Déconnexion' : undefined"
                >
                    <UiIcon name="logout" class="h-4 w-4" />
                    <span v-if="!isSidebarCollapsed">Déconnexion</span>
                </Link>
            </div>
        </aside>

        <div class="flex h-full min-h-0 min-w-0 flex-col transition-all duration-200" :class="contentPaddingClasses">
            <div
                v-if="flash.success"
                class="border-b border-emerald-900/50 bg-emerald-950/40 px-4 py-2.5 text-sm text-emerald-100 lg:px-8"
            >
                {{ flash.success }}
            </div>
            <div
                v-if="flash.error"
                class="border-b border-red-900/50 bg-red-950/40 px-4 py-2.5 text-sm text-red-100 lg:px-8"
            >
                {{ flash.error }}
            </div>

            <main
                class="app-main min-h-0 flex-1 overflow-x-hidden overflow-y-auto bg-gradient-to-b from-slate-950 to-slate-900/80 px-4 py-6 text-sm leading-relaxed text-slate-200 lg:px-8 lg:py-8"
            >
                <div class="mx-auto" :class="contentWidthClasses">
                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>
