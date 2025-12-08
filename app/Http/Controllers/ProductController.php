<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    // =======================================================
    // LIST PRODUCTS (with filters + search + pagination)
    // =======================================================
    public function index(Request $request)
    {
        $query = Product::with('category');

        // Filter by category name
        if ($request->has('category') && $request->category !== 'all') {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('name', $request->category);
            });
        }

        // Search by product name
        if ($request->has('search') && $request->search !== '') {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        return response()->json($query->paginate(10));
    }

    // =======================================================
    // SHOW SINGLE PRODUCT
    // =======================================================
    public function show($id)
    {
        return response()->json(
            Product::with('category')->findOrFail($id)
        );
    }

    // =======================================================
    // CREATE PRODUCT (with IMAGE upload)
    // =======================================================
    public function store(Request $request)
    {
        // Validation
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'description'   => 'nullable|string',
            'base_price'    => 'required|numeric|min:0',
            'category_id'   => 'required|exists:categories,id',
            'is_available'  => 'boolean',
            'image'         => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
        ]);

        // Handle product image upload
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('product_images', 'public');
            $validated['image_url'] = asset('storage/' . $path);
        }

        // Save product
        $product = Product::create($validated);

        return response()->json([
            'message' => 'Product created successfully!',
            'product' => $product
        ], 201);
    }

    // =======================================================
    // UPDATE PRODUCT (with image replace)
    // =======================================================
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        // Validation
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'description'   => 'nullable|string',
            'base_price'    => 'required|numeric|min:0',
            'category_id'   => 'required|exists:categories,id',
            'is_available'  => 'boolean',
            'image'         => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
        ]);

        // Replace image if new one is uploaded
        if ($request->hasFile('image')) {

            // delete old image if exists
            if ($product->image_url) {
                $oldPath = str_replace(url('storage') . '/', '', $product->image_url);
                Storage::disk('public')->delete($oldPath);
            }

            // store new image
            $path = $request->file('image')->store('product_images', 'public');
            $validated['image_url'] = asset('storage/' . $path);
        }

        $product->update($validated);

        return response()->json([
            'message' => 'Product updated successfully!',
            'product' => $product
        ]);
    }

    // =======================================================
    // DELETE PRODUCT (also delete image)
    // =======================================================
    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        if ($product->image_url) {
            $oldPath = str_replace(url('storage') . '/', '', $product->image_url);
            Storage::disk('public')->delete($oldPath);
        }

        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully'
        ]);
    }
}
