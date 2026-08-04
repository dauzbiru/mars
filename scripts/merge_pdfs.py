#!/usr/bin/env python3
"""
Merge multiple PDF files into one, in order.
Usage: python merge_pdfs.py <output_pdf> <input1> <input2> [...]
"""
import sys
import json
import os


def merge_pdfs(output_path, inputs):
    from pypdf import PdfReader, PdfWriter
    writer = PdfWriter()
    for path in inputs:
        if not os.path.exists(path):
            print(f"ERROR: File not found: {path}", file=sys.stderr)
            return False
        try:
            reader = PdfReader(path)
        except Exception as e:
            print(f"ERROR: Cannot read {path}: {e}", file=sys.stderr)
            return False
        for page in reader.pages:
            writer.add_page(page)
    try:
        with open(output_path, "wb") as f:
            writer.write(f)
    except Exception as e:
        print(f"ERROR: Cannot write {output_path}: {e}", file=sys.stderr)
        return False
    return True


if __name__ == "__main__":
    if len(sys.argv) < 4:
        print("Usage: python merge_pdfs.py <output_pdf> <input1> <input2> [...]", file=sys.stderr)
        sys.exit(1)
    output = sys.argv[1]
    inputs = sys.argv[2:]
    ok = merge_pdfs(output, inputs)
    print(json.dumps({"success": ok, "output": output if ok else None}))
    sys.exit(0 if ok else 1)
