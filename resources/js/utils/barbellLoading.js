// Spécifications IPF (diamètre / épaisseur en mm), mises à l'échelle en px pour l'UI.
// Référence user:
// - Barre: 2.2 m, manchons Ø51 mm, shaft Ø28 mm
// - Disques:
//   25: Ø450 / 27 (rouge)
//   20: Ø450 / 22.5 (bleu)
//   15: Ø400 / 22 (jaune)
//   10: Ø350 / 22 (vert)
//    5: Ø225 / 21.5 (blanc)
//  2.5: Ø190 / 16 (noir)
// 1.25: Ø160 / 12 (argent)
//  0.5: Ø134 / 8 (argent)
// 0.25: Ø112 / 6 (argent)
const PLATE_MM = [
  { weight: 25, color: 'red', diameterMm: 450, thicknessMm: 27 },
  { weight: 20, color: 'blue', diameterMm: 450, thicknessMm: 22.5 },
  { weight: 15, color: 'yellow', diameterMm: 400, thicknessMm: 22 },
  { weight: 10, color: 'green', diameterMm: 350, thicknessMm: 22 },
  { weight: 5, color: 'white', diameterMm: 225, thicknessMm: 21.5 },
  { weight: 2.5, color: 'black', diameterMm: 190, thicknessMm: 16 },
  { weight: 1.25, color: 'silver', diameterMm: 160, thicknessMm: 12 },
  { weight: 0.5, color: 'silver', diameterMm: 134, thicknessMm: 8 },
  { weight: 0.25, color: 'silver', diameterMm: 112, thicknessMm: 6 },
];

const MAX_DIAMETER_MM = 450;
const MAX_PLATE_HEIGHT_PX = 64; // ≈ l'ancien rendu, mais désormais proportionnel.
const HEIGHT_SCALE = MAX_PLATE_HEIGHT_PX / MAX_DIAMETER_MM;

// Épaisseur en px: contrainte UI (évite des disques trop "épais" visuellement).
const THICKNESS_SCALE = 0.45; // px/mm (27mm -> ~12px)
const MIN_PLATE_WIDTH_PX = 4;

export const BARBELL_PLATE_SPECS = PLATE_MM.map((plate) => ({
  weight: plate.weight,
  color: plate.color,
  height: Math.max(10, Math.round(plate.diameterMm * HEIGHT_SCALE)),
  width: Math.max(MIN_PLATE_WIDTH_PX, Math.round(plate.thicknessMm * THICKNESS_SCALE)),
}));

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
