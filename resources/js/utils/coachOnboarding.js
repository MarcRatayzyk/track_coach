const ONBOARDING_KEY = 'tc_coach_onboarding_done';

export function isCoachOnboardingDone() {
  if (typeof window === 'undefined') {
    return true;
  }
  return localStorage.getItem(ONBOARDING_KEY) === '1';
}

export function markCoachOnboardingDone() {
  if (typeof window !== 'undefined') {
    localStorage.setItem(ONBOARDING_KEY, '1');
  }
}
