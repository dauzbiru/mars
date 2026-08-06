import sys
import json
import os

def convert_to_pdf(xlsx_path, pdf_path):
    import xlwings as xw
    
    app = xw.App(visible=False)
    try:
        wb = app.books.open(xlsx_path)
        
        wb.api.ExportAsFixedFormat(
            Type=0,  # xlTypePDF
            Filename=pdf_path,
            Quality=0,  # xlQualityStandard
            IncludeDocProperties=True,
            IgnorePrintAreas=False,
            OpenAfterPublish=False
        )
        
        wb.close()
        return True
    except Exception as e:
        print(f"ERROR: {e}", file=sys.stderr)
        return False
    finally:
        app.quit()

if __name__ == '__main__':
    if len(sys.argv) != 3:
        print("Usage: python xlwings-to-pdf.py <xlsx_path> <pdf_path>", file=sys.stderr)
        sys.exit(1)
    
    xlsx_path = sys.argv[1]
    pdf_path = sys.argv[2]
    
    if not os.path.exists(xlsx_path):
        print(f"ERROR: File not found: {xlsx_path}", file=sys.stderr)
        sys.exit(1)
    
    ok = convert_to_pdf(xlsx_path, pdf_path)
    print(json.dumps({"success": ok, "pdf_path": pdf_path if ok else None}))
    sys.exit(0 if ok else 1)
