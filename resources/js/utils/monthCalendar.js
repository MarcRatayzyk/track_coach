import { buildCalendarRows, isoWeekdayFromDate, WEEKDAY_LABELS } from './programBuilder';

function formatYmd(date) {
  const y = date.getFullYear();
  const m = String(date.getMonth() + 1).padStart(2, '0');
  const d = String(date.getDate()).padStart(2, '0');
  return `${y}-${m}-${d}`;
}

function parseYmd(key) {
  if (!key) {
    return null;
  }
  const [y, mo, d] = String(key).split('-').map(Number);
  if (!y || !mo || !d) {
    return null;
  }
  return new Date(y, mo - 1, d);
}

function addMonths(date, count) {
  const d = new Date(date.getFullYear(), date.getMonth(), date.getDate());
  d.setMonth(d.getMonth() + count);
  return d;
}

function startOfMonth(date) {
  return new Date(date.getFullYear(), date.getMonth(), 1);
}

function endOfMonth(date) {
  return new Date(date.getFullYear(), date.getMonth() + 1, 0);
}

function startOfIsoWeekFromDate(date) {
  const monday = new Date(date.getFullYear(), date.getMonth(), date.getDate());
  monday.setDate(monday.getDate() - (isoWeekdayFromDate(date) - 1));
  return monday;
}

function addDays(date, count) {
  const d = new Date(date.getFullYear(), date.getMonth(), date.getDate());
  d.setDate(d.getDate() + count);
  return d;
}

function stripTime(date) {
  return new Date(date.getFullYear(), date.getMonth(), date.getDate());
}

function isWithinRange(date, startDate, endDate) {
  const value = stripTime(date).getTime();
  return value >= stripTime(startDate).getTime() && value <= stripTime(endDate).getTime();
}

export function defaultCalendarRange() {
  const now = new Date();

  return {
    start: startOfMonth(addMonths(now, -4)),
    end: endOfMonth(addMonths(now, 2)),
  };
}

function monthHeaderLabel(year, month) {
  const d = new Date(year, month, 1);
  return d
    .toLocaleDateString('fr-FR', { month: 'short' })
    .replace('.', '')
    .toUpperCase()
    .slice(0, 4);
}

function rangeLabel(startDate, endDate) {
  const start = startDate.toLocaleDateString('fr-FR', { month: 'short', year: 'numeric' });
  const end = endDate.toLocaleDateString('fr-FR', { month: 'short', year: 'numeric' });
  return `${start} – ${end}`;
}

function buildMonthIndexLookup(startDate, endDate) {
  const lookup = new Map();
  let index = 0;
  let year = startDate.getFullYear();
  let month = startDate.getMonth();
  const endYear = endDate.getFullYear();
  const endMonth = endDate.getMonth();

  while (year < endYear || (year === endYear && month <= endMonth)) {
    lookup.set(`${year}-${month}`, index);
    index += 1;
    month += 1;
    if (month > 11) {
      month = 0;
      year += 1;
    }
  }

  return lookup;
}

function monthIndexForDate(lookup, year, month) {
  return lookup.get(`${year}-${month}`) ?? 0;
}

function weekHeaderMonth(weekCells) {
  const monday = weekCells[0];
  if (monday?.inRange && monday.date) {
    return { year: monday.year, month: monday.month };
  }

  const inRange = weekCells.filter((cell) => cell.inRange && cell.date);
  if (!inRange.length) {
    return null;
  }

  return { year: inRange[0].year, month: inRange[0].month };
}

function programSessionHasContent(session) {
  if (!session) {
    return false;
  }
  return (session.exercises ?? []).length > 0 || String(session.session_label ?? '').trim() !== '';
}

export function buildTrainingYearGrid(startDate, endDate) {
  const gridStart = startOfIsoWeekFromDate(startDate);
  const gridEnd = addDays(startOfIsoWeekFromDate(endDate), 6);
  const monthIndexLookup = buildMonthIndexLookup(startDate, endDate);

  const weekColumns = [];
  let currentMonday = gridStart;

  while (currentMonday.getTime() <= gridEnd.getTime()) {
    const cells = [];

    for (let weekday = 1; weekday <= 7; weekday++) {
      const date = addDays(currentMonday, weekday - 1);
      const inRange = isWithinRange(date, startDate, endDate);

      cells.push({
        date: inRange ? formatYmd(date) : null,
        dayNumber: inRange ? date.getDate() : null,
        year: date.getFullYear(),
        month: date.getMonth(),
        inRange,
        monthIndex: monthIndexForDate(monthIndexLookup, date.getFullYear(), date.getMonth()),
      });
    }

    weekColumns.push({ cells });
    currentMonday = addDays(currentMonday, 7);
  }

  const monthHeaders = [];
  let groupStart = 0;
  let currentKey = null;
  let currentMonth = null;

  for (let index = 0; index < weekColumns.length; index++) {
    const month = weekHeaderMonth(weekColumns[index].cells);
    const key = month ? `${month.year}-${month.month}` : 'none';

    if (key !== currentKey) {
      if (currentKey !== null && currentMonth) {
        monthHeaders.push({
          label: monthHeaderLabel(currentMonth.year, currentMonth.month),
          colSpan: index - groupStart,
          monthIndex: monthIndexForDate(monthIndexLookup, currentMonth.year, currentMonth.month),
          year: currentMonth.year,
          month: currentMonth.month,
        });
      }
      groupStart = index;
      currentKey = key;
      currentMonth = month;
    }
  }

  if (currentKey !== null && currentMonth) {
    monthHeaders.push({
      label: monthHeaderLabel(currentMonth.year, currentMonth.month),
      colSpan: weekColumns.length - groupStart,
      monthIndex: monthIndexForDate(monthIndexLookup, currentMonth.year, currentMonth.month),
      year: currentMonth.year,
      month: currentMonth.month,
    });
  }

  const rows = WEEKDAY_LABELS.map((label, weekdayIndex) => ({
    weekday: weekdayIndex + 1,
    label,
    cells: weekColumns.map((week) => week.cells[weekdayIndex]),
  }));

  return {
    rangeLabel: rangeLabel(startDate, endDate),
    monthHeaders,
    rows,
    weekCount: weekColumns.length,
  };
}

export function indexProgramSessionsByDate(programBlock) {
  const map = {};

  if (!programBlock?.date_start || !programBlock?.week_count) {
    return map;
  }

  const rows = buildCalendarRows(
    programBlock.week_count,
    programBlock.date_start,
    programBlock.sessions ?? {},
  );

  for (const row of rows) {
    for (const cell of row.cells) {
      const session = programBlock.sessions?.[cell.key];
      if (cell.date && programSessionHasContent(session)) {
        map[cell.date] = session;
      }
    }
  }

  return map;
}

export function indexTrainingSessionsByDate(sessions = []) {
  const map = {};

  for (const session of sessions) {
    const match = String(session.session_date ?? '').match(/^(\d{4}-\d{2}-\d{2})/);
    const key = match?.[1];
    if (!key) {
      continue;
    }
    if (!map[key]) {
      map[key] = [];
    }
    map[key].push(session);
  }

  return map;
}

export function indexCompetitionsByDate(competitions = []) {
  const map = {};

  for (const competition of competitions) {
    const match = String(competition.competition_date ?? '').match(/^(\d{4}-\d{2}-\d{2})/);
    const key = match?.[1];
    if (key) {
      map[key] = competition;
    }
  }

  return map;
}

export function buildTrainingYearGridFromProgramBlock() {
  const { start, end } = defaultCalendarRange();
  return buildTrainingYearGrid(start, end);
}
