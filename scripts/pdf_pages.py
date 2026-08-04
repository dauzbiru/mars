#!/usr/bin/env python3
"""
Count pages of a PDF file.
Usage: python pdf_pages.py <pdf_path>
Prints an integer page count to stdout.
"""
import sys


def main():
    if len(sys.argv) != 2:
        print("0", file=sys.stderr)
        sys.exit(1)
    from pypdf import PdfReader
    try:
        reader = PdfReader(sys.argv[1])
        print(len(reader.pages))
    except Exception:
        print("0", file=sys.stderr)
        sys.exit(1)


if __name__ == "__main__":
    main()
