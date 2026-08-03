export const CURRENCY_STORAGE_KEY = 'pr_currency';

export const PLAN_KEYS = ['starter', 'growth', 'scale'];

/** USD per 1 EUR (display). EUR = USD / rate. */
export const DEFAULT_EUR_TO_USD_RATE = 1.08;

/**
 * Catalogue in USD. Launch -50% → 24.99 / 34.99 / 44.99 USD.
 * EUR is converted from those USD amounts.
 */
export const DEFAULT_PRICING = {
  launch_discount_percent: 50,
  eur_to_usd_rate: DEFAULT_EUR_TO_USD_RATE,
  plans: [
    { key: 'starter', name: 'Starter', price_usd: 49.99, max_athletes: 15 },
    { key: 'growth', name: 'Growth', price_usd: 69.99, max_athletes: 40 },
    { key: 'scale', name: 'Scale', price_usd: 89.99, max_athletes: null },
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

export function syncCurrencyWithLocale(locale) {
  return storeCurrency(currencyFromLocale(locale));
}

/** 24.99 USD → EUR at rate 1.08 ≈ 23.14 */
export function usdToEur(amountUsd, rate = DEFAULT_EUR_TO_USD_RATE) {
  const usd = Number(amountUsd);
  const fx = Number(rate);
  if (!Number.isFinite(usd) || !Number.isFinite(fx) || fx <= 0) {
    return 0;
  }
  return Math.round((usd / fx) * 100) / 100;
}

export function listPrice(plan, currency = 'eur', rate = DEFAULT_EUR_TO_USD_RATE) {
  const usd = Number(plan?.price_usd ?? 0);
  if (resolveCurrency(currency) === 'usd') {
    return Number.isFinite(usd) ? usd : 0;
  }
  if (plan?.price_eur != null && Number.isFinite(Number(plan.price_eur))) {
    return Number(plan.price_eur);
  }
  return usdToEur(usd, rate);
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
