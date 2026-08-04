<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Criterion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CriterionController extends Controller
{
    public function index(Item $item)
    {
        $criteria = $item->criteria;
        return view('criteria.index', compact('item', 'criteria'));
    }

    public function create(Item $item)
    {
        return view('criteria.create', compact('item'));
    }

    public function store(Request $request, Item $item)
    {
        $request->validate(['description' => 'required|string|max:255']);
        $sort = ($item->criteria()->max('sort') ?? 0) + 1;
        $criterion = $item->criteria()->create([
            'description' => $request->description,
            'sort' => $sort,
        ]);

        Cache::forget('monitoring.all_items');

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'criterion' => ['id' => $criterion->id, 'description' => $criterion->description]]);
        }

        return redirect("/categories/{$item->category_id}")->with('success', 'Opsi berhasil ditambahkan.');
    }

    public function edit(Item $item, Criterion $criterion)
    {
        return view('criteria.edit', compact('item', 'criterion'));
    }

    public function update(Request $request, Item $item, Criterion $criterion)
    {
        $request->validate(['description' => 'required|string|max:255']);
        $criterion->update(['description' => $request->description]);
        Cache::forget('monitoring.all_items');

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'criterion' => ['id' => $criterion->id, 'description' => $criterion->description]]);
        }

        return redirect("/categories/{$item->category_id}")->with('success', 'Opsi berhasil diubah.');
    }

    public function destroy(Request $request, Item $item, Criterion $criterion)
    {
        $criterion->delete();
        Cache::forget('monitoring.all_items');

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect("/categories/{$item->category_id}")->with('success', 'Opsi berhasil dihapus.');
    }

    public function batchStore(Request $request, Item $item)
    {
        $descriptions = $request->input('descriptions', []);
        $sort = ($item->criteria()->max('sort') ?? 0) + 1;
        $count = 0;
        foreach ($descriptions as $i => $desc) {
            $desc = trim($desc);
            if ($desc === '') continue;
            $item->criteria()->create([
                'description' => $desc,
                'sort' => $sort++,
            ]);
            $count++;
        }
        Cache::forget('monitoring.all_items');
        $actualCount = $count ?? 0;
        return redirect("/categories/{$item->category_id}")->with('success', $actualCount . ' opsi berhasil ditambahkan.');
    }

    public function reorder(Request $request, Item $item)
    {
        $ids = $request->input('items', []);
        foreach ($ids as $i => $id) {
            Criterion::where('id', $id)->where('item_id', $item->id)->update(['sort' => $i + 1]);
        }
        Cache::forget('monitoring.all_items');
        return response()->json(['ok' => true]);
    }
}
