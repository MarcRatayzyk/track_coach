function dateKey(value) {
  if (!value) {
    return null;
  }

  const match = String(value).slice(0, 10).match(/^(\d{4}-\d{2}-\d{2})$/);
  return match ? match[1] : null;
}

function emptySeries() {
  return {
    squat: [],
    bench: [],
    deadlift: [],
    total: [],
  };
}

function sortByDate(records) {
  return [...records].sort((a, b) => a.date.localeCompare(b.date));
}

function normalizeRecords(personalRecords = []) {
  return sortByDate(
    personalRecords
      .map((record) => ({
        date: dateKey(record.reference_date),
        squat: Number(record.squat ?? 0),
        bench: Number(record.bench ?? 0),
        deadlift: Number(record.deadlift ?? 0),
      }))
      .filter((record) => record.date),
  );
}

function liftValue(record, key) {
  if (key === 'total') {
    return (
      Number(record.squat ?? 0) + Number(record.bench ?? 0) + Number(record.deadlift ?? 0)
    );
  }

  return Number(record[key] ?? 0);
}

function recordsToSeries(records, key) {
  return records.map((record) => ({
    date: record.date,
    value: liftValue(record, key),
  }));
}

export function buildPrEvolutionSeries({ personalRecords = [] }) {
  const chartRecords = normalizeRecords(personalRecords);

  if (!chartRecords.length) {
    return emptySeries();
  }

  return {
    squat: recordsToSeries(chartRecords, 'squat'),
    bench: recordsToSeries(chartRecords, 'bench'),
    deadlift: recordsToSeries(chartRecords, 'deadlift'),
    total: recordsToSeries(chartRecords, 'total'),
  };
}

export function currentValueFromSeries(series) {
  if (!series?.length) {
    return 0;
  }

  return series[series.length - 1]?.value ?? 0;
}

export function seriesGain(series) {
  if (series.length < 2) {
    return 0;
  }

  const first = series[0];
  const last = series[series.length - 1];
  return Number(last.value ?? 0) - Number(first.value ?? 0);
}
