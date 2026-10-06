<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Guarda documentos de agentes en el disco privado.
 * Rechaza e.firma, certificados y llaves. El nombre en disco es un UUID.
 */
class AgenteAduanalArchivoService
{
    public const DISCO = 'local';

    public const MAX_KB = 10240;

    private const MIMES = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];

    public function mensajeSiInvalido(UploadedFile $archivo): ?string
    {
        if (! $archivo->isValid()) {
            return 'No se pudo leer el archivo.';
        }

        $nombre = strtolower(str_replace('\\', '/', $archivo->getClientOriginalName()));
        $nombre = basename($nombre);

        if (str_contains($nombre, '..') || str_contains($archivo->getClientOriginalName(), "\0")) {
            return 'El nombre del archivo no es válido.';
        }

        if (preg_match('/\.(key|cer|pfx|p12|pem|crt)(\.|$)/', $nombre)) {
            return 'No se aceptan certificados, llaves privadas ni archivos .key, .cer, .pfx o .p12.';
        }

        if (preg_match('/e[\.\s_-]?firma|\bfiel\b|llave[\s_-]?privada|private[\s_-]?key/i', $nombre)) {
            return 'No se almacena la e.firma, la FIEL ni llaves privadas.';
        }

        $partes = explode('.', $nombre);
        $extension = (string) array_pop($partes);
        if (! in_array($extension, ['pdf', 'jpg', 'jpeg', 'png'], true)) {
            return 'Solo se aceptan PDF, JPG y PNG.';
        }

        foreach ($partes as $trozo) {
            if (in_array($trozo, ['key', 'cer', 'pfx', 'p12', 'pem', 'crt'], true)) {
                return 'No se aceptan certificados, llaves privadas ni archivos .key, .cer, .pfx o .p12.';
            }
        }

        $mime = (string) $archivo->getMimeType();
        if (! isset(self::MIMES[$mime])) {
            return 'El contenido del archivo no es un PDF, JPG o PNG.';
        }

        if ($archivo->getSize() > self::MAX_KB * 1024) {
            return 'El archivo supera el límite de 10 MB.';
        }

        return null;
    }

    /**
     * @return array{ruta: string, nombre_archivo: string}
     */
    public function guardar(UploadedFile $archivo, string $directorio): array
    {
        $mensaje = $this->mensajeSiInvalido($archivo);
        if ($mensaje !== null) {
            throw new \InvalidArgumentException($mensaje);
        }

        $extension = self::MIMES[(string) $archivo->getMimeType()];
        $nombreDisco = (string) Str::uuid().'.'.$extension;
        $directorio = trim(str_replace(['..', '\\'], ['', '/'], $directorio), '/');
        $ruta = $archivo->storeAs($directorio, $nombreDisco, self::DISCO);

        if (! is_string($ruta) || $ruta === '' || str_contains($ruta, '..')) {
            throw new \RuntimeException('No se pudo guardar el archivo.');
        }

        return [
            'ruta' => $ruta,
            'nombre_archivo' => $this->nombreVisible($archivo, $extension),
        ];
    }

    public function eliminar(?string $ruta): void
    {
        if (! $this->rutaSegura($ruta)) {
            return;
        }

        Storage::disk(self::DISCO)->delete($ruta);
    }

    public function rutaSegura(?string $ruta): bool
    {
        if (! is_string($ruta) || $ruta === '') {
            return false;
        }

        if (str_contains($ruta, '..') || str_contains($ruta, '\\') || str_starts_with($ruta, '/')) {
            return false;
        }

        return str_starts_with($ruta, 'agentes-aduanales/');
    }

    private function nombreVisible(UploadedFile $archivo, string $extension): string
    {
        $original = basename(str_replace('\\', '/', $archivo->getClientOriginalName()));
        $original = str_replace(['"', "\r", "\n", '<', '>', "\0"], '', $original);
        $original = trim($original);

        if ($original === '' || $original === '.' || $original === '..') {
            return 'documento.'.$extension;
        }

        return mb_substr($original, 0, 180);
    }
}
