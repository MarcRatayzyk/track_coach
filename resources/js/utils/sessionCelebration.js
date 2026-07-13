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

function actualLinesFromWorkItems(workItems = []) {
  return workItems
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
  const topsetItem = workItems.find((item) => item.section === 'topset');
  const topsetLine = topsetItem?.line;
  const topsetSubtitle = topsetLine
    ? formatTopsetCelebration(topsetLine, oneRm, mainLift)
    : 'SÉANCE TERMINÉE';

  const topsetLoadKg = resolveTopsetLoadKg(topsetLine, oneRm, mainLift);
  const barbell = topsetLoadKg ? buildBarbellLoading(topsetLoadKg, barWeightKg) : null;

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
    tonnage: Math.round(tonnage),
    tonnageLabel,
    totalReps,
    repsLabel,
    adherence,
    adherenceLabel,
    shareText,
  };
}
