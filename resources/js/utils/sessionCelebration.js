import { defaultLiftName } from './programBuilder';
import { buildBarbellLoading, formatBarLoadKg } from './barbellLoading';
import { scoreDayAdherence } from './sessionAdherence';
import { lineVolume, resolveLoadKg } from './trainingVolume';

function resolveTopsetLoadKg(line, oneRm, mainLift) {
  if (!line) {
    return null;
  }

  if (line.load != null && line.load !== '') {
    const direct = Number(line.load);
    if (Number.isFinite(direct) && direct > 0) {
      return direct;
    }
  }

  return resolveLoadKg(line, oneRm, mainLift);
}

export function formatTopsetCelebration(line, oneRm = {}, mainLift = 'squat') {
  if (!line?.exercise_name?.trim() && !line?.lift) {
    return 'TOPSET';
  }

  const name = (line.exercise_name?.trim() || defaultLiftName(line.lift ?? mainLift)).toUpperCase();
  const parts = [name];

  if (line.sets != null && line.reps != null) {
    parts.push(`${line.sets}X${line.reps}`);
  }

  const loadKg = resolveTopsetLoadKg(line, oneRm, mainLift);
  const loadLabel = formatBarLoadKg(loadKg);
  if (loadLabel) {
    parts.push(`${loadLabel}KG`);
  }

  return parts.join(' - ');
}

function pickTopsets(workItems = [], plannedItems = [], oneRm = {}, mainLift = 'squat') {
  const plannedLiftByKey = new Map();
  for (const planned of plannedItems ?? []) {
    const key = String(planned?.exercise_variant_id ?? '') || String(planned?.exercise_name ?? '').trim().toLowerCase();
    if (!key) {
      continue;
    }
    plannedLiftByKey.set(key, planned?.lift ?? planned?.main_lift ?? null);
  }

  const candidates = (workItems ?? [])
    .filter((item) => item?.section === 'topset')
    .map((item) => {
      const line = item?.line ?? null;
      const key = String(line?.exercise_variant_id ?? '') || String(line?.exercise_name ?? '').trim().toLowerCase();
      const liftHint = plannedLiftByKey.get(key) ?? line?.lift ?? mainLift;
      const loadKg = resolveTopsetLoadKg(line, oneRm, mainLift);
      return { item, line, lift: liftHint ?? mainLift, loadKg: loadKg ?? 0 };
    })
    .filter((c) => c.line);

  // 1 topset max par lift, on garde le plus lourd.
  const bestByLift = new Map();
  for (const c of candidates) {
    const lift = c.lift ?? mainLift;
    const best = bestByLift.get(lift);
    if (!best || c.loadKg > best.loadKg) {
      bestByLift.set(lift, c);
    }
  }

  return [...bestByLift.values()]
    .sort((a, b) => b.loadKg - a.loadKg)
    .slice(0, 3)
    .map((c) => ({
      lift: c.lift,
      line: c.line,
      loadKg: c.loadKg || null,
    }));
}

function actualLinesFromWorkItems(workItems = []) {
  return workItems
    .filter((item) => item?.section !== 'warmup')
    .filter((item) => String(item?.line?.exercise_name ?? '').trim())
    .map((item) => ({
      ...item.line,
      section: item.section,
    }));
}

export function buildSessionCelebrationPayload({
  sessionTitle,
  workItems = [],
  plannedItems = [],
  oneRm = {},
  mainLift = 'squat',
  barWeightKg = 20,
}) {
  const selectedTopsets = pickTopsets(workItems, plannedItems, oneRm, mainLift);
  const primaryTopset = selectedTopsets[0] ?? null;
  const topsetLine = primaryTopset?.line ?? null;
  const topsetSubtitle = topsetLine ? formatTopsetCelebration(topsetLine, oneRm, mainLift) : 'SÉANCE TERMINÉE';

  const topsetLoadKg = primaryTopset?.loadKg ?? null;
  const barbell = topsetLoadKg ? buildBarbellLoading(topsetLoadKg, barWeightKg) : null;
  const topsets = selectedTopsets.map((entry) => ({
    lift: entry.lift,
    subtitle: formatTopsetCelebration(entry.line, oneRm, mainLift),
    loadKg: entry.loadKg,
    barbell: entry.loadKg ? buildBarbellLoading(entry.loadKg, barWeightKg) : null,
  }));

  let tonnage = 0;
  let totalReps = 0;

  for (const item of workItems) {
    const volume = lineVolume(item.line, oneRm, mainLift);
    if (Number.isFinite(volume) && volume > 0) {
      tonnage += volume;
    }

    const sets = Number(item.line?.sets ?? 0);
    const reps = Number(item.line?.reps ?? 0);
    if (Number.isFinite(sets) && sets > 0 && Number.isFinite(reps) && reps > 0) {
      totalReps += sets * reps;
    }
  }

  const adherence = scoreDayAdherence(
    plannedItems,
    actualLinesFromWorkItems(workItems),
    oneRm,
    mainLift,
  );

  const tonnageLabel = tonnage > 0 ? `${Math.round(tonnage).toLocaleString('fr-FR')} kg` : '—';
  const repsLabel = totalReps > 0 ? `${totalReps}` : '—';
  const adherenceLabel = adherence.percentage != null ? `${adherence.percentage}%` : '—';

  const shareText = [
    `✅ ${sessionTitle}`,
    topsetSubtitle,
    `Adhérence ${adherenceLabel} · ${tonnageLabel} · ${totalReps > 0 ? `${totalReps} reps` : '—'}`,
    '',
    'Track Coach',
  ].join('\n');

  return {
    sessionTitle,
    topsetSubtitle,
    topsetLoadKg,
    barbell,
    topsets,
    tonnage: Math.round(tonnage),
    tonnageLabel,
    totalReps,
    repsLabel,
    adherence,
    adherenceLabel,
    shareText,
  };
}
