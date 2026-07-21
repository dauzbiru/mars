import sys
import time
import gc
import os
import xlwings as xw

path = os.path.abspath(sys.argv[1])
app = xw.App(visible=False)
wb = app.books.open(path)

# --- Sheet 1: Catatan / Keterangan ---
ws = wb.sheets[0]

for row in range(34, 100):
    cell = ws.range(f"B{row}")
    val = cell.value
    if val is None:
        continue

    sval = str(val).strip()
    is_header = sval in ("Catatan:", "Keterangan:")

    # Font
    cell.font.name = "FreeSans"
    cell.font.color = (0, 0, 0)
    if is_header:
        cell.font.size = 12
        cell.font.bold = True
        cell.font.color = (0, 112, 192)
    else:
        cell.font.size = 11
        cell.font.bold = False

    # Headers: center
    if is_header:
        cell.api.HorizontalAlignment = -4131  # center

    # Merge B:M for content rows (not header, not empty)
    if not is_header and sval != "":
        ws.range(f"B{row}:M{row}").api.Merge()
        cell.api.HorizontalAlignment = -4131  # center
        cell.api.VerticalAlignment = -4160    # top

        # Wrap text + height 30 only if > 120 chars
        if len(sval) > 120:
            cell.api.WrapText = True
            ws.range(f"{row}:{row}").api.RowHeight = 30

# --- Sheet 2: Temuan (C8:C69) ---
ws2 = wb.sheets[1]
headers2 = ("Checklist Kondisi Gerai:", "Note:")

for row in range(8, 70):
    cell = ws2.range(f"C{row}")
    val = cell.value
    if val is None:
        continue

    sval = str(val).strip()
    if sval == "":
        continue

    is_header2 = sval in headers2

    cell.font.name = "FreeSans"
    cell.font.size = 11
    cell.font.bold = is_header2
    cell.font.color = (0, 0, 0)
    cell.api.VerticalAlignment = -4160  # top

    if len(sval) > 116:
        cell.api.WrapText = True
        ws2.range(f"{row}:{row}").api.RowHeight = 30

# B column: center+middle alignment for numbering
for row in range(8, 70):
    cell = ws2.range(f"B{row}")
    val = cell.value
    if val is None:
        continue
    cell.font.name = "FreeSans"
    cell.font.size = 11
    cell.font.bold = True
    cell.font.color = (0, 0, 0)
    cell.api.HorizontalAlignment = -4108  # center
    cell.api.VerticalAlignment = -4108    # center

# Delete empty rows above first filled row in column C
first_filled = 0
for row in range(8, 70):
    val = ws2.range(f"C{row}").value
    if val is not None and str(val).strip() != "":
        first_filled = row
        break

if first_filled > 8:
    ws2.range(f"8:{first_filled - 1}").api.Delete()

wb.save()
wb.close()

app.api.Quit()
del wb
del app
gc.collect()

for _ in range(20):
    try:
        with open(path, 'rb') as f:
            f.read(1)
        break
    except (PermissionError, IOError):
        time.sleep(0.25)
