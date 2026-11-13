<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Icd10Code;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class Icd10ApiController extends Controller
{
    /**
     * Search ICD-10 codes for real-time autocomplete
     */
    public function search(Request $request): JsonResponse
    {
        $query = trim($request->input('q', ''));

        $queryBuilder = Icd10Code::query();

        if (strlen($query) > 0) {
            $queryBuilder->where(function ($q) use ($query) {
                $q->where('code', 'like', "{$query}%")
                    ->orWhere('name_id', 'like', "%{$query}%")
                    ->orWhere('name_en', 'like', "%{$query}%");
            });
        } else {
            // Default top common primary diseases
            $queryBuilder->where('is_common', true);
        }

        $results = $queryBuilder->orderBy('is_common', 'desc')
            ->orderBy('code', 'asc')
            ->take(15)
            ->get(['code', 'name_id', 'name_en', 'category']);

        return response()->json($results);
    }
}

