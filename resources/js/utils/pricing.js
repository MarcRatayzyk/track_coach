export const CURRENCY_STORAGE_KEY = 'pr_currency';

export const PLAN_KEYS = ['starter', 'growth', 'scale'];

/**
 * List prices: EUR catalogue + USD conversion (marketing .99).
 * With -50% launch: EUR 19.99 / 29.99 / 39.99 — USD 24.99 / 34.99 / 44.99
 */
export const DEFAULT_PRICING = {
  launch_discount_percent: 50,
  plans: [
    { key: 'starter', name: 'Starter', price_eur: 39.99, price_usd: 49.99, max_athletes: 15 },
    { key: 'growth', name: 'Growth', price_eur: 59.99, price_usd: 69.99, max_athletes: 40 },
    { key: 'scale', name: 'Scale', price_eur: 79.99, price_usd: 89.99, max_athletes: null },
  ],
};

export function resolveCurrency(raw) {
  return raw === 'usd' ? 'usd' : 'eur';
}

/** EN → USD, FR (and others) → EUR */
export function currencyFromLocale(locale) {
  return String(locale).startsWith('en') ? 'usd' : 'eur';
}

export function readStoredCurrency() {
  if (typeof localStorage === 'undefined') {
    return 'eur';
  }
  return resolveCurrency(localStorage.getItem(CURRENCY_STORAGE_KEY));
}

export function storeCurrency(currency) {
  const resolved = resolveCurrency(currency);
  if (typeof localStorage !== 'undefined') {
    localStorage.setItem(CURRENCY_STORAGE_KEY, resolved);
  }
  return resolved;
}

/** Prefer locale-driven currency; keep storage in sync for other pages. */
export function syncCurrencyWithLocale(locale) {
  return storeCurrency(currencyFromLocale(locale));
}

export function listPrice(plan, currency = 'eur') {
  const key = resolveCurrency(currency) === 'usd' ? 'price_usd' : 'price_eur';
  const n = Number(plan?.[key] ?? plan?.price_eur ?? 0);
  return Number.isFinite(n) ? n : 0;
}

/**
 * Keeps .99-style amounts after discount (49.99 → 24.99 at 50%).
 */
export function discountedPrice(amount, percent = 0) {
  const price = Number(amount);
  const pct = Number(percent);
  if (!Number.isFinite(price)) {
    return 0;
  }
  if (!Number.isFinite(pct) || pct <= 0) {
    return price;
  }
  const cents = Math.round(price * 100);
  return Math.floor((cents * (100 - pct)) / 100) / 100;
}

export function currencySymbol(currency = 'eur') {
  return resolveCurrency(currency) === 'usd' ? '$' : '€';
}

export function formatMoney(amount, { locale = 'en', currency = 'eur' } = {}) {
  const n = Number(amount);
  if (!Number.isFinite(n)) {
    return String(amount ?? '');
  }
  const tag = String(locale).startsWith('fr') ? 'fr-FR' : 'en-US';
  const code = resolveCurrency(currency) === 'usd' ? 'USD' : 'EUR';
  return new Intl.NumberFormat(tag, {
    style: 'currency',
    currency: code,
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(n);
}

export function formatMoneyPlain(amount, { locale = 'en', currency = 'eur' } = {}) {
  const n = Number(amount);
  if (!Number.isFinite(n)) {
    return String(amount ?? '');
  }
  const tag = String(locale).startsWith('fr') ? 'fr-FR' : 'en-US';
  const formatted = n.toLocaleString(tag, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
  return resolveCurrency(currency) === 'usd' ? `$${formatted}` : `${formatted} €`;
}
