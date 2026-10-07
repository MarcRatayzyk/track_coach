import { exerciseNameSlug } from './exerciseNames';

export function filterExerciseCatalog(catalog, { category = null, lift = null, equipment = null } = {}) {
  return (catalog ?? []).filter((exercise) => {
    if (category && exercise.category !== category) {
      return false;
    }

    if (lift) {
      if (exercise.lift !== lift && exercise.lift !== 'general') {
        return false;
      }
    }

    if (equipment && exercise.equipment !== equipment) {
      return false;
    }

    return true;
  });
}

export function builtinAccessoryExercises(catalog) {
  return (catalog ?? []).filter((exercise) => exercise.category === 'accessory' && !exercise.is_custom);
}

function normalizeMuscle(value) {
  return String(value ?? '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase();
}

/** Même logique que Exercise::inferAccessoryGroupSlug. */
export function inferAccessoryGroupSlug(exercise) {
  const muscle = normalizeMuscle(exercise?.movement_pattern);
  const lift = exercise?.lift;

  if (muscle.includes('triceps')) {
    return 'triceps';
  }

  if (muscle.includes('epaule') || muscle.includes('shoulder')) {
    return 'epaules';
  }

  if (muscle.includes('dos') || muscle.includes('back') || muscle.includes('biceps')) {
    return 'dos-accessoire';
  }

  if (
    muscle.includes('quadri')
    || muscle.includes('ischio')
    || muscle.includes('fessier')
    || muscle.includes('mollet')
    || muscle.includes('glute')
    || muscle.includes('hamstring')
    || muscle.includes('calf')
  ) {
    return 'jambes-accessoire';
  }

  if (lift === 'squat') {
    return 'jambes-accessoire';
  }

  if (lift === 'deadlift') {
    return 'dos-accessoire';
  }

  if (lift === 'bench') {
    return 'epaules';
  }

  return 'rowing-haltere';
}

export function resolveAccessoryParentId(exercise, groups) {
  const list = groups ?? [];

  if (exercise?.parent_exercise_id != null) {
    const selected = list.find((group) => Number(group.id) === Number(exercise.parent_exercise_id));
    if (selected) {
      return selected.id;
    }
  }

  const slug = inferAccessoryGroupSlug(exercise);
  const match = list.find((group) => exerciseNameSlug(group.slug || group.name) === slug);

  return match?.id ?? null;
}
