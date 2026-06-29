<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\AltaProductoController;

$ctrl = new AltaProductoController();
$validar = new ReflectionMethod($ctrl, 'validarProducto');
$validar->setAccessible(true);

$pass = 0;
$fail = 0;

function test($desc, $ok) {
    global $pass, $fail;
    if (!$ok) echo "FAIL | $desc" . PHP_EOL;
    $ok ? $pass++ : $fail++;
}

function tieneError($errores, $campo, $contiene = null) {
    foreach ($errores as $e) {
        if ($e['campo'] === $campo) {
            if ($contiene === null) return true;
            if (str_contains($e['error'], $contiene)) return true;
        }
    }
    return false;
}

function sinErrores($errores, $campos) {
    foreach ($campos as $c) {
        foreach ($errores as $e) {
            if ($e['campo'] === $c) return false;
        }
    }
    return true;
}

// ============================
// PT CON 6 CLASIFICACIONES — PRODUCTOS CORRECTOS
// ============================
echo "=== PT CORRECTOS ===" . PHP_EOL;

$ptBase = [
    'CODIGO' => 'EAEHO500', 'NOMBRE_TIPO' => 'AEROSOL HO', 'NOMBRE_MARCA' => 'WIESE',
    'NOMBRE_MODELO' => 'PREMIUM', 'NOMBRE_MEDIDA' => '323G C/12', 'NOMBRE_ESPECIFICACION' => 'NARANJA',
    'FAMILIA' => 'AEROSOLES', 'TIPO_PRODUCTO' => 'PT', 'UNIDAD_MEDIDA' => 'CAJA',
    'PRECIO' => '150.50', 'CLAVE_SAT' => '47131806', 'LOTE' => 'NO', 'PEDIMENTO' => 'NO', 'VOLTAJE' => '',
    'DEPARTAMENTO' => 'PT', 'LINEA' => 'Aerosoles', 'SUBFAMILIA' => 'Aerosol Hogar 400ml',
    'CANAL' => 'Autoservicio', 'VENDEDOR' => 'Jesus Sesma', 'MODULO' => 'AEROSOL',
];

$e = $validar->invoke($ctrl, $ptBase, 2);
test("PT completo con 6 clasif = sin error tipo/familia", !tieneError($e, 'TIPO_PRODUCTO', 'corresponde') && !tieneError($e, 'FAMILIA'));

// PT con diferentes codigos
$codigos_pt = ['EAEHO501','MAEHO502','NAEDC503','EARCO504','NTAST505','ETAAS506','MLILT507','NARCG508','EBRDR509','NDIEL510'];
foreach ($codigos_pt as $i => $cod) {
    $prod = $ptBase;
    $prod['CODIGO'] = $cod;
    $e = $validar->invoke($ctrl, $prod, $i+2);
    test("PT codigo $cod = sin error tipo", !tieneError($e, 'TIPO_PRODUCTO', 'corresponde'));
}

// PT con diferentes lineas
$lineas = ['Aerosoles','Breeze Matic','Canastilla','Clip On','Cono Gel','Dispensador','Gel Aromatizante','Hang Air','Insecticida','Lavatrastes'];
foreach ($lineas as $i => $lin) {
    $prod = $ptBase;
    $prod['CODIGO'] = 'EAEHO' . (600+$i);
    $prod['LINEA'] = $lin;
    $e = $validar->invoke($ctrl, $prod, $i+2);
    test("PT linea '$lin' = OK", !tieneError($e, 'LINEA'));
}

// PT con diferentes canales
$canales = ['Autoservicio','Abarrotera','Exportacion','Ferretero','Institucional','Mayoreo'];
foreach ($canales as $i => $can) {
    $prod = $ptBase;
    $prod['CODIGO'] = 'EAEHO' . (700+$i);
    $prod['CANAL'] = $can;
    $e = $validar->invoke($ctrl, $prod, $i+2);
    test("PT canal '$can' = OK", !tieneError($e, 'CANAL'));
}

// PT con diferentes vendedores
$vendedores = ['Alexandre Cominu','Ana Barrera','Francisco Alvarez','Guillermo Quiroz','Jesus Sesma','Jorge Ornelas','Luis Ibarra','Marco Vargas','Omar Garcia'];
foreach ($vendedores as $i => $ven) {
    $prod = $ptBase;
    $prod['CODIGO'] = 'EAEHO' . (800+$i);
    $prod['VENDEDOR'] = $ven;
    $e = $validar->invoke($ctrl, $prod, $i+2);
    test("PT vendedor '$ven' = OK", !tieneError($e, 'VENDEDOR'));
}

// PT con diferentes modulos
$modulos = ['AEROSOL','AROMATIZANTE','BREEZE MATIC','CANASTILLA','DISPENSADOR','INSECTICIDA','LAVATRASTES','LIMPIADOR','PASTILLA','TAPETE'];
foreach ($modulos as $i => $mod) {
    $prod = $ptBase;
    $prod['CODIGO'] = 'MAEHO' . (100+$i);
    $prod['MODULO'] = $mod;
    $e = $validar->invoke($ctrl, $prod, $i+2);
    test("PT modulo '$mod' = OK", !tieneError($e, 'MODULO'));
}

// PT con clasificaciones vacias (no obligatorias) — debe pasar OK
$prod = $ptBase;
$prod['DEPARTAMENTO'] = '';
$prod['LINEA'] = '';
$prod['SUBFAMILIA'] = '';
$prod['CANAL'] = '';
$prod['VENDEDOR'] = '';
$prod['MODULO'] = '';
$e = $validar->invoke($ctrl, $prod, 2);
test("PT sin clasificaciones = OK (no obligatorias)", sinErrores($e, ['DEPARTAMENTO','LINEA','SUBFAMILIA','CANAL','VENDEDOR','MODULO']));

// ============================
// MPI CON 6 CLASIFICACIONES
// ============================
echo PHP_EOL . "=== MPI CON CLASIFICACIONES ===" . PHP_EOL;

$mpiBase = [
    'CODIGO' => 'MPI0700', 'NOMBRE_TIPO' => 'FRAGANCIA', 'NOMBRE_MARCA' => 'IFF',
    'NOMBRE_MODELO' => 'BERGAMOT', 'NOMBRE_MEDIDA' => '25KG', 'NOMBRE_ESPECIFICACION' => 'CONCENTRADO',
    'FAMILIA' => '', 'TIPO_PRODUCTO' => 'MPI', 'UNIDAD_MEDIDA' => 'KG',
    'PRECIO' => '', 'CLAVE_SAT' => '', 'LOTE' => 'SI', 'PEDIMENTO' => 'SI', 'VOLTAJE' => '',
    'DEPARTAMENTO' => 'MPI', 'LINEA' => '', 'SUBFAMILIA' => '',
    'CANAL' => '', 'VENDEDOR' => '', 'MODULO' => '',
];

$e = $validar->invoke($ctrl, $mpiBase, 2);
test("MPI con clasif = sin error tipo", !tieneError($e, 'TIPO_PRODUCTO', 'corresponde'));
test("MPI con UNIDAD = sin error unidad", !tieneError($e, 'UNIDAD_MEDIDA'));
test("MPI sin FAMILIA = sin error (no obligatorio)", !tieneError($e, 'FAMILIA'));

// MPI con diferentes codigos
$codigos_mpi = ['MPI0701','MPI0702','FMPI010','FMPI020','EMPI0001','NMPI0001','MPIDA15','MPIVA10'];
foreach ($codigos_mpi as $i => $cod) {
    $prod = $mpiBase;
    $prod['CODIGO'] = $cod;
    $e = $validar->invoke($ctrl, $prod, $i+2);
    test("MPI codigo $cod = sin error tipo", !tieneError($e, 'TIPO_PRODUCTO', 'corresponde'));
}

// ============================
// ME CON 6 CLASIFICACIONES
// ============================
echo PHP_EOL . "=== ME CON CLASIFICACIONES ===" . PHP_EOL;

$meBase = [
    'CODIGO' => 'ME5000', 'NOMBRE_TIPO' => 'CAJA CORRUGADA', 'NOMBRE_MARCA' => 'KRAFT',
    'NOMBRE_MODELO' => 'CJ-500', 'NOMBRE_MEDIDA' => '30X30X25', 'NOMBRE_ESPECIFICACION' => 'BLANCA',
    'FAMILIA' => '', 'TIPO_PRODUCTO' => 'ME', 'UNIDAD_MEDIDA' => '',
    'PRECIO' => '', 'CLAVE_SAT' => '', 'LOTE' => '', 'PEDIMENTO' => '', 'VOLTAJE' => '',
    'DEPARTAMENTO' => 'ME', 'LINEA' => '', 'SUBFAMILIA' => '',
    'CANAL' => '', 'VENDEDOR' => '', 'MODULO' => '',
];

$e = $validar->invoke($ctrl, $meBase, 2);
test("ME con clasif = sin error tipo", !tieneError($e, 'TIPO_PRODUCTO', 'corresponde'));
test("ME sin FAMILIA ni UNIDAD = OK", !tieneError($e, 'FAMILIA') && !tieneError($e, 'UNIDAD_MEDIDA'));

$codigos_me = ['ME5001','ME5002','ME5003','ME5004','ME5005','ME5006','ME5007','ME5008','ME5009','ME5010'];
foreach ($codigos_me as $i => $cod) {
    $prod = $meBase;
    $prod['CODIGO'] = $cod;
    $e = $validar->invoke($ctrl, $prod, $i+2);
    test("ME codigo $cod = OK", !tieneError($e, 'TIPO_PRODUCTO', 'corresponde'));
}

// ============================
// MP CON 6 CLASIFICACIONES
// ============================
echo PHP_EOL . "=== MP CON CLASIFICACIONES ===" . PHP_EOL;

$mpBase = [
    'CODIGO' => 'MP2000', 'NOMBRE_TIPO' => 'DISPENSADOR', 'NOMBRE_MARCA' => 'ONE TOUCH',
    'NOMBRE_MODELO' => 'OT-200', 'NOMBRE_MEDIDA' => '1PZA', 'NOMBRE_ESPECIFICACION' => 'BLANCO',
    'FAMILIA' => '', 'TIPO_PRODUCTO' => 'MP', 'UNIDAD_MEDIDA' => '',
    'PRECIO' => '', 'CLAVE_SAT' => '', 'LOTE' => '', 'PEDIMENTO' => '', 'VOLTAJE' => '',
    'DEPARTAMENTO' => 'MP', 'LINEA' => 'Dispensador', 'SUBFAMILIA' => 'Dispensador',
    'CANAL' => '', 'VENDEDOR' => '', 'MODULO' => 'DISPENSADOR',
];

$e = $validar->invoke($ctrl, $mpBase, 2);
test("MP con clasif = OK", !tieneError($e, 'TIPO_PRODUCTO', 'corresponde'));

$codigos_mp = ['MP2001','MP2002','MP2003','MP2004','MP2005','MP2006','MP2007','MP2008','MP2009','MP2010'];
foreach ($codigos_mp as $i => $cod) {
    $prod = $mpBase;
    $prod['CODIGO'] = $cod;
    $e = $validar->invoke($ctrl, $prod, $i+2);
    test("MP codigo $cod = OK", !tieneError($e, 'TIPO_PRODUCTO', 'corresponde'));
}

// ============================
// ERRORES INTENCIONADOS CON CLASIFICACIONES
// ============================
echo PHP_EOL . "=== ERRORES INTENCIONADOS ===" . PHP_EOL;

// PT sin FAMILIA — error
$prod = $ptBase;
$prod['FAMILIA'] = '';
$e = $validar->invoke($ctrl, $prod, 2);
test("PT sin FAMILIA = error", tieneError($e, 'FAMILIA'));

// MPI sin UNIDAD — error
$prod = $mpiBase;
$prod['UNIDAD_MEDIDA'] = '';
$e = $validar->invoke($ctrl, $prod, 2);
test("MPI sin UNIDAD = error", tieneError($e, 'UNIDAD_MEDIDA'));

// Codigo MPI con tipo PT — error
$prod = $ptBase;
$prod['CODIGO'] = 'MPI0999';
$e = $validar->invoke($ctrl, $prod, 2);
test("MPI0999 + PT = error tipo", tieneError($e, 'TIPO_PRODUCTO', 'corresponde'));

// Codigo EAEHO con tipo ME — error
$prod = $meBase;
$prod['CODIGO'] = 'EAEHO999';
$e = $validar->invoke($ctrl, $prod, 2);
test("EAEHO999 + ME = error tipo", tieneError($e, 'TIPO_PRODUCTO', 'corresponde'));

// Codigo ME con tipo MPI — error
$prod = $mpiBase;
$prod['CODIGO'] = 'ME9999';
$e = $validar->invoke($ctrl, $prod, 2);
test("ME9999 + MPI = error tipo", tieneError($e, 'TIPO_PRODUCTO', 'corresponde'));

// Codigo RP con tipo PT — error
$prod = $ptBase;
$prod['CODIGO'] = 'RP9999';
$e = $validar->invoke($ctrl, $prod, 2);
test("RP9999 + PT = error tipo", tieneError($e, 'TIPO_PRODUCTO', 'corresponde'));

// Codigo 550 con tipo ME — error
$prod = $meBase;
$prod['CODIGO'] = '550999';
$e = $validar->invoke($ctrl, $prod, 2);
test("550999 + ME = error tipo", tieneError($e, 'TIPO_PRODUCTO', 'corresponde'));

// Codigo HER con tipo PT — error
$prod = $ptBase;
$prod['CODIGO'] = 'HER999';
$e = $validar->invoke($ctrl, $prod, 2);
test("HER999 + PT = error tipo", tieneError($e, 'TIPO_PRODUCTO', 'corresponde'));

// Codigo desconocido con cualquier tipo = OK
$tipos = ['MPI','ME','MP','PT','MN','RP','HERRAMIENTAS','MAQUINARIA','CONTABLE','GASTOS'];
foreach ($tipos as $tipo) {
    $prod = $ptBase;
    $prod['CODIGO'] = 'XRANDOM' . rand(1,999);
    $prod['TIPO_PRODUCTO'] = $tipo;
    if ($tipo === 'MPI') $prod['UNIDAD_MEDIDA'] = 'KG';
    if ($tipo === 'PT') $prod['FAMILIA'] = 'AEROSOLES';
    $e = $validar->invoke($ctrl, $prod, 2);
    test("Codigo random + tipo $tipo = sin error tipo", !tieneError($e, 'TIPO_PRODUCTO', 'corresponde'));
}

// ============================
// MASIVO: 50 productos PT con variaciones
// ============================
echo PHP_EOL . "=== MASIVO PT ===" . PHP_EOL;

$prefijos_pt = ['EAEHO','MAEHO','NAEHO','EAEDC','MAEDC','NAEDC','EARCO','NARCG','NTAST','ETAAS',
    'ETACP','ETALI','EARCG','EARGE','EARHA','EBRDR','MDIEL','MLILT','MNOCA','NDIEL',
    'NLILG','NNOCA','NPAAL','NPABA','NTACP','ELILG','EPAAL','EPABA','MPAAL','MPARE',
    'MPCCL','MTAAS','MTACP','MTALI','EAEIN','EAEMC','EAEMS','NAEMS','NARCO','NARGE',
    'NARHA','NBRDR','NDIER','NLILS','NLILT','NREAU','NTALI','EPAMI','EDIDC','EDILG'];

foreach ($prefijos_pt as $i => $pref) {
    $prod = $ptBase;
    $prod['CODIGO'] = $pref . ($i + 900);
    $e = $validar->invoke($ctrl, $prod, $i+2);
    test("PT prefijo $pref = OK", !tieneError($e, 'TIPO_PRODUCTO', 'corresponde'));
}

// ============================
// RESULTADO
// ============================
echo PHP_EOL . "==============================" . PHP_EOL;
echo "=== $pass passed, $fail failed ===" . PHP_EOL;
if ($fail === 0) echo "TODAS LAS PRUEBAS PASARON" . PHP_EOL;
else echo "HAY ERRORES — REVISAR ARRIBA" . PHP_EOL;
