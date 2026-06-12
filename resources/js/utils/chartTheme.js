export const LIFT_COLORS = {
  squat: { border: 'rgb(167, 139, 250)', bg: 'rgba(167, 139, 250, 0.25)' },
  bench: { border: 'rgb(232, 121, 249)', bg: 'rgba(232, 121, 249, 0.25)' },
  deadlift: { border: 'rgb(251, 191, 36)', bg: 'rgba(251, 191, 36, 0.25)' },
  total: { border: 'rgb(147, 197, 253)', bg: 'rgba(147, 197, 253, 0.2)' },
};

export const BLOCK_TYPE_COLORS = {
  volume: { border: 'rgb(52, 211, 153)', bg: 'rgba(52, 211, 153, 0.15)' },
  intensification: { border: 'rgb(59, 130, 246)', bg: 'rgba(59, 130, 246, 0.15)' },
  peaking: { border: 'rgb(251, 191, 36)', bg: 'rgba(251, 191, 36, 0.15)' },
};

export const READINESS_COLORS = {
  score: { border: 'rgb(59, 130, 246)', bg: 'rgba(59, 130, 246, 0.15)' },
};

export const COMPETITION_COLORS = {
  border: 'rgb(251, 191, 36)',
  bg: 'rgba(251, 191, 36, 0.15)',
};

function withAlpha(color, alpha) {
  if (color.startsWith('rgba')) {
    return color.replace(/,\s*[\d.]+\)$/, `, ${alpha})`);
  }

  return color.replace('rgb(', 'rgba(').replace(')', `, ${alpha})`);
}

function isLightTheme() {
  if (typeof document === 'undefined') {
    return false;
  }

  return document.documentElement.dataset.theme === 'light';
}

const GLOW_CARD_BG_DARK =
  'linear-gradient(145deg, rgba(15, 23, 42, 0.92) 0%, rgba(2, 6, 23, 0.78) 100%)';
const GLOW_CARD_BG_LIGHT =
  'linear-gradient(145deg, #ffffff 0%, #fffbeb 100%)';

export function glowCardStyle(colors) {
  const glow = colors.border ?? colors;
  const isLight = isLightTheme();

  return {
    background: isLight ? GLOW_CARD_BG_LIGHT : GLOW_CARD_BG_DARK,
    borderWidth: '1px',
    borderStyle: 'solid',
    borderColor: withAlpha(glow, isLight ? 0.45 : 0.32),
    boxShadow: isLight
      ? [
          `0 0 16px ${withAlpha(glow, 0.28)}`,
          `0 0 32px ${withAlpha(glow, 0.12)}`,
          'inset 0 1px 0 rgba(255, 255, 255, 0.9)',
        ].join(', ')
      : [
          `0 0 14px ${withAlpha(glow, 0.3)}`,
          `0 0 28px ${withAlpha(glow, 0.12)}`,
          'inset 0 1px 0 rgba(255, 255, 255, 0.06)',
        ].join(', '),
  };
}

export function prGlowCardStyle() {
  const squat = LIFT_COLORS.squat.border;
  const bench = LIFT_COLORS.bench.border;
  const deadlift = LIFT_COLORS.deadlift.border;
  const isLight = isLightTheme();

  return {
    background: isLight ? GLOW_CARD_BG_LIGHT : GLOW_CARD_BG_DARK,
    borderWidth: '1px',
    borderStyle: 'solid',
    borderColor: withAlpha(LIFT_COLORS.total.border, 0.28),
    boxShadow: isLight
      ? [
          `-8px 0 22px ${withAlpha(squat, 0.18)}`,
          `0 0 20px ${withAlpha(bench, 0.12)}`,
          `8px 0 22px ${withAlpha(deadlift, 0.18)}`,
          'inset 0 1px 0 rgba(255, 255, 255, 0.9)',
        ].join(', ')
      : [
          `-8px 0 22px ${withAlpha(squat, 0.2)}`,
          `0 0 20px ${withAlpha(bench, 0.14)}`,
          `8px 0 22px ${withAlpha(deadlift, 0.2)}`,
          'inset 0 1px 0 rgba(255, 255, 255, 0.06)',
        ].join(', '),
  };
}

export const LIFT_LABELS = {
  squat: 'Squat',
  bench: 'Bench',
  deadlift: 'Terre',
  total: 'Total',
};

const gridColor = 'rgba(51, 65, 85, 0.5)';
const tickColor = 'rgb(148, 163, 184)';

export function baseChartOptions(overrides = {}) {
  return {
    responsive: true,
    maintainAspectRatio: false,
    interaction: { mode: 'index', intersect: false },
    plugins: {
      legend: {
        labels: {
          color: tickColor,
          usePointStyle: true,
          padding: 16,
        },
      },
      tooltip: {
        backgroundColor: 'rgb(15, 23, 42)',
        titleColor: 'rgb(248, 250, 252)',
        bodyColor: 'rgb(203, 213, 225)',
        borderColor: 'rgb(51, 65, 85)',
        borderWidth: 1,
        padding: 12,
      },
    },
    scales: {
      x: {
        grid: { color: gridColor },
        ticks: { color: tickColor },
      },
      y: {
        grid: { color: gridColor },
        ticks: { color: tickColor },
        beginAtZero: true,
      },
    },
    ...overrides,
  };
}

export function doughnutChartOptions(overrides = {}) {
  return {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '62%',
    spacing: 0,
    layout: {
      padding: 0,
    },
    elements: {
      arc: {
        borderWidth: 0,
        hoverBorderWidth: 0,
      },
    },
    plugins: {
      legend: {
        position: 'bottom',
        labels: {
          color: tickColor,
          usePointStyle: true,
          padding: 12,
        },
      },
      tooltip: {
        backgroundColor: 'rgb(15, 23, 42)',
        titleColor: 'rgb(248, 250, 252)',
        bodyColor: 'rgb(203, 213, 225)',
        borderColor: 'rgb(51, 65, 85)',
        borderWidth: 1,
        callbacks: {
          label(context) {
            const value = context.parsed ?? 0;
            const total = context.dataset.data.reduce((a, b) => a + b, 0);
            const pct = total > 0 ? Math.round((value / total) * 100) : 0;
            return `${context.label}: ${Math.round(value).toLocaleString('fr-FR')} kg·reps (${pct} %)`;
          },
        },
      },
    },
    ...overrides,
  };
}
