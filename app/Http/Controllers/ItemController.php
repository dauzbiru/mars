<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ItemController extends Controller
{
    public function create(Category $category)
    {
        return view('items.create', compact('category'));
    }

    public function store(Request $request, Category $category)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'bobot' => 'nullable|numeric|min:0',
        ]);

        $maxSort = $category->items()->max('sort') ?? 0;
        $item = $category->items()->create(array_merge($request->only('name', 'bobot'), ['sort' => $maxSort + 1]));
        Cache::forget('monitoring.all_items');
        Cache::forget('monitoring.all_categories');

        $criteria = array_filter(array_map('trim', $request->input('criteria', [])));
        foreach ($criteria as $i => $desc) {
            $item->criteria()->create([
                'description' => $desc,
                'sort' => $i + 1,
            ]);
        }

        return redirect("/categories/$category->id")->with('success', 'Checklist berhasil ditambahkan.');
    }

    public function edit(Item $item)
    {
        return view('items.edit', compact('item'));
    }

    public function update(Request $request, Item $item)
    {
        $item->update($request->validate([
            'name' => 'required|string|max:255',
            'bobot' => 'nullable|numeric|min:0',
        ]));
        Cache::forget('monitoring.all_items');
        Cache::forget('monitoring.all_categories');

        return redirect("/categories/{$item->category_id}")->with('success', 'Checklist berhasil diperbarui.');
    }

    public function destroy(Item $item)
    {
        $categoryId = $item->category_id;
        $item->delete();
        Cache::forget('monitoring.all_items');
        Cache::forget('monitoring.all_categories');
        return redirect("/categories/$categoryId")->with('success', 'Checklist berhasil dihapus.');
    }

    public function moveUp(Item $item)
    {
        $prev = Item::where('category_id', $item->category_id)
            ->where('sort', '<', $item->sort)
            ->orderBy('sort', 'desc')
            ->first();

        if ($prev) {
            $temp = $item->sort;
            $item->update(['sort' => $prev->sort]);
            $prev->update(['sort' => $temp]);
        }
        Cache::forget('monitoring.all_items');
        Cache::forget('monitoring.all_categories');

        return redirect("/categories/{$item->category_id}");
    }

    public function moveDown(Item $item)
    {
        $next = Item::where('category_id', $item->category_id)
            ->where('sort', '>', $item->sort)
            ->orderBy('sort', 'asc')
            ->first();

        if ($next) {
            $temp = $item->sort;
            $item->update(['sort' => $next->sort]);
            $next->update(['sort' => $temp]);
        }
        Cache::forget('monitoring.all_items');
        Cache::forget('monitoring.all_categories');

        return redirect("/categories/{$item->category_id}");
    }

    public function reorder(Request $request, Category $category)
    {
        $ids = $request->input('items', []);
        foreach ($ids as $i => $id) {
            Item::where('id', $id)->where('category_id', $category->id)->update(['sort' => $i + 1]);
        }
        Cache::forget('monitoring.all_items');
        Cache::forget('monitoring.all_categories');
        return response()->json(['ok' => true]);
    }

    public function batchBobot(Request $request, Category $category)
    {
        $bobots = $request->input('bobot', []);
        foreach ($bobots as $itemId => $bobot) {
            Item::where('id', (int) $itemId)->where('category_id', $category->id)
                ->update(['bobot' => is_numeric($bobot) && $bobot !== '' ? (float) $bobot : null]);
        }
        Cache::forget('monitoring.all_items');
        Cache::forget('monitoring.all_categories');
        return redirect("/categories/{$category->id}")->with('success', 'Bobot berhasil disimpan.');
    }
}
