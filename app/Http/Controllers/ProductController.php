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
        // Validate input
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|integer|exists:categories,id',
            'base_price' => 'required|numeric|min:0',
            'is_available' => 'required|boolean',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);

        try {
            // Handle image upload
            $imageUrl = null;
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('products', $filename, 'public');
                
                // Store only the relative path, not the full URL
                $imageUrl = 'storage/products/' . $filename;
                
                \Log::info('Image stored successfully', [
                    'filename' => $filename,
                    'stored_path' => $imageUrl
                ]);
            }

            // Create product
            $product = Product::create([
                'name' => $validated['name'],
                'category_id' => $validated['category_id'],
                'base_price' => $validated['base_price'],
                'is_available' => $validated['is_available'],
                'description' => $validated['description'] ?? null,
                'image_url' => $imageUrl,
            ]);

            \Log::info('Product created successfully', [
                'id' => $product->id,
                'name' => $product->name,
                'image_url' => $product->image_url
            ]);

            return response()->json([
                'message' => 'Product created successfully',
                'data' => $product->load('category')
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Product creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Failed to create product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // =======================================================
    // UPDATE PRODUCT (with image replace)
    // =======================================================
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|integer|exists:categories,id',
            'base_price' => 'required|numeric|min:0',
            'is_available' => 'required',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);

        // Handle image upload if present
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($product->image_url && strpos($product->image_url, 'storage/') === 0) {
                $oldPath = str_replace('storage/', '', $product->image_url);
                \Illuminate\Support\Facades\Storage::disk('public')->delete($oldPath);
            }

            // Store new image
            $file = $request->file('image');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('products', $filename, 'public');
            
            // Store only the relative path
            $imageUrl = 'storage/products/' . $filename;

            \Log::info('Product image updated', [
                'product_id' => $product->id,
                'new_path' => $imageUrl
            ]);
        }

        // Update product fields
        $product->name = $validated['name'];
        $product->category_id = $validated['category_id'];
        $product->base_price = $validated['base_price'];
        $product->is_available = ($validated['is_available'] == "1" || $validated['is_available'] == 1) ? 1 : 0;
        $product->description = $validated['description'] ?? null;
        $product->image_url = $imageUrl ?? $product->image_url;

        $product->save();

        return response()->json([
            'message' => 'Product updated successfully',
            'data' => $product->fresh('category')
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
