export function programSessionVisitOptions(overrides = {}) {
  return {
    preserveScroll: true,
    preserveState: true,
    only: ['activeBlock'],
    ...overrides,
  };
}
