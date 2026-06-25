import openpyxl
wb = openpyxl.load_workbook('C:/Users/IT/Desktop/Productos Chapalita Clasificacion.xlsx', read_only=True)
ws = wb.active

# Get ALL codes that have E4 in PurchaseUnit
e4_codes = []
for row in ws.iter_rows(min_row=2, values_only=True):
    code = str(row[0] or '').strip()
    unit = str(row[14] or '').strip()
    if unit == 'E4' and code:
        e4_codes.append(code)

# Generate SQL in chunks of 500 (MySQL limit)
for i in range(0, len(e4_codes), 500):
    chunk = e4_codes[i:i+500]
    codes = "','".join(chunk)
    print(f"UPDATE productos SET unidad_venta = 'KG' WHERE codigo IN ('{codes}') AND deleted_at IS NULL;")

print(f"-- Total E4 codes: {len(e4_codes)}")
wb.close()
