<?php

namespace App\Http\Controllers\Web\Coach;

use App\Http\Controllers\Controller;
use App\Models\Exercise;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExerciseLibraryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Exercise::query()->with('variants')->orderBy('name');

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

        if ($request->filled('q')) {
            $term = '%'.$request->string('q')->toString().'%';
            $query->where(function ($builder) use ($term): void {
                $builder->where('name', 'like', $term)
                    ->orWhereHas('variants', fn ($variantQuery) => $variantQuery->where('name', 'like', $term));
            });
        }

        return response()->json($query->get());
    }
}
