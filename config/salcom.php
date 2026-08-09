<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Usuarios internos que NO requieren confirmar correo (portal proveedor)
    |--------------------------------------------------------------------------
    | Comparación case-insensitive contra `usuario` o `correo` (parte local
    | antes de @). Staff / demo / admins de prueba.
    */
    'usuarios_sin_confirmar_correo' => [
        // Staff Wiese / Salcom
        'cintia.barrera',
        'brenda.pliego',
        'blanca.paganoni',
        'acela.bolanos',
        'fredcominu',
        'alex.salazar',
        'jesus.espinoza',
        'cinthya.martinez',
        'sandra.gutierrez',
        'aneso.cominu',
        'karen.bravo',
        // Demo
        'prov001',
        'cli001',
        // Códigos espejo admin en portal
        // (también se marca por usuario del AdminUser)
    ],

];
