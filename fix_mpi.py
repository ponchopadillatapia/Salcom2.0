import openpyxl
wb = openpyxl.load_workbook('C:/Users/IT/Desktop/Productos Chapalita Clasificacion.xlsx', read_only=True)
ws = wb.active

mpi_h87 = []
for row in ws.iter_rows(min_row=2, values_only=True):
    code = str(row[0] or '').strip()
    unit = str(row[14] or '').strip()
    if code.upper().startswith('MPI') and unit == 'H87':
        mpi_h87.append(code)

codes = "','".join(mpi_h87)
print(f"UPDATE productos SET unidad_venta = 'PZA' WHERE codigo IN ('{codes}') AND deleted_at IS NULL;")
wb.close()
