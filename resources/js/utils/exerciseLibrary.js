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
