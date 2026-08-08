<?php

namespace App\Http\Controllers;

use App\Models\Gerai;
use App\Models\KotaMap;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Reader\XLSX\Reader as XLSXReader;
use OpenSpout\Common\Entity\Row;

class GeraiController extends Controller
{
    private static function resolveKotaArea(string $kodeGerai, ?string $namaKota, ?string $area): array
    {
        $prefix = strtoupper(substr($kodeGerai, 0, 3));
        $map = KotaMap::where('kode', $prefix)->first();
        if ($map) {
            return [$map->nama_kota, $map->area];
        }
        return [$namaKota, $area];
    }

    public function index()
    {
        $gerais = Gerai::orderBy('kode_gerai')->get();
        $kotaMaps = KotaMap::orderBy('kode')->get();
        return view('gerais.index', compact('gerais', 'kotaMaps'));
    }

    public function create()
    {
        return view('gerais.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'kode_gerai' => 'required|string|max:50|unique:gerais,kode_gerai,NULL,id,is_active,1',
            'nama_gerai' => 'required|string|max:255',
            'franchisee' => 'required|string|max:255',
            'alamat' => 'nullable|string',
            'email' => 'nullable|email|max:255',
            'no_telepon' => 'nullable|string|max:20',
            'opening_at' => 'nullable|date',
            'nama_kota' => 'nullable|string|max:255',
            'area' => 'nullable|string|max:255',
        ]);

        [$data['nama_kota'], $data['area']] = self::resolveKotaArea($data['kode_gerai'], $data['nama_kota'] ?? null, $data['area'] ?? null);

        $prefix = strtoupper(substr($data['kode_gerai'], 0, 3));
        if ($data['nama_kota'] && $data['area'] && !KotaMap::where('kode', $prefix)->exists()) {
            KotaMap::create(['kode' => $prefix, 'nama_kota' => $data['nama_kota'], 'area' => $data['area']]);
        }

        Gerai::create(['is_active' => true] + $data);
        Cache::forget('gerais.active');

        return redirect('/gerais')->with('success', 'Gerai berhasil ditambahkan.');
    }

    public function show(Gerai $gerai)
    {
        return view('gerais.show', compact('gerai'));
    }

    public function edit(Gerai $gerai)
    {
        return view('gerais.edit', compact('gerai'));
    }

    public function update(Request $request, Gerai $gerai)
    {
        $data = $request->validate([
            'kode_gerai' => 'required|string|max:50|unique:gerais,kode_gerai,' . $gerai->id . ',id,is_active,1',
            'nama_gerai' => 'required|string|max:255',
            'franchisee' => 'required|string|max:255',
            'alamat' => 'nullable|string',
            'email' => 'nullable|email|max:255',
            'no_telepon' => 'nullable|string|max:20',
            'opening_at' => 'nullable|date',
            'nama_kota' => 'nullable|string|max:255',
            'area' => 'nullable|string|max:255',
        ]);

        [$data['nama_kota'], $data['area']] = self::resolveKotaArea($data['kode_gerai'], $data['nama_kota'] ?? null, $data['area'] ?? null);

        $gerai->update($data);
        Cache::forget('gerais.active');

        return redirect('/gerais')->with('success', 'Gerai berhasil diperbarui.');
    }

    public function destroy(Gerai $gerai)
    {
        $gerai->delete();
        Cache::forget('gerais.active');

        return redirect('/gerais')->with('success', 'Gerai berhasil dihapus.');
    }

    public function tutup(Gerai $gerai)
    {
        $gerai->update([
            'is_active' => false,
            'closed_at' => now(),
        ]);
        Cache::forget('gerais.active');

        return redirect('/gerais')->with('success', 'Gerai berhasil ditutup.');
    }

    public function buka(Gerai $gerai)
    {
        $duplicate = Gerai::active()->where('kode_gerai', $gerai->kode_gerai)->where('id', '!=', $gerai->id)->exists();

        if ($duplicate) {
            return redirect('/gerais')->with('error', 'Gagal membuka: sudah ada gerai aktif dengan kode ' . $gerai->kode_gerai . '. Nonaktifkan dulu yang lain.');
        }

        $gerai->update([
            'is_active' => true,
            'closed_at' => null,
        ]);
        Cache::forget('gerais.active');

        return redirect('/gerais')->with('success', 'Gerai berhasil dibuka kembali.');
    }

    public function syncKota()
    {
        $gerais = Gerai::active()->get();
        $kotaMaps = KotaMap::all()->keyBy('kode');
        $updated = 0;

        foreach ($gerais as $g) {
            $prefix = strtoupper(substr($g->kode_gerai, 0, 3));
            $map = $kotaMaps->get($prefix);
            if ($map && (!$g->nama_kota || !$g->area)) {
                $g->update([
                    'nama_kota' => $map->nama_kota,
                    'area' => $map->area,
                ]);
                $updated++;
            }
        }

        return redirect('/gerais')->with('success', "Berhasil update $updated gerai dari daftar kota.");
    }

    public function storeKotaMap(Request $request)
    {
        $data = $request->validate([
            'kode' => 'required|string|max:10|unique:kota_maps,kode',
            'nama_kota' => 'required|string|max:255',
            'area' => 'required|string|max:255',
        ]);
        $data['kode'] = strtoupper($data['kode']);

        KotaMap::create($data);

        return redirect('/gerais')->with('success', 'Kota baru berhasil ditambahkan.');
    }

    public function updateKotaMap(Request $request, KotaMap $kotaMap)
    {
        $data = $request->validate([
            'kode' => 'required|string|max:10|unique:kota_maps,kode,' . $kotaMap->id,
            'nama_kota' => 'required|string|max:255',
            'area' => 'required|string|max:255',
        ]);
        $data['kode'] = strtoupper($data['kode']);

        $kotaMap->update($data);

        return redirect('/gerais')->with('success', 'Kota berhasil diperbarui.');
    }

    public function destroyKotaMap(KotaMap $kotaMap)
    {
        $kotaMap->delete();

        return redirect('/gerais')->with('success', 'Kota berhasil dihapus.');
    }

    public function importForm()
    {
        return view('gerais.import');
    }

    public function importExcel(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx',
        ]);

        $file = $request->file('file');
        $reader = new XLSXReader();
        $reader->open($file->getPathname());

        $count = 0;

        DB::transaction(function () use ($reader, &$count) {
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $rowIndex => $row) {
                    if ($rowIndex === 1) continue;

                    $getCell = function ($cells, $idx) {
                        $val = isset($cells[$idx]) ? $cells[$idx]->getValue() : '';
                        if ($val instanceof \DateTimeInterface) return $val->format('d-m-Y');
                        if (is_numeric($val)) return (string) $val;
                        return is_string($val) ? $val : '';
                    };
                    $kode = trim($getCell($row->cells, 0));
                    $nama = trim($getCell($row->cells, 1));
                    $franchisee = trim($getCell($row->cells, 2));
                    $alamat = trim($getCell($row->cells, 3));
                    $email = trim($getCell($row->cells, 4));
                    $noTelepon = trim($getCell($row->cells, 5));
                    $openingRaw = trim($getCell($row->cells, 6));
                    $namaKota = trim($getCell($row->cells, 7));
                    $area = trim($getCell($row->cells, 8));

                    if (empty($kode) && empty($nama)) continue;
                    if (empty($kode)) continue;

                    [$namaKota, $area] = self::resolveKotaArea($kode, $namaKota ?: null, $area ?: null);

                    $data = ['nama_gerai' => $nama, 'franchisee' => $franchisee, 'alamat' => $alamat, 'email' => $email, 'no_telepon' => $noTelepon, 'nama_kota' => $namaKota, 'area' => $area];
                    if (!empty($openingRaw)) {
                        try {
                            $data['opening_at'] = \Carbon\Carbon::createFromFormat('d-m-Y', $openingRaw)->format('Y-m-d');
                        } catch (\Exception $e) {
                            try {
                                $data['opening_at'] = \Carbon\Carbon::parse($openingRaw)->format('Y-m-d');
                            } catch (\Exception $e2) {
                                // skip invalid date
                            }
                        }
                    }

                    $gerai = Gerai::where('kode_gerai', $kode)->where('is_active', true)->first();
                    if ($gerai) {
                        $gerai->update($data);
                    } else {
                        Gerai::create(array_merge($data, ['kode_gerai' => $kode, 'is_active' => true]));
                    }
                    $count++;
                }
            }
        });

        $reader->close();
        Cache::forget('gerais.active');

        return redirect('/gerais')->with('success', "Berhasil import $count data gerai.");
    }

    public function exportExcel(Request $request)
    {
        $request->validate([
            'status' => 'nullable|in:all,active,closed',
        ]);

        $writer = new Writer();
        $filename = storage_path('app/' . uniqid('export-gerai-', true) . '.xlsx');

        $writer->openToFile($filename);

        $writer->addRow(Row::fromValues(['Kode Gerai', 'Nama Gerai', 'Franchisee', 'Alamat', 'Email', 'No Telepon', 'Opening', 'Nama Kota', 'Area']));

        $query = Gerai::orderBy('kode_gerai');
        $status = $request->query('status', 'all');
        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'closed') {
            $query->where('is_active', false);
        }

        foreach ($query->get() as $g) {
            $writer->addRow(Row::fromValues([$g->kode_gerai, $g->nama_gerai, $g->franchisee, $g->alamat, $g->email, $g->no_telepon, $g->opening_at?->format('d-m-Y'), $g->nama_kota, $g->area]));
        }

        $writer->close();

        return response()->download($filename)->deleteFileAfterSend(true);
    }

    public function template()
    {
        $writer = new Writer();
        $filename = storage_path('app/template-gerai.xlsx');

        $writer->openToFile($filename);

        $writer->addRow(Row::fromValues(['Kode Gerai', 'Nama Gerai', 'Franchisee', 'Alamat', 'Email', 'No Telepon', 'Opening', 'Nama Kota', 'Area']));
        $writer->addRow(Row::fromValues(['GR001', 'Gerai Contoh 1', 'Franchisee A', 'Jl. Contoh No. 1', 'gerai1@email.com', '081234567890', '15-01-2024', '', '']));
        $writer->addRow(Row::fromValues(['GR002', 'Gerai Contoh 2', 'Franchisee B', '', '', '', '01-06-2024', '', '']));

        $writer->close();

        return response()->download($filename)->deleteFileAfterSend(true);
    }
}
