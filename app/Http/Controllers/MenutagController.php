<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MenutagController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $menutags = DB::table('menu_tags')
                ->select(
                    'menu_tags.id',
                    'menu_tags.name',
                    'menu_tags.des',
                    'menu_tags.created_at',
                    'menu_tags.updated_at',
                )
                ->orderBy('menu_tags.id', 'desc')
                ->paginate(15);

            return response()->json([
                'menutags' => $menutags,
                'message' => 'Menutags retrieved successfully',
            ]);
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
                'name' => 'required',
                'des' => 'required',
            ]);

            $menutag = DB::table('menu_tags')->insert([
                'name' => $request->name,
                'des' => $request->des,
            ]);

            return response()->json([
                'message' => 'Menutag created successfully',
                'menutag' => $menutag,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
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
