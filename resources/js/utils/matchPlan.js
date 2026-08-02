export const LIFTS = ['squat', 'bench', 'deadlift'];

export const LIFT_LABELS = {
  squat: 'Squat (S)',
  bench: 'Bench (B)',
  deadlift: 'Deadlift (D)',
};

export const ATTEMPT_KEYS = ['attempt1', 'attempt2', 'attempt3'];

export const ATTEMPT_LABELS = {
  attempt1: 'Essai 1',
  attempt2: 'Essai 2',
  attempt3: 'Essai 3',
};

export const MAX_WARMUP_BARS = 10;

function newId() {
  return `sc_${Date.now()}_${Math.random().toString(36).slice(2, 9)}`;
}

export function emptyScenario(name = 'Scénario') {
  const lifts = {};
  for (const lift of LIFTS) {
    lifts[lift] = { attempt1: null, attempt2: null, attempt3: null };
  }
  return { id: newId(), name, lifts };
}

export function emptyWarmups() {
  return { squat: [], bench: [], deadlift: [] };
}

export function defaultStructuredPlan() {
  return {
    mode: 'structured',
    scenarios: [emptyScenario('Scénario principal')],
    warmups: emptyWarmups(),
  };
}

export function defaultTextPlan(text = '') {
  return { mode: 'text', text, warmups: emptyWarmups() };
}

function nullableWeight(value) {
  if (value === null || value === '' || value === undefined) {
    return null;
  }
  const n = Number(value);
  return Number.isFinite(n) ? n : null;
}

function nullableReps(value) {
  if (value === null || value === '' || value === undefined) {
    return null;
  }
  const n = Number(value);
  if (!Number.isFinite(n)) {
    return null;
  }
  const reps = Math.round(n);
  return reps >= 1 && reps <= 50 ? reps : null;
}

/** @returns {{ weight: number, reps: number|null }|null} */
export function normalizeWarmupBar(value) {
  if (value !== null && typeof value === 'object' && !Array.isArray(value)) {
    const weight = nullableWeight(value.weight);
    if (weight === null) {
      return null;
    }
    return { weight, reps: nullableReps(value.reps) };
  }
  const weight = nullableWeight(value);
  if (weight === null) {
    return null;
  }
  return { weight, reps: null };
}

export function normalizeWarmups(raw) {
  const out = emptyWarmups();
  if (!raw || typeof raw !== 'object') {
    return out;
  }
  for (const lift of LIFTS) {
    const list = Array.isArray(raw[lift]) ? raw[lift] : [];
    out[lift] = list
      .map(normalizeWarmupBar)
      .filter((v) => v !== null)
      .slice(0, MAX_WARMUP_BARS);
  }
  return out;
}

export function normalizeScenario(scenario) {
  const lifts = {};
  for (const lift of LIFTS) {
    const raw = scenario?.lifts?.[lift] ?? {};
    lifts[lift] = {
      attempt1: nullableWeight(raw.attempt1),
      attempt2: nullableWeight(raw.attempt2),
      attempt3: nullableWeight(raw.attempt3),
    };
  }
  return {
    id: scenario?.id ?? newId(),
    name: (scenario?.name ?? 'Scénario').trim() || 'Scénario',
    lifts,
  };
}

export function normalizePlan(data) {
  if (!data || typeof data !== 'object') {
    return defaultStructuredPlan();
  }

  const warmups = normalizeWarmups(data.warmups);

  if (data.mode === 'text') {
    return { mode: 'text', text: data.text ?? '', warmups };
  }

  const scenarios = (data.scenarios ?? []).map(normalizeScenario);
  if (!scenarios.length) {
    scenarios.push(emptyScenario('Scénario principal'));
  }

  return { mode: 'structured', scenarios, warmups };
}

export function matchPlanFromCompetition(comp) {
  if (comp?.match_plan_data && typeof comp.match_plan_data === 'object') {
    return normalizePlan(comp.match_plan_data);
  }
  if (comp?.match_plan?.trim()) {
    return defaultTextPlan(comp.match_plan);
  }
  return defaultStructuredPlan();
}

export function formatWeight(value) {
  if (value === null || value === '' || value === undefined) {
    return '—';
  }
  const n = Number(value);
  if (!Number.isFinite(n)) {
    return '—';
  }
  if (Math.abs(n - Math.round(n)) < 0.001) {
    return String(Math.round(n));
  }
  return String(n).replace(/\.?0+$/, '') || String(n);
}

export function formatWarmupBar(bar) {
  if (!bar || bar.weight == null) {
    return '—';
  }
  const weight = formatWeight(bar.weight);
  if (bar.reps != null) {
    return `${weight}×${bar.reps}`;
  }
  return `${weight} kg`;
}

export function scenarioTotal(scenario) {
  let sum = 0;
  let has = false;
  for (const lift of LIFTS) {
    const v = scenario?.lifts?.[lift]?.attempt3;
    if (v !== null && v !== '' && v !== undefined) {
      sum += Number(v);
      has = true;
    }
  }
  return has ? sum : null;
}

function hasWarmupContent(warmups) {
  if (!warmups) {
    return false;
  }
  return LIFTS.some((lift) => (warmups[lift] ?? []).length > 0);
}

export function hasWarmupBars(compOrPlan) {
  const plan = compOrPlan?.match_plan_data
    ? matchPlanFromCompetition(compOrPlan)
    : normalizePlan(compOrPlan);
  return hasWarmupContent(plan.warmups);
}

export function hasMatchPlanContent(comp) {
  const plan = matchPlanFromCompetition(comp);
  if (plan.mode === 'text') {
    return Boolean(plan.text?.trim()) || hasWarmupContent(plan.warmups);
  }
  const hasAttempts = (plan.scenarios ?? []).some((s) =>
    LIFTS.some((lift) =>
      ATTEMPT_KEYS.some((key) => {
        const v = s.lifts?.[lift]?.[key];
        return v !== null && v !== '' && v !== undefined;
      }),
    ),
  );
  return hasAttempts || hasWarmupContent(plan.warmups);
}
