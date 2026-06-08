export const LIFTS = ['squat', 'bench', 'deadlift'];

export const LIFT_LABELS = {
  squat: 'Squat (S)',
  bench: 'Bench (B)',
  deadlift: 'Deadlift (T)',
};

export const ATTEMPT_KEYS = ['attempt1', 'attempt2', 'attempt3'];

export const ATTEMPT_LABELS = {
  attempt1: 'Essai 1',
  attempt2: 'Essai 2',
  attempt3: 'Essai 3',
};

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

export function defaultStructuredPlan() {
  return {
    mode: 'structured',
    scenarios: [emptyScenario('Scénario principal')],
  };
}

export function defaultTextPlan(text = '') {
  return { mode: 'text', text };
}

function nullableWeight(value) {
  if (value === null || value === '' || value === undefined) {
    return null;
  }
  const n = Number(value);
  return Number.isFinite(n) ? n : null;
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

  if (data.mode === 'text') {
    return { mode: 'text', text: data.text ?? '' };
  }

  const scenarios = (data.scenarios ?? []).map(normalizeScenario);
  if (!scenarios.length) {
    scenarios.push(emptyScenario('Scénario principal'));
  }

  return { mode: 'structured', scenarios };
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

export function hasMatchPlanContent(comp) {
  const plan = matchPlanFromCompetition(comp);
  if (plan.mode === 'text') {
    return Boolean(plan.text?.trim());
  }
  return (plan.scenarios ?? []).some((s) =>
    LIFTS.some((lift) =>
      ATTEMPT_KEYS.some((key) => {
        const v = s.lifts?.[lift]?.[key];
        return v !== null && v !== '' && v !== undefined;
      }),
    ),
  );
}
