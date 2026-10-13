<?php

return [
    // POR QUÉ solo BBVA: es la ÚNICA cuenta que existe de verdad en la base (clave_corta 8969,
    // "BBVA 969 SALCOM PESOS"). Antes el menú listaba 7 bancos, pero 6 no tenían cuenta y el
    // sistema redirigía en silencio a BBVA: Karen podía entrar a "Santander" y ver la hoja
    // vacía sin entender por qué. Mostrar solo lo que funciona evita esa confusión.
    // Cuando existan las otras cuentas (con su registro en cuentas_bancarias y su mapeo en
    // AdminPanelController::claveCuentaWiese), se agregan aquí.
    'bbva' => 'BBVA 8969 MXN',
];
