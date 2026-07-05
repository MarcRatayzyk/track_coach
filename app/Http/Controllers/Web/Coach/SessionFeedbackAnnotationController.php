<?php

namespace App\Http\Controllers\Web\Coach;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSessionFeedbackAnnotationRequest;
use App\Models\SessionFeedbackAnnotation;
use App\Models\SessionFeedbackMedia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionFeedbackAnnotationController extends Controller
{
    public function store(
        StoreSessionFeedbackAnnotationRequest $request,
        SessionFeedbackMedia $media,
    ): JsonResponse {
        $annotation = SessionFeedbackAnnotation::query()->create([
            'session_feedback_media_id' => $media->id,
            'coach_id' => $request->user()->id,
            'timestamp_ms' => $request->integer('timestamp_ms'),
            'body' => $request->validated('body'),
            'shapes' => $request->validated('shapes'),
        ]);

        return response()->json($this->present($annotation), 201);
    }

    public function update(Request $request, SessionFeedbackAnnotation $annotation): JsonResponse
    {
        $feedback = $annotation->media?->feedback;
        abort_if($feedback === null || ! $request->user()?->can('annotate', $feedback), 403);
        abort_if($annotation->coach_id !== $request->user()->id, 403);

        $validated = $request->validate([
            'timestamp_ms' => ['sometimes', 'integer', 'min:0'],
            'body' => ['nullable', 'string', 'max:2000'],
            'shapes' => ['nullable', 'array'],
        ]);

        $annotation->update($validated);

        return response()->json($this->present($annotation->fresh()));
    }

    public function destroy(Request $request, SessionFeedbackAnnotation $annotation): JsonResponse
    {
        $feedback = $annotation->media?->feedback;
        abort_if($feedback === null || ! $request->user()?->can('annotate', $feedback), 403);
        abort_if($annotation->coach_id !== $request->user()->id, 403);

        $annotation->delete();

        return response()->json(['message' => 'Annotation supprimée.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(SessionFeedbackAnnotation $annotation): array
    {
        return [
            'id' => $annotation->id,
            'session_feedback_media_id' => $annotation->session_feedback_media_id,
            'timestamp_ms' => $annotation->timestamp_ms,
            'body' => $annotation->body,
            'shapes' => $annotation->shapes ?? [],
            'created_at' => $annotation->created_at?->toIso8601String(),
        ];
    }
}
