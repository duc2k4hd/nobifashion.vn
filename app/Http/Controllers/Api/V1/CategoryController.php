<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Category;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::select('id', 'name', 'slug')
            ->withCount(['posts as posts_count' => fn ($q) => $q->published()])
            ->orderByDesc('posts_count')
            ->get();

        return response()->json([
            'data' => $categories,
        ]);
    }
}


