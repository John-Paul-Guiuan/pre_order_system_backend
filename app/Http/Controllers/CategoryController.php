<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Get all categories
     * Auto-creates default categories if none exist
     */
    public function index()
    {
        // If no categories exist, create default ones
        if (Category::count() === 0) {
            $defaultCategories = [
                ['name' => 'Pastry'],
                ['name' => 'Bread'],
                ['name' => 'Cake'],
            ];

            foreach ($defaultCategories as $category) {
                Category::firstOrCreate(
                    ['name' => $category['name']],
                    $category
                );
            }
        }

        return response()->json(Category::all());
    }

    /**
     * Find or create a category by name
     */
    public function findOrCreate(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255'
        ]);

        $category = Category::firstOrCreate(
            ['name' => $request->name],
            ['name' => $request->name]
        );

        return response()->json($category);
    }
}

