<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class AltaProductoNacionalTest extends TestCase
{
    use RefreshDatabase;

    private function sesionAdmin(): array
    {
        $admin = AdminUser::create([
            'nombre' => 'Admin Test',
            'correo' => 'admin@test.com',
            'usuario' => 'ADMTEST',
            'password' => Hash::make('test1234'),
            'activo' => true,
            'rol' => 'admin',
        ]);

        return [
            'admin_id' => $admin->id,
            'admin_nombre' => $admin->nombre,
            'admin_correo' => $admin->correo,
            'admin_usuario' => $admin->usuario,
            'admin_rol' => $admin->rol,
        ];
    }

    private function crearExcelNacional(array $filas = []): UploadedFile
    {
        if (empty($filas)) {
            $filas = [[
                'PREFIJO' => 'ME',
                'CONSECUTIVO' => '001',
                'NOMBRE_TIPO' => 'caja corrugada',
                'NOMBRE_MEDIDA' => '40x30x25cm',
                'TIPO_PRODUCTO' => 'me',
                'PRECIO' => '$150.50',
                'MOQ' => '100',
            ]];
        }

        $ss = new Spreadsheet();
        $sheet = $ss->getActiveSheet();
        $sheet->setCellValue('A1', 'Filtro consecutivos (col. B):');
        $sheet->setCellValue('B1', 'Solo activos');

        $headers = ['PREFIJO', 'CONSECUTIVO', 'NOMBRE_TIPO', 'NOMBRE_MARCA', 'NOMBRE_MODELO', 'NOMBRE_MEDIDA', 'NOMBRE_ESPECIFICACION', 'FAMILIA', 'TIPO_PRODUCTO', 'UNIDAD_MEDIDA', 'PRECIO', 'MOQ', 'CLAVE_SAT', 'LOTE', 'PEDIMENTO', 'VOLTAJE'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col.'2', $h);
            $col++;
        }

        $row = 3;
        foreach ($filas as $fila) {
            $col = 'A';
            foreach ($headers as $h) {
                if (isset($fila[$h])) {
                    $sheet->setCellValue($col.$row, $fila[$h]);
                }
                $col++;
            }
            $row++;
        }

        $path = storage_path('app/test_nacional_upload.xlsx');
        (new Xlsx($ss))->save($path);

        return new UploadedFile($path, 'test_nacional.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    public function test_descargar_template_nacional_no_falla(): void
    {
        $this->withSession($this->sesionAdmin());

        $response = $this->get('/admin/alta-producto/template');

        $response->assertOk();
        $response->assertHeader('content-disposition');
    }

    public function test_subir_excel_nacional_me_mp_no_da_500(): void
    {
        $this->withSession($this->sesionAdmin());

        $file = $this->crearExcelNacional();

        $response = $this->post('/admin/alta-producto/subir', ['excel' => $file]);

        $this->assertNotEquals(500, $response->getStatusCode(), 'Subida nacional devolvió 500');
        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $producto = Producto::where('codigo', 'ME001')->first();
        $this->assertNotNull($producto);
        $this->assertSame('CAJA CORRUGADA 40X30X25CM', preg_replace('/\s+/', ' ', trim($producto->nombre)));
        $this->assertSame(150.50, (float) $producto->precio);
        $this->assertSame('Admin Test', $producto->proveedor_nombre);
    }

    public function test_subir_excel_nacional_con_error_muestra_mensaje_no_500(): void
    {
        Producto::create([
            'codigo' => 'ME002',
            'nombre' => 'PRODUCTO EXISTENTE',
            'precio' => 0,
            'unidad_venta' => 'PZA',
            'activo' => true,
        ]);

        $this->withSession($this->sesionAdmin());

        $file = $this->crearExcelNacional([[
            'PREFIJO' => 'ME',
            'CONSECUTIVO' => '002',
            'NOMBRE_TIPO' => 'CAJA',
            'NOMBRE_MEDIDA' => '30X30',
            'TIPO_PRODUCTO' => 'ME',
            'PRECIO' => '$10.00',
            'MOQ' => '50',
        ]]);

        $response = $this->post('/admin/alta-producto/subir', ['excel' => $file]);

        $this->assertNotEquals(500, $response->getStatusCode());
        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_subir_excel_nacional_sin_moq_genera_error(): void
    {
        $this->withSession($this->sesionAdmin());

        $file = $this->crearExcelNacional([[
            'PREFIJO' => 'ME',
            'CONSECUTIVO' => '010',
            'NOMBRE_TIPO' => 'CAJA',
            'NOMBRE_MEDIDA' => '30X30',
            'TIPO_PRODUCTO' => 'ME',
            'PRECIO' => '$10.00',
        ]]);

        $response = $this->post('/admin/alta-producto/subir', ['excel' => $file]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_subir_template_generado_por_sistema(): void
    {
        Producto::create([
            'codigo' => 'ME100',
            'nombre' => 'PRODUCTO BASE',
            'precio' => 0,
            'unidad_venta' => 'PZA',
            'activo' => true,
        ]);

        $this->withSession($this->sesionAdmin());

        $templateResponse = $this->get('/admin/alta-producto/template');
        $templateResponse->assertOk();

        $templatePath = storage_path('app/test_template_generado.xlsx');
        file_put_contents($templatePath, $templateResponse->getContent());

        $ss = \PhpOffice\PhpSpreadsheet\IOFactory::load($templatePath);
        $ss->setActiveSheetIndex(0);
        $sheet = $ss->getSheetByName('Productos') ?? $ss->getActiveSheet();
        $sheet->setCellValue('A3', 'ME');
        $sheet->setCellValue('B3', '099');
        $sheet->setCellValue('C3', 'CAJA CORRUGADA');
        $sheet->setCellValue('F3', '40X30X25CM');
        $sheet->setCellValue('I3', 'ME');
        $sheet->setCellValue('K3', '$25.00');
        $sheet->setCellValue('L3', '25');
        $filledPath = storage_path('app/test_template_lleno.xlsx');
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss))->save($filledPath);

        $file = new UploadedFile($filledPath, 'template_lleno.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
        $response = $this->post('/admin/alta-producto/subir', ['excel' => $file]);

        $this->assertNotEquals(500, $response->getStatusCode(), 'Subida del template generado devolvió 500');
        $response->assertRedirect();
    }
}
