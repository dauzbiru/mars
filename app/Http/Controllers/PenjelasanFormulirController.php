<?php

namespace App\Http\Controllers;

use App\Models\PenjelasanFormulir;
use Illuminate\Http\Request;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Row;

class PenjelasanFormulirController extends Controller
{
    private function validateFormulir($formulir): void
    {
        if (!in_array((int) $formulir, [2, 3], true)) {
            abort(404);
        }
    }

    public function index($formulir)
    {
        $this->validateFormulir($formulir);
        $items = PenjelasanFormulir::where('formulir', $formulir)->orderBy('sort')->get();
        $title = "Penjelasan Formulir $formulir";
        return view("tugas.penjelasan-formulir-{$formulir}", compact('items', 'formulir', 'title'));
    }

    public function store(Request $request, $formulir)
    {
        $this->validateFormulir($formulir);

        $request->validate([
            'kondisi' => 'required|string|max:1000',
            'penjelasan' => 'nullable|string|max:5000',
        ]);

        $maxSort = PenjelasanFormulir::where('formulir', $formulir)->max('sort') ?? 0;

        PenjelasanFormulir::create([
            'formulir' => $formulir,
            'kondisi' => $request->kondisi,
            'penjelasan' => $request->penjelasan,
            'sort' => $maxSort + 1,
        ]);

        return redirect("/tugas/penjelasan-formulir-{$formulir}")->with('success', 'Item berhasil ditambahkan.');
    }

    public function update(Request $request, PenjelasanFormulir $penjelasanFormulir)
    {
        $request->validate([
            'kondisi' => 'required|string|max:1000',
            'penjelasan' => 'nullable|string|max:5000',
        ]);

        $penjelasanFormulir->update([
            'kondisi' => $request->kondisi,
            'penjelasan' => $request->penjelasan,
        ]);

        return redirect("/tugas/penjelasan-formulir-{$penjelasanFormulir->formulir}")->with('success', 'Item berhasil diperbarui.');
    }

    public function destroy(PenjelasanFormulir $penjelasanFormulir)
    {
        $formulir = $penjelasanFormulir->formulir;
        $penjelasanFormulir->delete();

        return redirect("/tugas/penjelasan-formulir-$formulir")->with('success', 'Item berhasil dihapus.');
    }

    public function importForm($formulir)
    {
        $this->validateFormulir($formulir);
        $title = "Penjelasan Formulir $formulir";
        return view('tugas.import', compact('formulir', 'title'));
    }

    public function import(Request $request, $formulir)
    {
        $this->validateFormulir($formulir);

        $request->validate([
            'file' => 'required|file|mimes:xlsx',
        ]);

        $reader = new Reader();
        $reader->open($request->file('file')->getPathname());

        $maxSort = PenjelasanFormulir::where('formulir', $formulir)->max('sort') ?? 0;
        $count = 0;

        foreach ($reader->getSheetIterator() as $sheet) {
            $first = true;
            foreach ($sheet->getRowIterator() as $row) {
                if ($first) { $first = false; continue; }
                $cells = $row->toArray();
                $kondisi = $cells[0] ?? '';
                $penjelasan = $cells[1] ?? '';
                if (empty(trim($kondisi))) continue;

                $maxSort++;
                PenjelasanFormulir::create([
                    'formulir' => $formulir,
                    'kondisi' => $kondisi,
                    'penjelasan' => $penjelasan,
                    'sort' => $maxSort,
                ]);
                $count++;
            }
        }

        $reader->close();

        return redirect("/tugas/penjelasan-formulir-{$formulir}")->with('success', "$count item berhasil diimport.");
    }

    public function template($formulir)
    {
        $this->validateFormulir($formulir);

        $writer = new Writer();
        $filename = storage_path("app/template-penjelasan-formulir-{$formulir}.xlsx");
        $writer->openToFile($filename);

        $writer->addRow(Row::fromValues(['Kondisi', 'Penjelasan']));
        $writer->addRow(Row::fromValues(['Contoh kondisi...', 'Contoh penjelasan...']));

        $writer->close();

        return response()->download($filename)->deleteFileAfterSend(true);
    }

}
