#!/usr/bin/env python3
"""
Fill placeholders in a Word template and export to PDF via Word COM.
Usage: python word_fill_export.py <template_path> <output_pdf_path> <replacements_json>
"""
import sys
import json
import os
import win32com.client

WD_FORMAT_PDF = 17  # wdFormatPDF
WD_REPLACE_ALL = 2


def word_fill_export(template_path, output_pdf_path, replacements):
    word = win32com.client.DispatchEx("Word.Application")
    word.Visible = False
    word.DisplayAlerts = 0
    doc = None
    try:
        doc = word.Documents.Open(template_path, ReadOnly=False)

        for key, value in replacements.items():
            if key == "" or value is None:
                continue
            value = str(value)
            # FindText, MatchCase, MatchWholeWord, MatchWildcards, MatchSoundsLike,
            # MatchAllWordForms, Forward, Wrap, Format, ReplaceWith, Replace
            doc.Content.Find.Execute(
                key, False, False, False, False, False, True, 1, False, value, WD_REPLACE_ALL
            )

        output_pdf_path = os.path.abspath(output_pdf_path)
        if os.path.exists(output_pdf_path):
            os.remove(output_pdf_path)
        doc.ExportAsFixedFormat(
            OutputFileName=output_pdf_path,
            ExportFormat=WD_FORMAT_PDF,
            OpenAfterExport=False,
            OptimizeFor=0,
            Range=0,
        )
        doc.Close(False)
        doc = None
        return os.path.exists(output_pdf_path)
    finally:
        if doc is not None:
            try:
                doc.Close(False)
            except Exception:
                pass
        word.Quit()


if __name__ == "__main__":
    if len(sys.argv) != 4:
        print("Usage: python word_fill_export.py <template_path> <output_pdf_path> <replacements_json>", file=sys.stderr)
        sys.exit(1)

    template_path = sys.argv[1]
    output_pdf_path = sys.argv[2]
    replacements_json = sys.argv[3]

    if not os.path.exists(template_path):
        print(f"ERROR: File not found: {template_path}", file=sys.stderr)
        sys.exit(1)

    try:
        with open(replacements_json, "r", encoding="utf-8-sig") as f:
            replacements = json.load(f)
    except Exception as e:
        print(f"ERROR: Cannot read replacements JSON: {e}", file=sys.stderr)
        sys.exit(1)

    ok = word_fill_export(template_path, output_pdf_path, replacements)
    print(json.dumps({"success": ok, "output": output_pdf_path if ok else None}))
    sys.exit(0 if ok else 1)
