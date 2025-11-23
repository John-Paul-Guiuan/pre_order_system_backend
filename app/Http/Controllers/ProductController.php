<?php
namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    // List products with optional filters
    public function index(Request $request)
    {
        $query = Product::with('category'); // eager load category

        // Filter by category (bread, cakes, pastries, cookies)
        if ($request->has('category')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('name', $request->category);
            });
        }

        // Search by name
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        return response()->json($query->paginate(10)); // paginated list
    }

    // Show single product
    public function show($id)
    {
        $product = Product::with('category')->findOrFail($id);
        return response()->json($product);
    }

    public function store(Request $request)
{
    // Validate input
    $request->validate([
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'base_price' => 'required|numeric|min:0',
        'category_id' => 'required|exists:categories,id',
        'is_available' => 'boolean',
    ]);

    // Create product
    $product = Product::create([
        'name' => $request->name,
        'description' => $request->description,
        'base_price' => $request->base_price,
        'category_id' => $request->category_id,
        'is_available' => $request->is_available ?? true,
    ]);

    return response()->json([
        'message' => 'Product created successfully!',
        'product' => $product
    ], 201);
}

}
?>