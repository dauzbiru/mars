<?php

namespace App\Http\Controllers;

use App\Models\HargaLab;
use Illuminate\Http\Request;

class HargaLabController extends Controller
{
    public function index()
    {
        $hargaLab = HargaLab::orderBy('kota')->orderBy('laboratorium')->get();
        return view('harga-lab.index', compact('hargaLab'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'kota' => 'nullable|string|max:255',
            'laboratorium' => 'required|string|max:255',
            'mikrobiologi' => 'nullable|numeric|min:0',
            'fisika_kimia' => 'nullable|numeric|min:0',
            'lengkap' => 'nullable|numeric|min:0',
            'catatan' => 'nullable|string|max:1000',
            'alamat' => 'nullable|string|max:1000',
        ]);

        HargaLab::create($data);

        return redirect('/harga-lab')->with('success', 'Data harga uji lab berhasil ditambahkan.');
    }

    public function update(Request $request, HargaLab $hargaLab)
    {
        $data = $request->validate([
            'kota' => 'nullable|string|max:255',
            'laboratorium' => 'required|string|max:255',
            'mikrobiologi' => 'nullable|numeric|min:0',
            'fisika_kimia' => 'nullable|numeric|min:0',
            'lengkap' => 'nullable|numeric|min:0',
            'catatan' => 'nullable|string|max:1000',
            'alamat' => 'nullable|string|max:1000',
        ]);

        $hargaLab->update($data);

        return redirect('/harga-lab')->with('success', 'Data harga uji lab berhasil diperbarui.');
    }

    public function destroy(HargaLab $hargaLab)
    {
        $hargaLab->delete();

        return redirect('/harga-lab')->with('success', 'Data harga uji lab berhasil dihapus.');
    }
}
