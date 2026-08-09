<?php

namespace App\Http\Controllers;

use App\Exceptions\ProveedorApiException;
use App\Http\Requests\LoginProveedorRequest;
use App\Mail\BienvenidaProveedor;
use App\Models\AdminUser;
use App\Models\ProveedorUser;
use App\Services\AlertEngineService;
use App\Services\ProveedorApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

class AuthProveedorController extends Controller
{
    private ProveedorApiService $apiService;

    public function __construct(ProveedorApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    public function mostrarLogin()
    {
        if (session('proveedor_id')) {
            return $this->redirectTrasLoginProveedor(session('proveedor_nombre', 'Proveedor'));
        }

        return view('proveedores.login');
    }

    public function mostrarRegistro()
    {
        return view('proveedores.registro');
    }

    public function procesarLogin(LoginProveedorRequest $request)
    {
        $rateLimitKey = 'login-proveedor|'.$request->ip();
        $maxAttempts = config('auth.rate_limiting.max_attempts', 5);
        $decaySeconds = config('auth.rate_limiting.decay_seconds', 60);

        if (RateLimiter::tooManyAttempts($rateLimitKey, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            Log::warning('Login bloqueado por rate limiting', [
                'ip' => $request->ip(),
                'segundos_restantes' => $seconds,
            ]);

            return back()
                ->with('error', "Demasiados intentos de inicio de sesión. Intenta de nuevo en {$seconds} segundos.")
                ->withInput();
        }

        $codigo = $request->codigo;
        $pwd = $request->pwd;
        $modo = $this->getLoginMode();

        if ($modo === 'local') {
            $datos = $this->loginViaLocal($codigo, $pwd);

            return $this->respuestaLoginLocal($datos, $rateLimitKey, $decaySeconds, $codigo);
        }

        $apiResult = $this->apiService->loginApi($codigo, $pwd);

        if ($apiResult['success']) {
            $datos = $this->loginViaApi($apiResult);
            RateLimiter::clear($rateLimitKey);
            $this->guardarSesion($datos, 'api', $datos['token']);
            Log::info('Login: exitoso por API', ['codigo' => $codigo]);

            return $this->redirectTrasLoginProveedor($datos['nombre']);
        }

        $errorType = $apiResult['error_type'] ?? '';

        if ($errorType === ProveedorApiException::AUTENTICACION_FALLIDA) {
            RateLimiter::hit($rateLimitKey, $decaySeconds);
            Log::error('Login: credenciales rechazadas por API', ['codigo' => $codigo]);

            return back()->with('error', 'Credenciales incorrectas')->withInput();
        }

        if ($modo === 'api') {
            RateLimiter::hit($rateLimitKey, $decaySeconds);
            Log::error('Login: API no disponible, sin fallback', ['codigo' => $codigo, 'error_type' => $errorType, 'modo' => 'api']);

            return back()->with('error', $apiResult['message'])->withInput();
        }

        $erroresFallback = [
            ProveedorApiException::API_CAIDA, ProveedorApiException::TIMEOUT,
            ProveedorApiException::ERROR_SERVIDOR, ProveedorApiException::ERROR_DESCONOCIDO,
        ];

        if (in_array($errorType, $erroresFallback)) {
            Log::warning('Login: fallback a BD local', ['codigo' => $codigo, 'error_type' => $errorType]);
            $datos = $this->loginViaLocal($codigo, $pwd);

            return $this->respuestaLoginLocal($datos, $rateLimitKey, $decaySeconds, $codigo);
        }

        RateLimiter::hit($rateLimitKey, $decaySeconds);
        Log::error('Login: error no contemplado', ['codigo' => $codigo, 'error_type' => $errorType]);

        return back()->with('error', $apiResult['message'])->withInput();
    }

    public function guardar(Request $request)
    {
        try {
            $esMoral = $request->input('tipo_persona') === 'Persona Moral';

            $request->validate([
                'tipo_persona' => 'required|in:Persona Física,Persona Moral',
                'nombres' => ($esMoral ? 'nullable' : 'required').'|string|max:150',
                'apellido_paterno' => ($esMoral ? 'nullable' : 'required').'|string|max:100',
                'apellido_materno' => 'nullable|string|max:100',
                'razon_social' => ($esMoral ? 'required' : 'nullable').'|string|max:255',
                'telefono' => 'required|string|max:20',
                'correo' => 'required|email',
                'password' => 'required|min:8|confirmed',
            ], [
                'tipo_persona.required' => 'Selecciona el tipo de persona.',
                'nombres.required' => 'El nombre es obligatorio.',
                'apellido_paterno.required' => 'El apellido paterno es obligatorio.',
                'razon_social.required' => 'La razón social es obligatoria.',
                'correo.required' => 'El correo es obligatorio.',
                'correo.email' => 'El correo no es válido.',
                'password.required' => 'La contraseña es obligatoria.',
                'password.min' => 'La contraseña debe tener mínimo 8 caracteres.',
                'password.confirmed' => 'Las contraseñas no coinciden.',
            ]);

            $correo = strtolower(trim((string) $request->correo));

            // Verificar si ya existe por correo
            $existe = DB::table('proveedores_users')
                ->where('correo', $correo)
                ->orWhere('usuario', $correo)
                ->exists();

            if ($existe) {
                return back()->withErrors(['correo' => 'Este correo ya está registrado.'])->withInput();
            }

            $recaptchaSecret = config('services.recaptcha.secret_key');
            if ($recaptchaSecret) {
                $recaptcha = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                    'secret' => $recaptchaSecret, 'response' => $request->input('g-recaptcha-response'), 'remoteip' => $request->ip(),
                ])->json();
                if (! ($recaptcha['success'] ?? false)) {
                    return back()->withErrors(['g-recaptcha-response' => 'Captcha inválido'])->withInput();
                }
            }

            if ($esMoral) {
                $nombre = trim((string) $request->razon_social);
                $baseUsuario = $this->slugUsuarioRazonSocial($nombre);
            } else {
                $nombres = trim((string) $request->nombres);
                $apellidoPaterno = trim((string) $request->apellido_paterno);
                $apellidoMaterno = trim((string) ($request->apellido_materno ?? ''));
                $nombre = trim(implode(' ', array_filter([$nombres, $apellidoPaterno, $apellidoMaterno])));
                $baseUsuario = $this->slugUsuarioPersona($nombres, $apellidoPaterno);
            }

            if ($baseUsuario === '') {
                $baseUsuario = 'proveedor';
            }

            $usuario = $this->generarUsuarioUnico($baseUsuario, $correo);

            // Insertar proveedor (correo pendiente de verificación)
            $insert = [
                'usuario' => $usuario,
                'password' => bcrypt($request->password),
                'nombre' => $nombre,
                'tipo_persona' => $request->tipo_persona,
                'telefono' => $request->telefono,
                'correo' => $correo,
                'activo' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            if (Schema::hasColumn('proveedores_users', 'correo_verified_at')) {
                $insert['correo_verified_at'] = null;
            }
            $proveedorId = DB::table('proveedores_users')->insertGetId($insert);

            $this->enviarBienvenidaRegistro(
                (int) $proveedorId,
                $nombre,
                $correo,
                $usuario
            );

            return redirect('/login-proveedor')->with(
                'mensaje',
                'Registro exitoso. Revisa tu correo y confirma tu cuenta antes de iniciar sesión.'
            );

        } catch (ValidationException $e) {
            throw $e; // Re-lanzar para que Laravel muestre los errores de validación
        } catch (\Exception $e) {
            Log::error('Error registro proveedor: '.$e->getMessage().' | '.$e->getFile().':'.$e->getLine());

            return back()->withErrors(['general' => 'Error al registrar. Intenta de nuevo. ('.class_basename($e).')'])->withInput();
        }
    }

    public function mostrarActualizacion()
    {
        $proveedor = ProveedorUser::find(session('proveedor_id'));

        return view('proveedores.actualizacion', compact('proveedor'));
    }

    public function guardarActualizacion(Request $request)
    {
        $proveedor = ProveedorUser::find(session('proveedor_id'));
        if (! $proveedor) {
            return redirect()->route('proveedores.perfil')->with('error', 'Proveedor no encontrado.');
        }

        if ($proveedor->tipoPersonaBloqueado()) {
            $enviado = $this->normalizarTipoPersonaLocal((string) $request->input('tipo_persona', ''));
            $actual = $proveedor->tipoPersonaNormalizado();
            if ($enviado !== '' && $enviado !== $actual) {
                return back()->withErrors([
                    'tipo_persona' => 'El tipo de persona ya quedó fijado y no se puede cambiar (como en el SAT). Si hay un error de registro, contacta a Compras.',
                ])->withInput();
            }
            $request->merge(['tipo_persona' => $actual]);
        }

        $request->validate([
            'nombre' => 'required|string|max:255', 'tipo_persona' => 'required|string|max:255',
            'telefono' => 'required|string|max:20', 'correo' => 'required|email',
            'password' => 'nullable|min:8|confirmed',
        ], [
            'nombre.required' => 'El nombre es obligatorio.', 'telefono.required' => 'El teléfono es obligatorio.',
            'correo.required' => 'El correo es obligatorio.', 'correo.email' => 'El correo no es válido.',
            'password.min' => 'La contraseña debe tener mínimo 8 caracteres.', 'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        $data = [
            'nombre' => $request->nombre,
            'telefono' => $request->telefono,
            'correo' => $request->correo,
        ];
        if (! $proveedor->tipoPersonaBloqueado()) {
            $data['tipo_persona'] = $request->tipo_persona;
        }
        $proveedor->update($data);
        if ($request->password) {
            $proveedor->update(['password' => bcrypt($request->password)]);
        }

        // Mantener el header / sesión alineados con el perfil
        session([
            'proveedor_nombre' => $proveedor->nombre,
            'proveedor_correo' => $proveedor->correo,
        ]);

        return redirect()->route('proveedores.perfil')->with('mensaje', 'Datos actualizados correctamente.');
    }

    private function normalizarTipoPersonaLocal(string $tipo): string
    {
        $tipo = trim($tipo);
        if ($tipo === 'Persona Física' || $tipo === 'Persona Moral') {
            return $tipo;
        }
        $lower = mb_strtolower($tipo);
        if (str_contains($lower, 'moral')) {
            return 'Persona Moral';
        }
        if (str_contains($lower, 'fís') || str_contains($lower, 'fis')) {
            return 'Persona Física';
        }

        return $tipo;
    }

    public function cerrarSesion()
    {
        session()->forget(['proveedor_id', 'proveedor_nombre', 'proveedor_codigo', 'proveedor_correo', 'proveedor_token', 'proveedor_login_source']);

        return redirect('/login-proveedor')->with('mensaje', 'Sesión cerrada correctamente');
    }

    /**
     * Confirma el correo del proveedor mediante enlace firmado del mail de bienvenida.
     */
    public function verificarCorreo(Request $request, int $id)
    {
        if (! $request->hasValidSignature()) {
            return redirect('/login-proveedor')
                ->with('error', 'El enlace de verificación no es válido o ya expiró. Si necesitas uno nuevo, contacta a Compras.');
        }

        $proveedor = ProveedorUser::find($id);
        if (! $proveedor) {
            return redirect('/login-proveedor')
                ->with('error', 'No se encontró la cuenta asociada a este enlace.');
        }

        if (! $proveedor->hasVerifiedCorreo()) {
            $proveedor->markCorreoAsVerified();
        }

        return redirect('/login-proveedor')->with(
            'mensaje',
            'Correo confirmado. Ya puedes iniciar sesión con tu usuario o correo.'
        );
    }

    // ── Private helpers ──

    private function loginViaApi(array $apiResult): array
    {
        $data = $apiResult['data'];

        return ['id' => $data['usuario'] ?? null, 'nombre' => $data['usuario'] ?? 'Proveedor', 'codigo' => $data['usuario'] ?? null, 'correo' => $data['usuario'] ?? null, 'token' => $data['tokencreado'] ?? null];
    }

    private function loginViaLocal(string $codigo, string $pwd): ?array
    {
        $proveedor = ProveedorUser::where(function ($q) use ($codigo) {
            $q->where('usuario', $codigo)->orWhere('correo', $codigo);
        })->first();
        if ($proveedor && Hash::check($pwd, $proveedor->password)) {
            if (! $proveedor->hasVerifiedCorreo()) {
                return ['error' => 'correo_no_verificado'];
            }

            // Persistimos la excepción staff (si aplica) para que no dependa solo de config.
            if (
                ProveedorUser::tieneColumnaCorreoVerified()
                && $proveedor->correo_verified_at === null
                && $proveedor->estaExentoDeConfirmarCorreo()
            ) {
                $proveedor->markCorreoAsVerified();
            }

            return ['id' => $proveedor->id, 'nombre' => $proveedor->nombre, 'codigo' => $proveedor->id_proveedor, 'correo' => $proveedor->correo, 'token' => null];
        }

        // Admins y staff interno pueden entrar al portal de proveedores (cuenta espejo).
        // Rol admin: siempre. Otros roles: solo si están en la lista sin confirmar correo.
        $admin = AdminUser::where(function ($q) use ($codigo) {
            $q->where('usuario', $codigo)->orWhere('correo', $codigo);
        })->first();

        if ($admin && Hash::check($pwd, $admin->password) && $admin->activo) {
            $puedePortal = $admin->rol === 'admin'
                || ProveedorUser::usuarioExentoDeConfirmarCorreo($admin->usuario)
                || ProveedorUser::usuarioExentoDeConfirmarCorreo($admin->correo);

            if ($puedePortal) {
                $proveedor = $this->asegurarProveedorEspejoAdmin($admin);

                return [
                    'id' => $proveedor->id,
                    'nombre' => $proveedor->nombre ?? $admin->nombre,
                    'codigo' => $proveedor->id_proveedor ?? $proveedor->codigo_compras ?? ('ADMIN-'.$admin->id),
                    'correo' => $proveedor->correo ?? $admin->correo,
                    'token' => null,
                ];
            }
        }

        return null;
    }

    private function respuestaLoginLocal(?array $datos, string $rateLimitKey, int $decaySeconds, string $codigo)
    {
        if (is_array($datos) && ($datos['error'] ?? null) === 'correo_no_verificado') {
            RateLimiter::clear($rateLimitKey);

            return back()
                ->with('error', 'Debes confirmar tu correo antes de iniciar sesión. Revisa tu bandeja de entrada (y spam).')
                ->withInput();
        }

        if ($datos) {
            RateLimiter::clear($rateLimitKey);
            $this->guardarSesion($datos, 'local', null);

            return $this->redirectTrasLoginProveedor($datos['nombre']);
        }

        RateLimiter::hit($rateLimitKey, $decaySeconds);
        Log::error('Login: fallo local', ['codigo' => $codigo]);

        return back()->with('error', 'Credenciales incorrectas')->withInput();
    }

    private function asegurarProveedorEspejoAdmin(AdminUser $admin): ProveedorUser
    {
        $existente = ProveedorUser::where('usuario', $admin->usuario)->first();
        if ($existente) {
            if (ProveedorUser::tieneColumnaCorreoVerified() && $existente->correo_verified_at === null) {
                $existente->markCorreoAsVerified();
            }
            if (! $existente->activo) {
                $existente->update(['activo' => true]);
            }

            return $existente->fresh();
        }

        $datos = [
            'usuario' => $admin->usuario,
            'nombre' => $admin->nombre,
            'correo' => $admin->correo ?: ($admin->usuario.'@salcom.local'),
            'password' => $admin->password,
            'tipo_persona' => 'Persona Moral',
            'activo' => true,
        ];
        if (ProveedorUser::tieneColumnaCorreoVerified()) {
            $datos['correo_verified_at'] = now();
        }

        if (Schema::hasColumn('proveedores_users', 'id_proveedor')) {
            $datos['id_proveedor'] = 'ADMIN-'.$admin->id;
        } elseif (Schema::hasColumn('proveedores_users', 'codigo_compras')) {
            $datos['codigo_compras'] = 'ADMIN-'.$admin->id;
        }

        $proveedor = new ProveedorUser;
        $proveedor->forceFill($datos)->save();

        return $proveedor->fresh();
    }

    private function redirectTrasLoginProveedor(string $nombre)
    {
        $proveedor = ProveedorUser::find(session('proveedor_id'));
        if ($proveedor && ! $proveedor->activo) {
            return redirect()->route('proveedores.onboarding')
                ->with('mensaje', 'Bienvenido '.$nombre.'. Completa tu onboarding para que Dirección active tu cuenta.');
        }

        return redirect('/portal-proveedor')->with('mensaje', 'Bienvenido '.$nombre);
    }

    private function guardarSesion(array $datos, string $source, ?string $token): void
    {
        session(['proveedor_id' => $datos['id'], 'proveedor_nombre' => $datos['nombre'], 'proveedor_codigo' => $datos['codigo'], 'proveedor_correo' => $datos['correo'], 'proveedor_token' => $token, 'proveedor_login_source' => $source]);
    }

    private function getLoginMode(): string
    {
        $modo = config('services.proveedor_api.login_mode', 'fallback');

        return in_array($modo, ['api', 'local', 'fallback']) ? $modo : 'fallback';
    }

    /**
     * Correo de bienvenida con enlace de verificación + alerta en la campana del portal.
     */
    private function enviarBienvenidaRegistro(int $proveedorId, string $nombre, string $correo, string $usuario): void
    {
        $titulo = '¡Bienvenido al Portal de Proveedores!';
        $contenido = 'Hola '.$nombre.'. Tu registro fue exitoso. Confirma tu correo para poder iniciar sesión. Tu usuario es '.$usuario.'.';

        try {
            app(AlertEngineService::class)->crearAlerta([
                'tipo' => 'bienvenida',
                'modulo' => 'onboarding',
                'destinatario_tipo' => 'proveedor',
                'destinatario_id' => $proveedorId,
                'titulo' => $titulo,
                'contenido' => $contenido,
                'nivel' => 'info',
            ]);
        } catch (\Exception $e) {
            Log::warning('No se pudo crear alerta de bienvenida', [
                'proveedor_id' => $proveedorId,
                'error' => $e->getMessage(),
            ]);
        }

        $urlVerificacion = URL::temporarySignedRoute(
            'proveedores.verificar-correo',
            now()->addHours(48),
            ['id' => $proveedorId]
        );

        // El correo se envía después de responder al navegador para no retrasar el registro.
        dispatch(function () use ($nombre, $correo, $usuario, $urlVerificacion) {
            try {
                Mail::to($correo)->send(new BienvenidaProveedor($nombre, $correo, $usuario, $urlVerificacion));
            } catch (\Exception $e) {
                Log::warning('No se pudo enviar correo de bienvenida', [
                    'correo' => $correo,
                    'error' => $e->getMessage(),
                ]);
            }
        })->afterResponse();
    }

    private function slugUsuarioPersona(string $nombres, string $apellidoPaterno): string
    {
        $primerNombre = explode(' ', trim($nombres))[0] ?? '';
        $n = $this->slugParte($primerNombre);
        $a = $this->slugParte($apellidoPaterno);

        if ($n !== '' && $a !== '') {
            return $n.'.'.$a;
        }

        return $n !== '' ? $n : $a;
    }

    private function slugUsuarioRazonSocial(string $razonSocial): string
    {
        $slug = $this->quitarAcentos(mb_strtolower(trim($razonSocial)));
        $slug = preg_replace('/[^a-z0-9]+/', '.', $slug) ?? '';
        $slug = trim($slug, '.');
        $slug = preg_replace('/\.{2,}/', '.', $slug) ?? '';

        return substr($slug, 0, 40);
    }

    private function slugParte(string $texto): string
    {
        $slug = $this->quitarAcentos(mb_strtolower(trim($texto)));
        $slug = preg_replace('/[^a-z0-9]+/', '', $slug) ?? '';

        return substr($slug, 0, 40);
    }

    private function quitarAcentos(string $texto): string
    {
        $map = [
            'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a', 'ã' => 'a',
            'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
            'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o', 'õ' => 'o',
            'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
            'ñ' => 'n', 'ç' => 'c',
            'Á' => 'a', 'À' => 'a', 'Ä' => 'a', 'Â' => 'a', 'Ã' => 'a',
            'É' => 'e', 'È' => 'e', 'Ë' => 'e', 'Ê' => 'e',
            'Í' => 'i', 'Ì' => 'i', 'Ï' => 'i', 'Î' => 'i',
            'Ó' => 'o', 'Ò' => 'o', 'Ö' => 'o', 'Ô' => 'o', 'Õ' => 'o',
            'Ú' => 'u', 'Ù' => 'u', 'Ü' => 'u', 'Û' => 'u',
            'Ñ' => 'n', 'Ç' => 'c',
        ];

        return strtr($texto, $map);
    }

    private function generarUsuarioUnico(string $base, string $correo): string
    {
        $candidato = $base;
        $i = 2;

        while (
            DB::table('proveedores_users')
                ->where(function ($q) use ($candidato) {
                    $q->where('usuario', $candidato)->orWhere('correo', $candidato);
                })
                ->exists()
        ) {
            $candidato = $base.$i;
            $i++;
            if ($i > 9999) {
                $candidato = $base.'.'.substr(md5($correo.microtime()), 0, 6);
                break;
            }
        }

        return $candidato;
    }
}
