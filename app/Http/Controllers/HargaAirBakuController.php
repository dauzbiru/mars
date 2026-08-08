<?php

namespace App\Http\Controllers;

use App\Models\HargaAirBaku;
use Illuminate\Http\Request;

class HargaAirBakuController extends Controller
{
    public function index()
    {
        $hargaAirBaku = HargaAirBaku::orderBy('kota')->orderBy('nama_supplier')->get();
        return view('harga-air-baku.index', compact('hargaAirBaku'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'kota' => 'nullable|string|max:255',
            'nama_supplier' => 'required|string|max:255',
            'harga_air_baku' => 'nullable|numeric|min:0',
            'pemilik' => 'nullable|string|max:255',
            'no_telepon' => 'nullable|string|max:20',
        ]);

        HargaAirBaku::create($data);

        return redirect('/harga-air-baku')->with('success', 'Data harga air baku berhasil ditambahkan.');
    }

    public function update(Request $request, HargaAirBaku $hargaAirBaku)
    {
        $data = $request->validate([
            'kota' => 'nullable|string|max:255',
            'nama_supplier' => 'required|string|max:255',
            'harga_air_baku' => 'nullable|numeric|min:0',
            'pemilik' => 'nullable|string|max:255',
            'no_telepon' => 'nullable|string|max:20',
        ]);

        $hargaAirBaku->update($data);

        return redirect('/harga-air-baku')->with('success', 'Data harga air baku berhasil diperbarui.');
    }

    public function destroy(HargaAirBaku $hargaAirBaku)
    {
        $hargaAirBaku->delete();

        return redirect('/harga-air-baku')->with('success', 'Data harga air baku berhasil dihapus.');
    }
}
