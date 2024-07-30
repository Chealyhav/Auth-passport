<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $categories = DB::table('categories')
                ->leftJoin('product_category', 'categories.id', '=', 'product_category.category_id')
                ->leftJoin('products', 'product_category.product_id', '=', 'products.id')
                ->select(
                    'categories.id as category_id',
                    'categories.name as category_name',
                    'categories.des as category_description',
                    'categories.created_at as category_created_at',
                    'categories.updated_at as category_updated_at',
                )
                ->orderBy('categories.id', 'desc')
                ->paginate(15);
            $categories->getCollection()->transform(function ($item) {
                $products = DB::table('products')
                    ->join('product_category', 'products.id', '=', 'product_category.product_id')
                    ->where('product_category.category_id', $item->category_id)
                    ->select(
                        'products.id',
                        'products.name',
                        'products.description',
                        'products.created_at',
                        'products.updated_at'
                    )
                    ->get();

                return [
                    'id' => $item->category_id,
                    'name' => $item->category_name,
                    'description' => $item->category_description,
                    'created_at' => $item->category_created_at,
                    'updated_at' => $item->category_updated_at,
                    'products' => $products,
                ];
            });

            return response()->json($categories, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'des' => 'required|string|max:255',
                'products.*' => 'exists:products,id',
            ]);

            $category = DB::table('categories')->insert([
                'name' => $request->name,
                'des' => $request->des,
            ]);

            // insert product_category
            foreach ($request->products as $product) {
                DB::table('product_category')->insert([
                    'product_id' => $product,
                    'category_id' => $category,
                ]);
            }

            $category->products = DB::table('products')
                ->join('product_category', 'products.id', '=', 'product_category.product_id')
                ->where('product_category.category_id', $category->category_id)
                ->select(
                    'products.id',
                    'products.name',
                    'products.description',
                    'products.created_at',
                    'products.updated_at'
                )
                ->get()
                ->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'description' => $product->description,
                        'created_at' => $product->created_at,
                        'updated_at' => $product->updated_at,
                    ];
                });

            $category->products = $category->products->toArray();

            if ($category) {
                return response()->json([
                    'message' => 'Success',
                    'data' => $category,
                ], 200);
            }

            return response()->json(['message' => 'Success'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error', 'error' => $e], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
    }
}
