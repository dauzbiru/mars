<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Result;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::whereNull('parent_id')->withCount('items', 'children')->withSum('items', 'bobot')->orderBy('sort')->get();
        return view('categories.index', compact('categories'));
    }

    public function show(Category $category)
    {
        $children = $category->children()->withCount('items', 'children')->get();
        $items = $category->items()->with('criteria')->get();

        return view('categories.show', compact('category', 'children', 'items'));
    }

    public function create()
    {
        $parentId = request('parent_id');
        $parent = $parentId ? Category::findOrFail($parentId) : null;
        return view('categories.create', compact('parent'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id',
        ]);
        $data['sort'] = 0;

        Category::create($data);
        Cache::forget('monitoring.all_items');
        Cache::forget('monitoring.all_categories');

        $parentId = $data['parent_id'] ?? null;
        $redirect = $parentId
            ? "/categories/$parentId"
            : '/categories';

        return redirect($redirect)->with('success', 'Tugas berhasil ditambahkan.');
    }

    public function edit(Category $category)
    {
        return view('categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        $category->update($request->validate([
            'name' => 'required|string|max:255',
        ]));
        Cache::forget('monitoring.all_items');
        Cache::forget('monitoring.all_categories');

        return redirect("/categories/$category->id")->with('success', 'Tugas berhasil diperbarui.');
    }

    public function reorder(Request $request)
    {
        $ids = $request->input('items', []);
        foreach ($ids as $i => $id) {
            Category::where('id', $id)->update(['sort' => $i + 1]);
        }
        Cache::forget('monitoring.all_categories');
        return response()->json(['ok' => true]);
    }

    public function destroy(Category $category)
    {
        $parentId = $category->parent_id;
        $category->delete();
        Cache::forget('monitoring.all_items');
        Cache::forget('monitoring.all_categories');

        $redirect = $parentId ? "/categories/$parentId" : '/categories';

        return redirect($redirect)->with('success', 'Tugas berhasil dihapus.');
    }
}
