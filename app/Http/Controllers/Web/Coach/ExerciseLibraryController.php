<?php

namespace App\Http\Controllers\Web\Coach;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCustomExerciseRequest;
use App\Http\Requests\UpdateCustomExerciseRequest;
use App\Models\Exercise;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ExerciseLibraryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Exercise::query()
            ->when(
                $request->user()?->role === 'coach',
                fn ($builder) => $builder->forCoach($request->user()),
            )
            ->with('variants')
            ->orderBy('name');

        if ($request->filled('lift')) {
            $lift = $request->string('lift')->toString();
            $query->where(function ($builder) use ($lift): void {
                $builder->where('lift', $lift)
                    ->orWhere('lift', Exercise::LIFT_GENERAL);
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->string('category')->toString());
        }

        if ($request->filled('equipment')) {
            $query->where('equipment', $request->string('equipment')->toString());
        }

        if ($request->boolean('custom_only')) {
            $query->where('is_custom', true)->where('coach_id', $request->user()?->id);
        }

        if ($request->filled('q')) {
            $term = '%'.$request->string('q')->toString().'%';
            $query->where(function ($builder) use ($term): void {
                $builder->where('name', 'like', $term)
                    ->orWhereHas('variants', fn ($variantQuery) => $variantQuery->where('name', 'like', $term));
            });
        }

        return response()->json($query->get());
    }

    public function store(StoreCustomExerciseRequest $request): RedirectResponse|JsonResponse
    {
        $coach = $request->user();
        $validated = $request->validated();
        $slug = $this->uniqueSlugForCoach($coach->id, $validated['name']);

        $exercise = Exercise::query()->create([
            'coach_id' => $coach->id,
            'is_custom' => true,
            'name' => $validated['name'],
            'slug' => $slug,
            'lift' => $validated['lift'],
            'category' => $validated['category'],
            'equipment' => $validated['equipment'],
            'movement_pattern' => $validated['movement_pattern'] ?? null,
        ]);

        if ($request->wantsJson()) {
            return response()->json($exercise->load('variants'), 201);
        }

        return back()->with('success', 'Exercice personnalisé ajouté.');
    }

    public function update(UpdateCustomExerciseRequest $request, Exercise $exercise): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();

        if (isset($validated['name']) && $validated['name'] !== $exercise->name) {
            $validated['slug'] = $this->uniqueSlugForCoach(
                $exercise->coach_id,
                $validated['name'],
                $exercise->id,
            );
        }

        $exercise->update($validated);

        if ($request->wantsJson()) {
            return response()->json($exercise->fresh('variants'));
        }

        return back()->with('success', 'Exercice mis à jour.');
    }

    public function destroy(Request $request, Exercise $exercise): RedirectResponse|JsonResponse
    {
        $this->authorize('delete', $exercise);

        $exercise->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Exercice supprimé.']);
        }

        return back()->with('success', 'Exercice supprimé.');
    }

    private function uniqueSlugForCoach(int $coachId, string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name) ?: 'exercice';
        $slug = $baseSlug;
        $suffix = 1;

        while (Exercise::query()
            ->where('coach_id', $coachId)
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = $baseSlug.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
