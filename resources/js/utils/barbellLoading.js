export const BARBELL_PLATE_SPECS = [
  { weight: 25, color: 'red', height: 64, width: 14 },
  { weight: 20, color: 'blue', height: 56, width: 12 },
  { weight: 15, color: 'yellow', height: 48, width: 12 },
  { weight: 10, color: 'green', height: 40, width: 10 },
  { weight: 5, color: 'white', height: 32, width: 8 },
  { weight: 2.5, color: 'black', height: 24, width: 6 },
  { weight: 1.25, color: 'grey', height: 20, width: 6 },
];

const PLATE_BY_WEIGHT = new Map(BARBELL_PLATE_SPECS.map((plate) => [plate.weight, plate]));

export function formatBarLoadKg(value) {
  const numeric = Number(value);
  if (!Number.isFinite(numeric) || numeric <= 0) {
    return null;
  }

  const rounded = Math.round(numeric * 4) / 4;
  if (Number.isInteger(rounded)) {
    return String(rounded);
  }

  return rounded.toFixed(1).replace(/\.0$/, '');
}

export function platesPerSide(totalKg, barWeightKg = 20) {
  const total = Number(totalKg);
  if (!Number.isFinite(total) || total <= barWeightKg) {
    return [];
  }

  let remaining = Math.round(((total - barWeightKg) / 2) * 100) / 100;
  const plates = [];

  for (const spec of BARBELL_PLATE_SPECS) {
    while (remaining + 0.001 >= spec.weight) {
      plates.push({ ...spec });
      remaining = Math.round((remaining - spec.weight) * 100) / 100;
    }
  }

  return plates;
}

export function buildBarbellLoading(totalKg, barWeightKg = 20) {
  const loadLabel = formatBarLoadKg(totalKg);
  const sidePlates = platesPerSide(totalKg, barWeightKg);
  const perSideKg = sidePlates.reduce((sum, plate) => sum + plate.weight, 0);

  return {
    totalKg: Number(totalKg),
    loadLabel,
    barWeightKg,
    perSideKg,
    sidePlates,
    plateSummary: summarizePlates(sidePlates),
  };
}

function summarizePlates(plates) {
  const counts = new Map();

  for (const plate of plates) {
    counts.set(plate.weight, (counts.get(plate.weight) ?? 0) + 1);
  }

  return [...counts.entries()]
    .sort((a, b) => b[0] - a[0])
    .map(([weight, count]) => ({
      weight,
      count,
      spec: PLATE_BY_WEIGHT.get(weight) ?? null,
    }));
}
