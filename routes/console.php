<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ═══════════════════════════════════════════════════════
// IA Proactiva — Comandos programados
// ═══════════════════════════════════════════════════════

// Diario 06:00 — Evaluar rendimiento de proveedores
Schedule::command('ia:evaluar-proveedores')->dailyAt('06:00');

// Diario 07:00 — Verificar vencimiento de documentos fiscales
Schedule::command('ia:verificar-documentos')->dailyAt('07:00');

// Cada 4 horas (horario laboral) — Verificar niveles de inventario
Schedule::command('ia:verificar-inventario')->everyFourHours()->between('8:00', '18:00');

// Lunes 05:00 — Generar pronósticos de demanda
Schedule::command('ia:generar-pronosticos')->weeklyOn(1, '05:00');

// Miércoles 08:00 — Generar sugerencias personalizadas
Schedule::command('ia:generar-sugerencias')->weeklyOn(3, '08:00');

// Trimestral — Generar OC masiva
Schedule::command('ia:oc-trimestral')->quarterly();

// Día 1 de cada mes 08:00 — Enviar aviso de opinión positiva a proveedores sin documento vigente
Schedule::command('salcom:aviso-opinion')->monthlyOn(1, '08:00');
