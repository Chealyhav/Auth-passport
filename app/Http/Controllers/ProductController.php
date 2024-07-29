<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('perPage', 15);

            $products = DB::table('products')
                ->leftJoin('product_category', 'products.id', '=', 'product_category.product_id')
                ->leftJoin('categories', 'product_category.category_id', '=', 'categories.id')
                ->leftJoin('product_menutag', 'products.id', '=', 'product_menutag.product_id')
                ->leftJoin('menu_tags', 'product_menutag.menutag_id', '=', 'menu_tags.id')
                ->select(
                    'products.id',
                    'products.name',
                    'products.image',
                    'products.price',
                    'products.description',
                    'products.created_at',
                    'products.updated_at'
                )
                ->orderBy('products.id', 'desc')
                ->paginate($perPage);

            $products->getCollection()->transform(function ($product) {
                // Categories
                $categories = DB::table('categories')
                    ->join('product_category', 'categories.id', '=', 'product_category.category_id')
                    ->where('product_category.product_id', $product->id)
                    ->select(
                        'categories.id',
                        'categories.name',
                        'categories.des as description',
                        'categories.created_at',
                        'categories.updated_at'
                    )
                    ->get()
                    ->map(function ($category) {
                        return [
                            'id' => $category->id,
                            'name' => $category->name,
                            'description' => $category->description,
                            'created_at' => $category->created_at,
                            'updated_at' => $category->updated_at,
                        ];
                    });

                $product->categories = $categories;

                // Menu Tags
                $menutags = DB::table('menu_tags')
                    ->join('product_menutag', 'menu_tags.id', '=', 'product_menutag.menutag_id')
                    ->where('product_menutag.product_id', $product->id)
                    ->select(
                        'menu_tags.id',
                        'menu_tags.name',
                        'menu_tags.des as description',
                        'menu_tags.created_at',
                        'menu_tags.updated_at'
                    )
                    ->get()
                    ->map(function ($menutag) {
                        return [
                            'id' => $menutag->id,
                            'name' => $menutag->name,
                            'description' => $menutag->description,
                            'created_at' => $menutag->created_at,
                            'updated_at' => $menutag->updated_at,
                        ];
                    });

                $product->menutags = $menutags;

                return $product;
            });

            return response()->json($products, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function create(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'required|string|max:255',
                'price' => 'required|numeric',
                'quantity' => 'required|integer',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'categories' => 'required|array',
                'categories.*' => 'required|exists:categories,id',
                'menutags' => 'nullable|array',
                'menutags.*' => 'nullable|exists:menu_tags,id',
            ]);

            $image = null;
            if ($request->hasFile('image')) {
                $image = Str::random(32).'.'.$request->image->getClientOriginalExtension();
                $request->image->move(public_path('product'), $image);
            }

            $productId = DB::table('products')->insertGetId([
                'name' => $request->name,
                'description' => $request->description,
                'price' => $request->price,
                'quantity' => $request->quantity,
                'created_at' => now(),
                'updated_at' => now(),
                'image' => $image,
            ]);

            $categories = array_map(function ($categoryId) use ($productId) {
                return [
                    'product_id' => $productId,
                    'category_id' => $categoryId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }, $request->categories);

            DB::table('product_category')->insert($categories);

            $menutags = array_map(function ($menutagId) use ($productId) {
                return [
                    'product_id' => $productId,
                    'menutag_id' => $menutagId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }, $request->menutags);

            DB::table('product_menutag')->insert($menutags);

            $product = DB::table('products')->where('id', $productId)->first();

            $categories = DB::table('categories')
                ->join('product_category', 'categories.id', '=', 'product_category.category_id')
                ->where('product_category.product_id', $productId)
                ->select(
                    'categories.id',
                    'categories.name',
                    'categories.des as description',
                    'categories.created_at',
                    'categories.updated_at'
                )
                ->get()
                ->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'description' => $category->description,
                        'created_at' => $category->created_at,
                        'updated_at' => $category->updated_at,
                    ];
                });

            $product->categories = $categories;

            $menutags = DB::table('menu_tags')
                ->join('product_menutag', 'menu_tags.id', '=', 'product_menutag.menutag_id')
                ->where('product_menutag.product_id', $productId)
                ->select(
                    'menu_tags.id',
                    'menu_tags.name',
                    'menu_tags.des as description',
                    'menu_tags.created_at',
                    'menu_tags.updated_at'
                )
                ->get()
                ->map(function ($menutag) {
                    return [
                        'id' => $menutag->id,
                        'name' => $menutag->name,
                        'description' => $menutag->description,
                        'created_at' => $menutag->created_at,
                        'updated_at' => $menutag->updated_at,
                    ];
                });

            $product->menutags = $menutags;

            return response()->json(['message' => 'Success', 'data' => $product], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $product = DB::table('products')
                ->leftJoin('product_category', 'products.id', '=', 'product_category.product_id')
                ->leftJoin('categories', 'product_category.category_id', '=', 'categories.id')
                ->leftJoin('product_menutag', 'products.id', '=', 'product_menutag.product_id')
                ->leftJoin('menu_tags', 'product_menutag.menutag_id', '=', 'menu_tags.id')
                ->select(
                    'products.id',
                    'products.name',
                    'products.image',
                    'products.price',
                    'products.description',
                    'products.created_at',
                    'products.updated_at'
                )
                ->where('products.id', $id)
                ->first();

            if (!$product) {
                return response()->json(['message' => 'Data not found'], 404);
            }

            $categories = DB::table('categories')
                ->join('product_category', 'categories.id', '=', 'product_category.category_id')
                ->where('product_category.product_id', $id)
                ->select(
                    'categories.id',
                    'categories.name',
                    'categories.des as description',
                    'categories.created_at',
                    'categories.updated_at'
                )
                ->get()
                ->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'description' => $category->description,
                        'created_at' => $category->created_at,
                        'updated_at' => $category->updated_at,
                    ];
                });

            $menutags = DB::table('menu_tags')
                ->join('product_menutag', 'menu_tags.id', '=', 'product_menutag.menutag_id')
                ->where('product_menutag.product_id', $id)
                ->select(
                    'menu_tags.id',
                    'menu_tags.name',
                    'menu_tags.des as description',
                    'menu_tags.created_at',
                    'menu_tags.updated_at'
                )
                ->get()
                ->map(function ($menutag) {
                    return [
                        'id' => $menutag->id,
                        'name' => $menutag->name,
                        'description' => $menutag->description,
                        'created_at' => $menutag->created_at,
                        'updated_at' => $menutag->updated_at,
                    ];
                });

            $product->categories = $categories;
            $product->menutags = $menutags;

            return response()->json($product, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'required|string|max:255',
                'price' => 'required|numeric',
                'quantity' => 'required|integer',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'categories' => 'required|array',
                'categories.*' => 'required|exists:categories,id',
                'menutags' => 'nullable|array',
                'menutags.*' => 'nullable|exists:menu_tags,id',
            ]);

            $image = null;
            if ($request->hasFile('image')) {
                $image = Str::random(32).'.'.$request->image->getClientOriginalExtension();
                $request->image->move(public_path('uploads/products'), $image);
            }

            DB::table('products')
                ->where('id', $id)
                ->update([
                    'name' => $request->name,
                    'description' => $request->description,
                    'price' => $request->price,
                    'quantity' => $request->quantity,
                    'updated_at' => now(),
                    'image' => $image ?? DB::table('products')->where('id', $id)->value('image'),
                ]);

            DB::table('product_category')->where('product_id', $id)->delete();
            $categories = array_map(function ($categoryId) use ($id) {
                return [
                    'product_id' => $id,
                    'category_id' => $categoryId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }, $request->categories);

            DB::table('product_category')->insert($categories);

            DB::table('product_menutag')->where('product_id', $id)->delete();
            if ($request->has('menutags')) {
                $menutags = array_map(function ($menutagId) use ($id) {
                    return [
                        'product_id' => $id,
                        'menutag_id' => $menutagId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }, $request->menutags);

                DB::table('product_menutag')->insert($menutags);
            }

            $product = DB::table('products')->where('id', $id)->first();

            $categories = DB::table('categories')
                ->join('product_category', 'categories.id', '=', 'product_category.category_id')
                ->where('product_category.product_id', $id)
                ->select(
                    'categories.id',
                    'categories.name',
                    'categories.des as description',
                    'categories.created_at',
                    'categories.updated_at'
                )
                ->get()
                ->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'description' => $category->description,
                        'created_at' => $category->created_at,
                        'updated_at' => $category->updated_at,
                    ];
                });

            $product->categories = $categories;

            $menutags = DB::table('menu_tags')
                ->join('product_menutag', 'menu_tags.id', '=', 'product_menutag.menutag_id')
                ->where('product_menutag.product_id', $id)
                ->select(
                    'menu_tags.id',
                    'menu_tags.name',
                    'menu_tags.des as description',
                    'menu_tags.created_at',
                    'menu_tags.updated_at'
                )
                ->get()
                ->map(function ($menutag) {
                    return [
                        'id' => $menutag->id,
                        'name' => $menutag->name,
                        'description' => $menutag->description,
                        'created_at' => $menutag->created_at,
                        'updated_at' => $menutag->updated_at,
                    ];
                });

            $product->menutags = $menutags;

            return response()->json(['message' => 'Success', 'data' => $product], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $product = DB::table('products')->where('id', $id)->first();
            if (!$product) {
                return response()->json(['message' => 'Data not found'], 404);
            }

            DB::table('product_category')->where('product_id', $id)->delete();
            DB::table('product_menutag')->where('product_id', $id)->delete();
            DB::table('products')->where('id', $id)->delete();

            return response()->json(['message' => 'Successfully deleted'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
