<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClassifyTextRequest;
use App\Services\ClassificationService;
use Illuminate\Http\JsonResponse;

class ClassificationController extends Controller
{
    public function __invoke(ClassifyTextRequest $request, ClassificationService $service): JsonResponse
    {
        $classification = $service->classify($request->validated('text'));

        return response()->json($classification->toArray());
    }
}
