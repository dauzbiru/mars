<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Item;
use Illuminate\Http\Request;
use OpenSpout\Reader\XLSX\Reader as XLSXReader;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Row;

class ImportController extends Controller
{
    public function create()
    {
        return view('import.create');
    }

    public function import(Request $request)
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

                $catName = trim((string) ($row->cells[0]->getValue() ?? ''));
                $itemName = trim((string) ($row->cells[1]->getValue() ?? ''));

                if (empty($catName) && empty($itemName)) continue;
                if (empty($catName)) continue;

                $category = Category::firstOrCreate(['name' => $catName]);

                if (empty($itemName)) continue;

                $exists = $category->items()->where('name', $itemName)->exists();
                if ($exists) continue;

                $category->items()->create(['name' => $itemName]);
                $count++;
            }
        }

        $reader->close();

        return redirect('/categories')->with('success', "Berhasil import $count data.");
    }

    public function template()
    {
        $writer = new Writer();
        $filename = storage_path('app/template-import-' . uniqid('', true) . '.xlsx');

        $writer->openToFile($filename);

        $writer->addRow(Row::fromValues(['Tugas', 'Checklist']));
        $writer->addRow(Row::fromValues(['Tugas 1', 'Checklist 1']));
        $writer->addRow(Row::fromValues(['Tugas 1', 'Checklist 2']));
        $writer->addRow(Row::fromValues(['Tugas 2', 'Checklist 1']));

        $writer->close();

        return response()->download($filename)->deleteFileAfterSend(true);
    }
}
