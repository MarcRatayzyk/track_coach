<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import InstallAppButton from '../Components/InstallAppButton.vue';
import InstallAppGuideModal from '../Components/InstallAppGuideModal.vue';
import AppLogo from '../Components/AppLogo.vue';
import BugReportModal from '../Components/BugReportModal.vue';
import LanguageSwitcher from '../Components/LanguageSwitcher.vue';
import MessageThreadUnreadBadge from '../Components/MessageThreadUnreadBadge.vue';
import UiIcon from '../Components/UiIcon.vue';
import { useNativeApp } from '../composables/useNativeApp';
import { usePwaInstall } from '../composables/usePwaInstall';
import { useTheme } from '../composables/useTheme';
import { echo } from '../echo';
import { resetAnalytics } from '../utils/analytics';
import { localeTag } from '../i18n';

const { t, locale } = useI18n();
const page = usePage();
const user = computed(() => page.props.auth?.user ?? null);
const sidebarProfile = computed(() => page.props.auth?.sidebarProfile ?? null);
const flash = computed(() => page.props.flash ?? {});
const isMobileMenuOpen = ref(false);
const showBugReportModal = ref(false);
const { isLight, toggleTheme } = useTheme();
const { showInstallGuide, installGuideType, closeInstallGuide } = usePwaInstall();
const { isNative } = useNativeApp();
const isKeyboardOpen = ref(false);
let baselineViewportHeight = 0;
let focusOutTimer = 0;

const isCoach = computed(() => user.value?.role === 'coach');
const messagingInbox = computed(() => page.props.messagingInbox ?? null);
let messagingPollTimer = null;
const subscribedThreadChannels = [];
let userEchoChannel = null;
let viewportCleanup = null;

function isEditableTarget(target) {
    if (!target || !(target instanceof Element)) {
        return false;
    }
    const tag = target.tagName;
    if (tag === 'TEXTAREA') {
        return true;
    }
    if (tag === 'INPUT') {
        const type = (target.getAttribute('type') || 'text').toLowerCase();
        return !['button', 'checkbox', 'radio', 'file', 'submit', 'reset', 'range', 'color', 'hidden'].includes(type);
    }
    return Boolean(target.isContentEditable);
}

function captureBaselineViewport() {
    if (typeof window === 'undefined') {
        return;
    }
    const height = window.visualViewport?.height ?? window.innerHeight;
    if (height > 0 && !isKeyboardOpen.value) {
        baselineViewportHeight = Math.max(baselineViewportHeight, height);
    }
    if (baselineViewportHeight <= 0 && height > 0) {
        baselineViewportHeight = height;
    }
}

function syncKeyboardOpen() {
    if (typeof window === 'undefined') {
        isKeyboardOpen.value = false;
        return;
    }

    const viewport = window.visualViewport;
    const viewportHeight = viewport?.height ?? window.innerHeight;
    if (baselineViewportHeight <= 0) {
        captureBaselineViewport();
    }

    // Sur Capacitor/Android, innerHeight et visualViewport peuvent rétrécir ensemble :
    // on compare donc à une hauteur de référence capturée hors clavier.
    const shrinkFromBaseline = Math.max(0, baselineViewportHeight - viewportHeight);
    const obscuredLegacy = viewport
        ? Math.max(0, window.innerHeight - viewport.height - viewport.offsetTop)
        : 0;
    const active = typeof document !== 'undefined' ? document.activeElement : null;
    const editing = isEditableTarget(active);

    // Avec adjustPan, le viewport ne rétrécit pas toujours : on se base aussi sur le focus.
    isKeyboardOpen.value =
        editing ||
        shrinkFromBaseline > 100 ||
        obscuredLegacy > 100;
}

function bindKeyboardListeners() {
    if (typeof window === 'undefined') {
        return;
    }

    captureBaselineViewport();

    const onChange = () => syncKeyboardOpen();
    const onFocusIn = (event) => {
        window.clearTimeout(focusOutTimer);
        if (isEditableTarget(event.target)) {
            // Masque immédiatement la nav (évite le flash au-dessus du clavier).
            isKeyboardOpen.value = true;
        }
        window.setTimeout(syncKeyboardOpen, 50);
        window.setTimeout(syncKeyboardOpen, 280);
    };
    const onFocusOut = () => {
        window.clearTimeout(focusOutTimer);
        focusOutTimer = window.setTimeout(() => {
            syncKeyboardOpen();
            if (!isKeyboardOpen.value) {
                captureBaselineViewport();
            }
        }, 180);
    };
    const onOrientation = () => {
        isKeyboardOpen.value = false;
        baselineViewportHeight = 0;
        window.setTimeout(() => {
            captureBaselineViewport();
            syncKeyboardOpen();
        }, 350);
    };

    const viewport = window.visualViewport;
    viewport?.addEventListener('resize', onChange);
    viewport?.addEventListener('scroll', onChange);
    window.addEventListener('resize', onChange);
    window.addEventListener('focusin', onFocusIn);
    window.addEventListener('focusout', onFocusOut);
    window.addEventListener('orientationchange', onOrientation);
    syncKeyboardOpen();

    viewportCleanup = () => {
        viewport?.removeEventListener('resize', onChange);
        viewport?.removeEventListener('scroll', onChange);
        window.removeEventListener('resize', onChange);
        window.removeEventListener('focusin', onFocusIn);
        window.removeEventListener('focusout', onFocusOut);
        window.removeEventListener('orientationchange', onOrientation);
        window.clearTimeout(focusOutTimer);
    };
}

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

    // Do not poll / subscribe to messaging while the paywall is up.
    if (billing.value?.hasAccess === false) {
        return;
    }

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

const coachNavDefs = [
    { labelKey: 'nav.dashboard', shortKey: 'nav.home', href: '/dashboard', pattern: '/dashboard', icon: 'dashboard' },
    { labelKey: 'nav.athletes', shortKey: 'nav.athletes', href: '/athletes', pattern: '/athletes', icon: 'users' },
    { labelKey: 'nav.competitions', shortKey: 'nav.competitionsShort', href: '/competitions', pattern: '/competitions', icon: 'trophy' },
    { labelKey: 'nav.programs', shortKey: 'nav.programsShort', href: '/program-builder', pattern: '/program-builder', icon: 'clipboard' },
    { labelKey: 'nav.feedbacks', shortKey: 'nav.feedbacks', href: '/feedbacks', pattern: '/feedbacks', icon: 'video' },
    {
        labelKey: 'nav.messaging',
        shortKey: 'nav.messages',
        href: '/messaging',
        pattern: '/messaging',
        icon: 'chat',
        unreadCount: 0,
    },
    { labelKey: 'nav.billing', shortKey: 'nav.billingShort', href: '/billing', pattern: '/billing', icon: 'bolt' },
];

const billing = computed(() => page.props.billing ?? null);
const isDemoAccount = computed(() => Boolean(user.value?.is_demo || billing.value?.isDemo));
const homeHref = computed(() => {
    if (isCoach.value) {
        return billing.value?.hasAccess ? '/dashboard' : '/billing';
    }
    return billing.value?.hasAccess === false ? '/subscription/blocked' : '/athlete/dashboard';
});
const showSidebarProfile = computed(
    () => Boolean(user.value && sidebarProfile.value && billing.value?.hasAccess !== false),
);
const BILLING_BANNER_DISMISS_KEY = 'tc-billing-banner-dismissed';
const billingBannerDismissed = ref(
    typeof window !== 'undefined' && window.localStorage.getItem(BILLING_BANNER_DISMISS_KEY) === '1',
);

const demoExpiresLabel = computed(() => {
    if (!billing.value?.demoExpiresAt) {
        return null;
    }
    return new Date(billing.value.demoExpiresAt).toLocaleString(localeTag(locale.value), {
        day: 'numeric',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    });
});
const trialBannerLabel = computed(() => {
    if (!billing.value || billing.value.isDemo) {
        return null;
    }
    if (billing.value.status !== 'trial' || !billing.value.trialEndsAt) {
        return null;
    }
    return new Date(billing.value.trialEndsAt).toLocaleDateString(localeTag(locale.value));
});

const showBillingBanner = computed(
    () => !billingBannerDismissed.value && (isDemoAccount.value || Boolean(trialBannerLabel.value)),
);

function dismissBillingBanner() {
    billingBannerDismissed.value = true;
    if (typeof window !== 'undefined') {
        window.localStorage.setItem(BILLING_BANNER_DISMISS_KEY, '1');
    }
}

const flashErrorDismissed = ref(false);
watch(
    () => page.props.flash?.error,
    () => {
        flashErrorDismissed.value = false;
    },
);
const navItems = computed(() => {
    if (!user.value) {
        return [];
    }
    if (!isCoach.value) {
        if (billing.value?.hasAccess === false) {
            return [];
        }

        return [
            {
                label: t('nav.home'),
                shortLabel: t('nav.home'),
                href: '/athlete/dashboard',
                pattern: '/athlete/dashboard',
                icon: 'dashboard',
            },
            {
                label: t('nav.program'),
                shortLabel: t('nav.programsShort'),
                href: '/athlete/program',
                pattern: '/athlete/program',
                icon: 'clipboard',
            },
            {
                label: t('nav.myProfile'),
                shortLabel: t('nav.profile'),
                href: `/athletes/${user.value.id}`,
                pattern: '/athletes',
                icon: 'user-circle',
            },
            {
                label: t('nav.feedbacks'),
                shortLabel: t('nav.feedbacks'),
                href: '/feedbacks',
                pattern: '/feedbacks',
                icon: 'video',
            },
            {
                label: t('nav.messaging'),
                shortLabel: t('nav.messages'),
                href: messagingInbox.value?.thread_id
                    ? `/messaging?thread=${messagingInbox.value.thread_id}`
                    : '/messaging',
                pattern: '/messaging',
                icon: 'chat',
                unreadCount: messagingInbox.value?.unread_count ?? 0,
            },
        ];
    }

    // Paywall: only Abonnement is reachable until subscription / trial is active.
    if (!billing.value?.hasAccess) {
        return coachNavDefs
            .filter((item) => item.pattern === '/billing')
            .map((item) => ({
                ...item,
                label: t(item.labelKey),
                shortLabel: t(item.shortKey),
            }));
    }

    return coachNavDefs.map((item) => {
        const mapped = {
            ...item,
            label: t(item.labelKey),
            shortLabel: t(item.shortKey),
        };
        if (item.pattern === '/messaging') {
            mapped.unreadCount = messagingInbox.value?.total_unread ?? 0;
        }
        return mapped;
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

const sidebarClasses = 'w-56 px-3 lg:w-64 lg:px-4';

const contentPaddingClasses = computed(() => {
    // Quand le clavier est ouvert, on retire le padding bas réservé à la nav (masquée).
    const parts = ['pl-0', 'tc-app-content', 'lg:pb-0'];
    if (isKeyboardOpen.value) {
        parts.push('tc-app-content--keyboard');
    }
    if (showBillingBanner.value) {
        parts.push('tc-app-content--banner');
    }
    return `${parts.join(' ')} lg:pl-56 xl:pl-64`;
});

const contentWidthClasses = computed(() => {
    const url = page.url.split('?')[0];

    if (
        url === '/program-builder' ||
        url.startsWith('/program-builder/') ||
        url === '/athlete/program' ||
        url.startsWith('/athlete/program/') ||
        url === '/athletes' ||
        url === '/competitions' ||
        url.startsWith('/competitions/')
    ) {
        return 'max-w-[112rem]';
    }

    return 'max-w-6xl';
});

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

    document.documentElement.classList.add('tc-app-shell');
    window.localStorage.removeItem('tc-sidebar-collapsed');
    setupMessagingRealtime();
    bindKeyboardListeners();
});

onUnmounted(() => {
    document.documentElement.classList.remove('tc-app-shell');
    leaveMessagingChannels();
    if (viewportCleanup) {
        viewportCleanup();
        viewportCleanup = null;
    }
});

watch(messagingInbox, () => {
    setupMessagingRealtime();
}, { deep: true });

watch(() => page.url, () => {
    closeMobileMenu();
});
</script>

<template>
    <div class="h-dvh max-h-dvh overflow-hidden bg-slate-950 text-slate-200">
        <div
            v-if="showBillingBanner"
            class="fixed inset-x-0 top-0 z-[45] flex items-center justify-center gap-3 border-b px-4 py-2 text-center text-xs sm:text-sm lg:left-64"
            :class="
                isDemoAccount
                    ? 'border-amber-500/30 bg-amber-950/90 text-amber-100'
                    : 'border-blue-500/30 bg-blue-950/90 text-blue-100'
            "
        >
            <div class="min-w-0 flex-1 px-6">
                <template v-if="isDemoAccount">
                    {{ t('banners.demoAccount') }}
                    <span v-if="demoExpiresLabel"> — {{ t('banners.demoExpires', { date: demoExpiresLabel }) }}</span>
                    ·
                    <Link href="/register" class="font-semibold underline hover:no-underline">
                        {{ t('banners.createRealAccount') }}
                    </Link>
                </template>
                <template v-else>
                    {{ t('banners.trialUntil', { date: trialBannerLabel }) }} ·
                    <Link href="/billing" class="font-semibold underline hover:no-underline">
                        {{ t('banners.seeOffers') }}
                    </Link>
                </template>
            </div>
            <button
                type="button"
                class="absolute right-2 top-1/2 flex h-7 w-7 -translate-y-1/2 items-center justify-center rounded-lg text-current/80 transition hover:bg-white/10 hover:text-white"
                :aria-label="t('nav.closeBanner')"
                @click="dismissBillingBanner"
            >
                <UiIcon name="x-mark" class="h-4 w-4" />
            </button>
        </div>

        <header
            class="tc-app-mobile-header fixed inset-x-0 z-40 flex items-center justify-between gap-3 border-b border-slate-800/90 bg-slate-900/95 px-4 py-2 backdrop-blur-sm lg:hidden"
            :class="showBillingBanner ? 'top-9' : 'top-0'"
        >
            <Link
                :href="homeHref"
                class="flex min-w-0 items-center gap-2"
            >
                <AppLogo
                    mark-class="h-12 w-12"
                    wordmark-class="truncate text-base font-bold text-white"
                />
            </Link>

            <div class="flex min-w-0 items-center gap-2">
                <p v-if="user" class="max-w-[7rem] truncate text-xs text-slate-400 sm:max-w-[10rem]">
                    {{ user.name }}
                </p>
                <button
                    type="button"
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-700/80 bg-slate-800/40 text-slate-300 transition hover:bg-slate-800/70 hover:text-white"
                    :aria-label="t('nav.moreOptions')"
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
            :class="[
                isMobileMenuOpen ? 'visible opacity-100' : 'pointer-events-none invisible opacity-0',
                showBillingBanner ? 'tc-mobile-overlay-menu--banner' : '',
            ]"
        >
            <Link
                v-if="showSidebarProfile"
                :href="sidebarProfile.href"
                class="mb-1 block rounded-xl border border-slate-800 bg-slate-950/50 px-3 py-2.5"
                @click="closeMobileMenu"
            >
                <p class="truncate text-sm font-semibold text-white">{{ sidebarProfile.label }}</p>
                <p class="truncate text-xs text-slate-400">{{ sidebarProfile.subtitle }}</p>
            </Link>

            <InstallAppButton variant="menu" @interacted="closeMobileMenu" />

            <div class="mt-1 flex items-center justify-between gap-2 px-3 py-2">
                <span class="text-xs font-medium text-slate-400">{{ t('common.language') }}</span>
                <LanguageSwitcher variant="compact" />
            </div>

            <button
                type="button"
                class="mt-1 flex w-full items-center gap-2.5 rounded-xl px-3 py-2.5 text-left text-sm font-medium text-slate-200 transition hover:bg-slate-800/60"
                @click="toggleTheme(); closeMobileMenu()"
            >
                <UiIcon :name="isLight ? 'moon' : 'sun'" class="h-4 w-4 text-blue-400" />
                <span>{{ isLight ? t('nav.darkTheme') : t('nav.lightTheme') }}</span>
            </button>

            <button
                type="button"
                class="mt-1 flex w-full items-center gap-2.5 rounded-xl px-3 py-2.5 text-left text-sm font-medium text-slate-200 transition hover:bg-slate-800/60"
                @click="showBugReportModal = true; closeMobileMenu()"
            >
                <UiIcon name="alert" class="h-4 w-4 text-blue-400" />
                <span>{{ t('nav.reportProblem') }}</span>
            </button>

            <Link
                href="/account/privacy"
                class="mt-1 flex w-full items-center gap-2.5 rounded-xl px-3 py-2.5 text-left text-sm font-medium text-slate-200 transition hover:bg-slate-800/60"
                @click="closeMobileMenu"
            >
                <UiIcon name="user-circle" class="h-4 w-4 text-blue-400" />
                <span>{{ t('nav.privacyAndData') }}</span>
            </Link>

            <Link
                href="/logout"
                method="post"
                as="button"
                class="mt-1 flex w-full items-center gap-2.5 rounded-xl px-3 py-2.5 text-left text-sm font-medium text-slate-200 transition hover:bg-slate-800/60"
                @click="() => { resetAnalytics(); closeMobileMenu(); }"
            >
                <UiIcon name="logout" class="h-4 w-4 text-blue-400" />
                <span>{{ t('nav.logOut') }}</span>
            </Link>
        </div>

        <aside
            class="fixed inset-y-0 left-0 z-40 hidden flex-col border-r border-slate-800/90 bg-slate-900/95 py-5 transition-all duration-200 lg:flex"
            :class="sidebarClasses"
        >
            <div class="flex items-center gap-2">
                <Link
                    :href="homeHref"
                    class="flex min-w-0 flex-1 items-center gap-2.5 rounded-xl border border-slate-700/80 bg-slate-800/40 px-3 py-2.5 transition hover:border-blue-500/50 hover:bg-slate-800/70"
                >
                    <AppLogo
                        mark-class="h-14 w-14"
                        wordmark-class="truncate text-xl font-bold tracking-tight text-white"
                    />
                </Link>
            </div>

            <Link
                v-if="showSidebarProfile"
                :href="sidebarProfile.href"
                class="mt-5 flex gap-2.5 rounded-xl border border-slate-700/80 bg-slate-950/50 p-3 transition hover:border-blue-500/40 hover:bg-slate-900/80"
            >
                <span
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-800 text-slate-300"
                >
                    <UiIcon :name="isCoach ? 'user-circle' : 'users'" class="h-5 w-5" />
                </span>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold text-white">{{ sidebarProfile.label }}</p>
                    <p class="truncate text-xs text-slate-400">{{ sidebarProfile.subtitle }}</p>
                    <p
                        class="mt-1.5 inline-flex rounded-full bg-slate-800 px-2 py-0.5 text-xs font-medium text-slate-300"
                    >
                        {{ user.role === 'coach' ? t('common.coach') : t('common.athlete') }}
                    </p>
                </div>
            </Link>

            <nav class="mt-6 flex flex-1 flex-col gap-1">
                <p
                    class="mb-1 px-1 text-[10px] font-semibold uppercase tracking-widest text-slate-500"
                >
                    {{ t('nav.menu') }}
                </p>
                <Link
                    v-for="item in navItems"
                    :key="item.href"
                    :href="item.href"
                    class="relative flex items-center gap-2.5 rounded-xl border px-2.5 py-2 transition"
                    :class="
                        navActive(item.pattern)
                            ? 'border-blue-500/60 bg-blue-600/20 text-white shadow-md shadow-blue-900/20'
                            : 'border-transparent text-slate-200 hover:border-slate-700 hover:bg-slate-800/50'
                    "
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
                    <span class="text-sm font-medium">{{ item.label }}</span>
                    <MessageThreadUnreadBadge
                        v-if="item.unreadCount > 0"
                        :count="item.unreadCount"
                    />
                </Link>
            </nav>

            <div class="mt-auto space-y-2 border-t border-slate-800 pt-4">
                <div class="flex justify-center px-1 py-1">
                    <LanguageSwitcher />
                </div>

                <InstallAppButton
                    variant="sidebar"
                    :collapsed="false"
                />

                <button
                    type="button"
                    class="flex w-full items-center justify-center gap-2 rounded-xl border border-slate-700/80 bg-slate-800/40 px-3 py-2 text-sm font-medium text-slate-200 transition hover:border-slate-600 hover:bg-slate-800/70 hover:text-white"
                    :title="isLight ? t('nav.switchToDark') : t('nav.switchToLight')"
                    @click="toggleTheme"
                >
                    <UiIcon :name="isLight ? 'moon' : 'sun'" class="h-4 w-4" />
                    <span>{{ isLight ? t('nav.darkTheme') : t('nav.lightTheme') }}</span>
                </button>

                <button
                    type="button"
                    class="flex w-full items-center justify-center gap-2 rounded-xl border border-slate-700/80 bg-slate-800/40 px-3 py-2 text-sm font-medium text-slate-200 transition hover:border-slate-600 hover:bg-slate-800/70 hover:text-white"
                    @click="showBugReportModal = true"
                >
                    <UiIcon name="alert" class="h-4 w-4" />
                    <span>{{ t('nav.reportProblem') }}</span>
                </button>

                <Link
                    href="/account/privacy"
                    class="flex w-full items-center justify-center gap-2 rounded-xl border border-slate-700/80 bg-slate-800/40 px-3 py-2 text-sm font-medium text-slate-200 transition hover:border-slate-600 hover:bg-slate-800/70 hover:text-white"
                >
                    <UiIcon name="user-circle" class="h-4 w-4" />
                    <span>{{ t('nav.privacy') }}</span>
                </Link>

                <Link
                    href="/logout"
                    method="post"
                    as="button"
                    class="flex w-full items-center justify-center gap-2 rounded-xl border border-slate-600 bg-slate-800/50 px-3 py-2 text-sm font-medium text-slate-100 transition hover:bg-slate-800"
                    @click="resetAnalytics"
                >
                    <UiIcon name="logout" class="h-4 w-4" />
                    <span>{{ t('nav.logOut') }}</span>
                </Link>
            </div>
        </aside>

        <nav
            v-show="!isKeyboardOpen"
            class="mobile-bottom-nav fixed inset-x-0 bottom-0 z-40 border-t border-slate-800/90 bg-slate-900/95 backdrop-blur-sm lg:hidden"
            :aria-hidden="isKeyboardOpen ? 'true' : undefined"
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
                v-if="flash.error && !flashErrorDismissed"
                class="relative z-50 border-b border-red-900/50 bg-red-950/90 px-4 py-2.5 text-sm text-red-100 lg:px-8"
            >
                <div class="flex items-start justify-between gap-3">
                    <p class="min-w-0 flex-1">{{ flash.error }}</p>
                    <button
                        type="button"
                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-red-200/80 transition hover:bg-red-900/50 hover:text-white"
                        :aria-label="t('nav.close')"
                        @click="flashErrorDismissed = true"
                    >
                        <UiIcon name="x-mark" class="h-4 w-4" />
                    </button>
                </div>
            </div>

            <main
                class="app-main min-h-0 flex-1 overflow-x-hidden overflow-y-auto overscroll-y-contain bg-gradient-to-b from-slate-950 to-slate-900/80 px-3 py-2 text-sm leading-relaxed text-slate-200 sm:px-4 sm:py-5 lg:px-8 lg:py-8"
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

        <BugReportModal v-model="showBugReportModal" />
    </div>
</template>
