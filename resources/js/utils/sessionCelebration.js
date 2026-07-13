import { formatLineRecap, formatLineRecapWithKg } from './programBuilder';
import { lineVolume } from './trainingVolume';

export function buildSessionCelebrationPayload({
  sessionTitle,
  workItems = [],
  oneRm = {},
  mainLift = 'squat',
}) {
  const topsetItem = workItems.find((item) => item.section === 'topset');
  const topsetLine = topsetItem?.line;
  const topsetSubtitle = topsetLine
    ? formatLineRecapWithKg(topsetLine, oneRm, mainLift) ?? formatLineRecap(topsetLine) ?? 'Topset'
    : 'Séance terminée';

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

  const tonnageLabel = tonnage > 0 ? `${Math.round(tonnage).toLocaleString('fr-FR')} kg` : '—';
  const repsLabel = totalReps > 0 ? `${totalReps} reps` : '—';
  const shareText = [
    `✅ ${sessionTitle}`,
    topsetSubtitle,
    `${tonnageLabel} · ${repsLabel}`,
    '',
    'Track Coach',
  ].join('\n');

  return {
    sessionTitle,
    topsetSubtitle,
    tonnage: Math.round(tonnage),
    tonnageLabel,
    totalReps,
    repsLabel,
    shareText,
  };
}
