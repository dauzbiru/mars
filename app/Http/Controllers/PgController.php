<?php

namespace App\Http\Controllers;

use App\Models\Pg;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Reader\XLSX\Reader as XLSXReader;
use OpenSpout\Common\Entity\Row;

class PgController extends Controller
{
    public function index()
    {
        $pgs = Pg::orderBy('kota')->orderBy('nama_pg')->get();
        return view('pgs.index', compact('pgs'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama_pg' => 'required|string|max:255',
            'kota' => 'nullable|string|max:255',
            'no_telepon' => 'nullable|string|max:20',
        ]);

        Pg::create($data);
        Cache::forget('pgs.all');

        return redirect('/pgs')->with('success', 'Data PG berhasil ditambahkan.');
    }

    public function update(Request $request, Pg $pg)
    {
        $data = $request->validate([
            'nama_pg' => 'required|string|max:255',
            'kota' => 'nullable|string|max:255',
            'no_telepon' => 'nullable|string|max:20',
        ]);

        $pg->update($data);
        Cache::forget('pgs.all');

        return redirect('/pgs')->with('success', 'Data PG berhasil diperbarui.');
    }

    public function destroy(Pg $pg)
    {
        $pg->delete();
        Cache::forget('pgs.all');

        return redirect('/pgs')->with('success', 'Data PG berhasil dihapus.');
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

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $rowIndex => $row) {
                if ($rowIndex === 1) continue;

                $getCell = function ($cells, $idx) {
                    $val = isset($cells[$idx]) ? $cells[$idx]->getValue() : '';
                    if ($val instanceof \DateTimeInterface) return $val->format('d-m-Y');
                    if (is_numeric($val)) return (string) $val;
                    return is_string($val) ? $val : '';
                };
                $nama = trim($getCell($row->cells, 0));
                $kota = trim($getCell($row->cells, 1));
                $noTelepon = trim($getCell($row->cells, 2));

                if (empty($nama)) continue;

                Pg::updateOrCreate(
                    ['nama_pg' => $nama, 'kota' => $kota],
                    ['no_telepon' => $noTelepon]
                );
                $count++;
            }
        }

        $reader->close();
        Cache::forget('pgs.all');

        return redirect('/pgs')->with('success', "Berhasil import $count data PG.");
    }

    public function exportExcel()
    {
        $writer = new Writer();
        $filename = storage_path('app/export-pg-' . uniqid('', true) . '.xlsx');

        $writer->openToFile($filename);

        $writer->addRow(Row::fromValues(['Nama PG', 'Kota', 'No Telepon']));

        $pgs = Pg::orderBy('kota')->orderBy('nama_pg')->get();
        foreach ($pgs as $p) {
            $writer->addRow(Row::fromValues([$p->nama_pg, $p->kota, $p->no_telepon]));
        }

        $writer->close();

        return response()->download($filename)->deleteFileAfterSend(true);
    }

    public function template()
    {
        $writer = new Writer();
        $filename = storage_path('app/template-pg-' . uniqid('', true) . '.xlsx');

        $writer->openToFile($filename);

        $writer->addRow(Row::fromValues(['Nama PG', 'Kota', 'No Telepon']));
        $writer->addRow(Row::fromValues(['PG Contoh', 'Jakarta', '081234567890']));

        $writer->close();

        return response()->download($filename)->deleteFileAfterSend(true);
    }
}
