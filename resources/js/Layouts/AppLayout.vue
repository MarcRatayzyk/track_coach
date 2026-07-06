<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import InstallAppButton from '../Components/InstallAppButton.vue';
import InstallAppGuideModal from '../Components/InstallAppGuideModal.vue';
import MessageThreadUnreadBadge from '../Components/MessageThreadUnreadBadge.vue';
import UiIcon from '../Components/UiIcon.vue';
import { useNativeApp } from '../composables/useNativeApp';
import { usePwaInstall } from '../composables/usePwaInstall';
import { useTheme } from '../composables/useTheme';
import { echo } from '../echo';

const page = usePage();
const user = computed(() => page.props.auth?.user ?? null);
const flash = computed(() => page.props.flash ?? {});
const isSidebarCollapsed = ref(false);
const isMobileMenuOpen = ref(false);
const { isLight, toggleTheme } = useTheme();
const { showInstallGuide, installGuideType, closeInstallGuide } = usePwaInstall();
const { isNative } = useNativeApp();

const isCoach = computed(() => user.value?.role === 'coach');
const messagingInbox = computed(() => page.props.messagingInbox ?? null);
let messagingPollTimer = null;
const subscribedThreadChannels = [];
let userEchoChannel = null;

function reloadMessagingInbox() {
    router.reload({
        only: ['messagingInbox'],
        preserveScroll: true,
        preserveState: true,
    });
}

function leaveMessagingChannels() {
    if (messagingPollTimer) {
        window.clearInterval(messagingPollTimer);
        messagingPollTimer = null;
    }

    if (echo) {
        subscribedThreadChannels.forEach((threadId) => {
            echo.leave(`private-threads.${threadId}`);
        });
        subscribedThreadChannels.length = 0;

        if (userEchoChannel) {
            echo.leave(`private-users.${userEchoChannel}`);
            userEchoChannel = null;
        }
    }
}

function setupMessagingRealtime() {
    if (typeof window === 'undefined' || !user.value) {
        return;
    }

    leaveMessagingChannels();

    const threadIds = isCoach.value
        ? (messagingInbox.value?.thread_ids ?? [])
        : messagingInbox.value?.thread_id
            ? [messagingInbox.value.thread_id]
            : [];

    if (echo && user.value.id) {
        userEchoChannel = user.value.id;
        echo.private(`users.${user.value.id}`).listen('.thread.updated', reloadMessagingInbox);

        threadIds.forEach((threadId) => {
            subscribedThreadChannels.push(threadId);
            echo.private(`threads.${threadId}`).listen('.message.sent', reloadMessagingInbox);
        });

        return;
    }

    messagingPollTimer = window.setInterval(reloadMessagingInbox, 60000);
}

const coachNav = [
    { label: 'Dashboard', shortLabel: 'Accueil', href: '/dashboard', pattern: '/dashboard', icon: 'dashboard' },
    { label: 'Athlètes', shortLabel: 'Athlètes', href: '/athletes', pattern: '/athletes', icon: 'users' },
    { label: 'Programmes', shortLabel: 'Prog.', href: '/program-builder', pattern: '/program-builder', icon: 'clipboard' },
    { label: 'Retours', shortLabel: 'Retours', href: '/feedbacks', pattern: '/feedbacks', icon: 'video' },
    {
        label: 'Messagerie',
        shortLabel: 'Messages',
        href: '/messaging',
        pattern: '/messaging',
        icon: 'chat',
        unreadCount: 0,
    },
];

const navItems = computed(() => {
    if (!user.value) {
        return [];
    }
    if (!isCoach.value) {
        return [
            {
                label: 'Accueil',
                shortLabel: 'Accueil',
                href: '/athlete/dashboard',
                pattern: '/athlete/dashboard',
                icon: 'dashboard',
            },
            {
                label: 'Programme',
                shortLabel: 'Prog.',
                href: '/athlete/program',
                pattern: '/athlete/program',
                icon: 'clipboard',
            },
            {
                label: 'Mon profil',
                shortLabel: 'Profil',
                href: `/athletes/${user.value.id}`,
                pattern: '/athletes',
                icon: 'user-circle',
            },
            {
                label: 'Retours',
                shortLabel: 'Retours',
                href: '/feedbacks',
                pattern: '/feedbacks',
                icon: 'video',
            },
            {
                label: 'Messagerie',
                shortLabel: 'Messages',
                href: messagingInbox.value?.thread_id
                    ? `/messaging?thread=${messagingInbox.value.thread_id}`
                    : '/messaging',
                pattern: '/messaging',
                icon: 'chat',
                unreadCount: messagingInbox.value?.unread_count ?? 0,
            },
        ];
    }
    return coachNav.map((item) => {
        if (item.pattern !== '/messaging') {
            return item;
        }

        return {
            ...item,
            unreadCount: messagingInbox.value?.total_unread ?? 0,
        };
    });
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

const contentPaddingClasses = computed(() => {
    const mobile = 'pl-0 tc-app-content lg:pb-0';

    if (isSidebarCollapsed.value) {
        return `${mobile} lg:pl-20`;
    }

    return `${mobile} lg:pl-56 xl:pl-64`;
});

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

function toggleMobileMenu() {
    isMobileMenuOpen.value = !isMobileMenuOpen.value;
}

function closeMobileMenu() {
    isMobileMenuOpen.value = false;
}

onMounted(() => {
    if (typeof window === 'undefined') {
        return;
    }

    isSidebarCollapsed.value = window.localStorage.getItem('tc-sidebar-collapsed') === 'true';
    setupMessagingRealtime();
});

onUnmounted(() => {
    leaveMessagingChannels();
});

watch(messagingInbox, () => {
    setupMessagingRealtime();
}, { deep: true });

watch(isSidebarCollapsed, (value) => {
    if (typeof window === 'undefined') {
        return;
    }

    window.localStorage.setItem('tc-sidebar-collapsed', value ? 'true' : 'false');
});

watch(() => page.url, () => {
    closeMobileMenu();
});
</script>

<template>
    <div class="h-screen overflow-hidden bg-slate-950 text-slate-200">
        <header
            class="tc-app-mobile-header fixed inset-x-0 top-0 z-40 flex items-center justify-between gap-3 border-b border-slate-800/90 bg-slate-900/95 px-4 py-3 backdrop-blur-sm lg:hidden"
        >
            <Link
                :href="isCoach ? '/dashboard' : '/athlete/dashboard'"
                class="flex min-w-0 items-center gap-2"
            >
                <span
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-600/25 text-blue-300"
                >
                    <UiIcon name="bolt" class="h-4 w-4" />
                </span>
                <span class="truncate text-sm font-bold text-white">Track Coach</span>
            </Link>

            <div class="flex min-w-0 items-center gap-2">
                <p v-if="user" class="max-w-[7rem] truncate text-xs text-slate-400 sm:max-w-[10rem]">
                    {{ user.name }}
                </p>
                <button
                    type="button"
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-700/80 bg-slate-800/40 text-slate-300 transition hover:bg-slate-800/70 hover:text-white"
                    aria-label="Plus d'options"
                    @click="toggleMobileMenu"
                >
                    <UiIcon name="ellipsis-vertical" class="h-4 w-4" />
                </button>
            </div>
        </header>

        <div
            v-if="isMobileMenuOpen"
            class="fixed inset-0 z-50 bg-slate-950/60 lg:hidden"
            @click="closeMobileMenu"
        />

        <div
            class="tc-mobile-overlay-menu fixed inset-x-0 z-50 mx-3 rounded-2xl border border-slate-700 bg-slate-900 p-2 shadow-2xl transition lg:hidden"
            :class="isMobileMenuOpen ? 'visible opacity-100' : 'pointer-events-none invisible opacity-0'"
        >
            <div
                v-if="user"
                class="mb-1 rounded-xl border border-slate-800 bg-slate-950/50 px-3 py-2.5"
            >
                <p class="truncate text-sm font-semibold text-white">{{ user.name }}</p>
                <p class="truncate text-xs text-slate-400">{{ user.email }}</p>
            </div>

            <InstallAppButton variant="menu" @interacted="closeMobileMenu" />

            <button
                type="button"
                class="mt-1 flex w-full items-center gap-2.5 rounded-xl px-3 py-2.5 text-left text-sm font-medium text-slate-200 transition hover:bg-slate-800/60"
                @click="toggleTheme(); closeMobileMenu()"
            >
                <UiIcon :name="isLight ? 'moon' : 'sun'" class="h-4 w-4 text-blue-400" />
                <span>{{ isLight ? 'Thème sombre' : 'Thème clair' }}</span>
            </button>

            <Link
                href="/logout"
                method="post"
                as="button"
                class="mt-1 flex w-full items-center gap-2.5 rounded-xl px-3 py-2.5 text-left text-sm font-medium text-slate-200 transition hover:bg-slate-800/60"
                @click="closeMobileMenu"
            >
                <UiIcon name="logout" class="h-4 w-4 text-blue-400" />
                <span>Déconnexion</span>
            </Link>
        </div>

        <aside
            class="fixed inset-y-0 left-0 z-40 hidden flex-col border-r border-slate-800/90 bg-slate-900/95 py-5 transition-all duration-200 lg:flex"
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
                <InstallAppButton
                    variant="sidebar"
                    :collapsed="isSidebarCollapsed"
                />

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

        <nav
            class="mobile-bottom-nav fixed inset-x-0 bottom-0 z-40 border-t border-slate-800/90 bg-slate-900/95 backdrop-blur-sm lg:hidden"
        >
            <div class="flex items-stretch justify-around px-1 pt-1">
                <Link
                    v-for="item in navItems"
                    :key="`mobile-${item.href}`"
                    :href="item.href"
                    class="relative flex min-w-0 flex-1 flex-col items-center gap-0.5 rounded-lg px-1 py-2 transition"
                    :class="
                        navActive(item.pattern)
                            ? 'text-blue-300'
                            : 'text-slate-400 hover:text-slate-200'
                    "
                >
                    <span
                        class="relative flex h-7 w-7 items-center justify-center rounded-lg"
                        :class="navActive(item.pattern) ? 'bg-blue-600/25' : ''"
                    >
                        <UiIcon :name="item.icon" class="h-4 w-4" />
                        <MessageThreadUnreadBadge
                            v-if="item.unreadCount > 0"
                            :count="item.unreadCount"
                        />
                    </span>
                    <span class="max-w-full truncate text-[10px] font-medium leading-tight">
                        {{ item.shortLabel ?? item.label }}
                    </span>
                </Link>
            </div>
        </nav>

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
                class="app-main min-h-0 flex-1 overflow-x-hidden overflow-y-auto bg-gradient-to-b from-slate-950 to-slate-900/80 px-3 py-4 text-sm leading-relaxed text-slate-200 sm:px-4 sm:py-6 lg:px-8 lg:py-8"
            >
                <div class="mx-auto" :class="contentWidthClasses">
                    <slot />
                </div>
            </main>
        </div>

        <InstallAppGuideModal
            v-if="!isNative"
            :open="showInstallGuide"
            :guide-type="installGuideType"
            @close="closeInstallGuide"
        />
    </div>
</template>
