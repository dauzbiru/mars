#!/usr/bin/env python3
"""
Convert a Word template (.doc/.docx) to a canonical .docx and inject
placeholders for the pra-monitoring cover letter, using Word COM.

Injections (per paragraph unless noted):
  - "No."   line        -> No.\t\t: {nomor_surat}
  - "Lampiran" line     -> Lampiran\t: {lampiran}
  - "Up" line           -> Up\t: {franchisee}
  - "Penerima Waralaba ..." line -> three lines:
        Penerima Waralaba {nama_gerai}
        {alamat}
        {kota}
  - body "lima lembar"  -> {lembar_huruf} lembar  (find/replace)

Usage: python prepare_word_template.py <input_doc> <output_docx>
"""
import sys
import json
import os
import win32com.client

WD_FORMAT_DOCX = 12   # wdFormatXMLDocument


def replace_all(doc, find_text, replace_text):
    # FindText, MatchCase, MatchWholeWord, MatchWildcards, MatchSoundsLike,
    # MatchAllWordForms, Forward, Wrap, Format, ReplaceWith, Replace
    doc.Content.Find.Execute(
        find_text, False, False, False, False, False, True, 1, False, replace_text, 2
    )  # wdReplaceAll


def normalize_line(text):
    return text.replace("\r", "").replace("\x07", "").strip()


def find_paragraph_starting_with(doc, prefix):
    for i in range(1, doc.Paragraphs.Count + 1):
        par = doc.Paragraphs.Item(i)
        if normalize_line(par.Range.Text).startswith(prefix):
            return par
    return None


def prepare(input_path, output_path):
    word = win32com.client.DispatchEx("Word.Application")
    word.Visible = False
    word.DisplayAlerts = 0
    doc = None
    try:
        doc = word.Documents.Open(
            os.path.abspath(input_path), ReadOnly=False, AddToRecentFiles=False
        )

        # Rebuild the header block (No. .. Dengan Hormat,) so the layout is
        # always exact regardless of how the source template is structured.
        no_par = find_paragraph_starting_with(doc, "No.")
        dengan_par = find_paragraph_starting_with(doc, "Dengan")
        if no_par is not None and dengan_par is not None:
            rng = doc.Range(no_par.Range.Start, dengan_par.Range.End)
            rng.Text = (
                "No.\t\t: {nomor_surat}\r"
                "Perihal\t: Hasil Pra-Monitoring\r"
                "Lampiran\t: {lampiran}\r\r"
                "Kepada,\r"
                "Penerima Waralaba Gerai Biru {kode_gerai}\r"
                "{alamat}\r"
                "{kota}\r\r"
                "Up\t: {franchisee}\r\r"
                "Dengan Hormat,\r"
            )

        replace_all(doc, "lima lembar", "{lembar_huruf}")

        output_path = os.path.abspath(output_path)
        if os.path.exists(output_path):
            os.remove(output_path)
        doc.SaveAs2(FileName=output_path, FileFormat=WD_FORMAT_DOCX)
        doc.Close(False)
        doc = None
        return os.path.exists(output_path)
    finally:
        if doc is not None:
            try:
                doc.Close(False)
            except Exception:
                pass
        word.Quit()


if __name__ == "__main__":
    if len(sys.argv) != 3:
        print("Usage: python prepare_word_template.py <input_doc> <output_docx>", file=sys.stderr)
        sys.exit(1)
    if not os.path.exists(sys.argv[1]):
        print(f"ERROR: File not found: {sys.argv[1]}", file=sys.stderr)
        sys.exit(1)
    ok = prepare(sys.argv[1], sys.argv[2])
    print(json.dumps({"success": ok, "output": sys.argv[2] if ok else None}))
    sys.exit(0 if ok else 1)
