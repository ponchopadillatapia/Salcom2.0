<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Producto;
use App\Models\ProductoProveedorPrecio;
use App\Models\ProveedorUser;
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

    private function crearProveedor(): ProveedorUser
    {
        return ProveedorUser::create([
            'usuario' => 'provnac',
            'password' => Hash::make('test1234'),
            'nombre' => 'Proveedor Nacional',
            'correo' => 'provnac@test.com',
            'id_proveedor' => 'SAP-1001',
            'activo' => true,
        ]);
    }

    private function sesionAdminConProveedor(ProveedorUser $proveedor): array
    {
        return array_merge($this->sesionAdmin(), [
            'alta_nacional_proveedor_id' => $proveedor->id,
            'alta_nacional_proveedor_nombre' => $proveedor->nombre,
        ]);
    }

    private function crearExcelNacional(array $filas = []): UploadedFile
    {
        if (empty($filas)) {
            $filas = [[
                'PREFIJO' => 'ME',
                'CONSECUTIVO' => '0001',
                'NOMBRE_TIPO' => 'caja corrugada',
                'NOMBRE_MEDIDA' => '40x30x25cm',
                'TIPO_PRODUCTO' => 'me',
                'PRECIO' => '$150.50',
                'MOQ' => '100',
            ]];
        }

        $ss = new Spreadsheet();
        $sheet = $ss->getActiveSheet();
        $headers = ['PREFIJO', 'CONSECUTIVO', 'NOMBRE_TIPO', 'NOMBRE_MARCA', 'NOMBRE_MODELO', 'NOMBRE_MEDIDA', 'NOMBRE_ESPECIFICACION', 'FAMILIA', 'TIPO_PRODUCTO', 'UNIDAD_MEDIDA', 'PRECIO', 'MOQ', 'CLAVE_SAT', 'LOTE', 'PEDIMENTO', 'VOLTAJE'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col.'1', $h);
            $col++;
        }

        $row = 2;
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

    public function test_concatenar_proveedor_guarda_sesion(): void
    {
        $proveedor = $this->crearProveedor();

        $response = $this->withSession($this->sesionAdmin())
            ->post('/admin/alta-producto/concatenar-proveedor', [
                'proveedor_id' => $proveedor->id,
            ]);

        $response->assertRedirect(route('admin.alta-producto'));
        $response->assertSessionHas('alta_nacional_proveedor_id', $proveedor->id);
        $response->assertSessionHas('mensaje');
    }

    public function test_subir_excel_nacional_me_mp_no_da_500(): void
    {
        $proveedor = $this->crearProveedor();
        $this->withSession($this->sesionAdminConProveedor($proveedor));

        $file = $this->crearExcelNacional();

        $response = $this->post('/admin/alta-producto/subir', [
            'excel' => $file,
        ]);

        $this->assertNotEquals(500, $response->getStatusCode(), 'Subida nacional devolvió 500');
        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $producto = Producto::where('codigo', 'ME0001')->first();
        $this->assertNotNull($producto);
        $this->assertSame('CAJA CORRUGADA 40X30X25CM', preg_replace('/\s+/', ' ', trim($producto->nombre)));
        $this->assertSame(150.50, (float) $producto->precio);
        $this->assertSame('Admin Test', $producto->proveedor_nombre);

        $this->assertDatabaseHas('producto_proveedor_precios', [
            'producto_id' => $producto->id,
            'proveedor_id' => $proveedor->id,
            'precio' => 150.50,
            'moq' => 100,
        ]);
    }

    public function test_subir_excel_nacional_sin_proveedor_concatenado_falla(): void
    {
        $this->withSession($this->sesionAdmin());
        $file = $this->crearExcelNacional();

        $response = $this->post('/admin/alta-producto/subir', ['excel' => $file]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_subir_excel_nacional_actualiza_producto_activo_existente(): void
    {
        $proveedor = $this->crearProveedor();
        $producto = Producto::create([
            'codigo' => 'ME0002',
            'nombre' => 'PRODUCTO EXISTENTE',
            'precio' => 0,
            'unidad_venta' => 'PZA',
            'activo' => true,
        ]);

        $file = $this->crearExcelNacional([[
            'PREFIJO' => 'ME',
            'CONSECUTIVO' => '0002',
            'NOMBRE_TIPO' => 'CAJA',
            'NOMBRE_MEDIDA' => '30X30',
            'TIPO_PRODUCTO' => 'ME',
            'PRECIO' => '$10.00',
            'MOQ' => '50',
        ]]);

        $response = $this->withSession($this->sesionAdminConProveedor($proveedor))
            ->post('/admin/alta-producto/subir', [
                'excel' => $file,
            ]);

        $this->assertNotEquals(500, $response->getStatusCode());
        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('producto_proveedor_precios', [
            'producto_id' => $producto->id,
            'proveedor_id' => $proveedor->id,
        ]);
    }

    public function test_subir_excel_nacional_sin_moq_genera_error(): void
    {
        $proveedor = $this->crearProveedor();
        $this->withSession($this->sesionAdminConProveedor($proveedor));

        $file = $this->crearExcelNacional([[
            'PREFIJO' => 'ME',
            'CONSECUTIVO' => '0010',
            'NOMBRE_TIPO' => 'CAJA',
            'NOMBRE_MEDIDA' => '30X30',
            'TIPO_PRODUCTO' => 'ME',
            'PRECIO' => '$10.00',
        ]]);

        $response = $this->post('/admin/alta-producto/subir', [
            'excel' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_consecutivo_con_ceros_extra_se_normaliza_a_4_digitos(): void
    {
        $proveedor = $this->crearProveedor();
        $this->withSession($this->sesionAdminConProveedor($proveedor));

        $file = $this->crearExcelNacional([[
            'PREFIJO' => 'MP',
            'CONSECUTIVO' => '000003',
            'NOMBRE_TIPO' => 'BOLSA',
            'NOMBRE_MEDIDA' => '30X30',
            'TIPO_PRODUCTO' => 'MP',
            'PRECIO' => '$12.00',
            'MOQ' => '50',
        ]]);

        $response = $this->post('/admin/alta-producto/subir', [
            'excel' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('productos', ['codigo' => 'MP0003']);
    }

    public function test_subir_excel_nacional_reactiva_producto_inactivo(): void
    {
        $proveedor = $this->crearProveedor();
        Producto::create([
            'codigo' => 'ME0008',
            'nombre' => 'PRODUCTO INACTIVO',
            'precio' => 10,
            'unidad_venta' => 'PZA',
            'activo' => false,
        ]);

        $file = $this->crearExcelNacional([[
            'PREFIJO' => 'ME',
            'CONSECUTIVO' => '0008',
            'NOMBRE_TIPO' => 'CAJA NUEVA',
            'NOMBRE_MEDIDA' => '40X30',
            'TIPO_PRODUCTO' => 'ME',
            'PRECIO' => '$20.00',
            'MOQ' => '25',
        ]]);

        $response = $this->withSession($this->sesionAdminConProveedor($proveedor))
            ->post('/admin/alta-producto/subir', ['excel' => $file]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $producto = Producto::where('codigo', 'ME0008')->first();
        $this->assertTrue($producto->activo);
        $this->assertDatabaseHas('producto_proveedor_precios', [
            'producto_id' => $producto->id,
            'proveedor_id' => $proveedor->id,
        ]);
    }

    public function test_mismo_codigo_puede_vincular_otro_proveedor(): void
    {
        $proveedorA = $this->crearProveedor();
        $proveedorB = ProveedorUser::create([
            'usuario' => 'demo',
            'password' => Hash::make('test1234'),
            'nombre' => 'Proveedor Demo',
            'correo' => 'demo@test.com',
            'id_proveedor' => 'DEMO-001',
            'activo' => true,
        ]);

        $producto = Producto::create([
            'codigo' => 'ME0011',
            'nombre' => 'PRODUCTO ACTIVO',
            'precio' => 50,
            'unidad_venta' => 'PZA',
            'activo' => true,
        ]);

        ProductoProveedorPrecio::create([
            'producto_id' => $producto->id,
            'proveedor_id' => $proveedorA->id,
            'precio' => 50,
            'moq' => 10,
        ]);

        $file = $this->crearExcelNacional([[
            'PREFIJO' => 'ME',
            'CONSECUTIVO' => '0011',
            'NOMBRE_TIPO' => 'CAJA',
            'NOMBRE_MEDIDA' => '30X30',
            'TIPO_PRODUCTO' => 'ME',
            'PRECIO' => '$55.00',
            'MOQ' => '15',
        ]]);

        $response = $this->withSession($this->sesionAdminConProveedor($proveedorB))
            ->post('/admin/alta-producto/subir', ['excel' => $file]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('producto_proveedor_precios', [
            'producto_id' => $producto->id,
            'proveedor_id' => $proveedorB->id,
            'precio' => 55.00,
            'moq' => 15,
        ]);
    }

    public function test_subir_template_generado_por_sistema(): void
    {
        $proveedor = $this->crearProveedor();
        Producto::create([
            'codigo' => 'ME0100',
            'nombre' => 'PRODUCTO BASE',
            'precio' => 0,
            'unidad_venta' => 'PZA',
            'activo' => true,
        ]);

        $this->withSession($this->sesionAdminConProveedor($proveedor));

        $templateResponse = $this->get('/admin/alta-producto/template');
        $templateResponse->assertOk();

        $templatePath = storage_path('app/test_template_generado.xlsx');
        file_put_contents($templatePath, $templateResponse->getContent());

        $ss = \PhpOffice\PhpSpreadsheet\IOFactory::load($templatePath);
        $ss->setActiveSheetIndex(0);
        $sheet = $ss->getSheetByName('Productos') ?? $ss->getActiveSheet();
        $sheet->setCellValue('A2', 'ME');
        $sheet->setCellValue('B2', '0099');
        $sheet->setCellValue('C2', 'CAJA CORRUGADA');
        $sheet->setCellValue('F2', '40X30X25CM');
        $sheet->setCellValue('I2', 'ME');
        $sheet->setCellValue('K2', '$25.00');
        $sheet->setCellValue('L2', '25');
        $filledPath = storage_path('app/test_template_lleno.xlsx');
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss))->save($filledPath);

        $file = new UploadedFile($filledPath, 'template_lleno.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
        $response = $this->post('/admin/alta-producto/subir', [
            'excel' => $file,
        ]);

        $this->assertNotEquals(500, $response->getStatusCode(), 'Subida del template generado devolvió 500');
        $response->assertRedirect();
    }
}
