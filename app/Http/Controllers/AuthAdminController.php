<?php

namespace App\Http\Controllers;

use App\Models\AdminUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class AuthAdminController extends Controller
{
    public function mostrarLogin()
    {
        if (session('admin_id')) {
            return redirect('/admin/dashboard');
        }

        return view('admin.login');
    }

    public function procesarLogin(Request $request)
    {
        $request->validate([
            'usuario' => 'required|string',
            'password' => 'required|string',
        ]);

        $key = 'login-admin|'.$request->ip();
        $max = config('auth.rate_limiting.max_attempts', 5);
        $decay = config('auth.rate_limiting.decay_seconds', 60);

        if (RateLimiter::tooManyAttempts($key, $max)) {
            $seconds = RateLimiter::availableIn($key);
            Log::warning('Login admin bloqueado por rate limiting', [
                'ip' => $request->ip(),
                'segundos' => $seconds,
            ]);

            return back()
                ->with('error', "Demasiados intentos. Intenta en {$seconds} segundos.")
                ->withInput();
        }

        $admin = AdminUser::where('usuario', $request->usuario)
            ->orWhere('correo', $request->usuario)
            ->first();

        if (! $admin || ! Hash::check($request->password, $admin->password)) {
            RateLimiter::hit($key, $decay);
            Log::error('Login admin: credenciales incorrectas', ['usuario' => $request->usuario]);

            return back()->with('error', 'Credenciales incorrectas')->withInput();
        }

        if (! $admin->activo) {
            RateLimiter::hit($key, $decay);

            return back()->with('error', 'Tu cuenta está desactivada. Contacta al administrador.')->withInput();
        }

        RateLimiter::clear($key);

        session([
            'admin_id' => $admin->id,
            'admin_nombre' => $admin->nombre,
            'admin_correo' => $admin->correo,
            'admin_usuario' => $admin->usuario,
            'admin_rol' => $admin->rol,
        ]);

        Log::info('Login admin exitoso', ['usuario' => $admin->usuario, 'rol' => $admin->rol]);

        // Redirigir según rol
        $redirect = match ($admin->rol) {
            'materia_prima' => '/admin/materia-prima',
            'material_empaque' => '/admin/material-empaque',
            default => '/admin/dashboard',
        };

        return redirect($redirect)->with('mensaje', 'Bienvenido '.$admin->nombre);
    }

    public function cerrarSesion()
    {
        session()->forget([
            'admin_id', 'admin_nombre', 'admin_correo', 'admin_usuario', 'admin_rol',
        ]);

        return redirect('/login-admin')->with('mensaje', 'Sesión cerrada correctamente');
    }

    public function mostrarPerfil()
    {
        $admin = AdminUser::find(session('admin_id'));

        return view('admin.perfil', [
            'admin' => $admin,
            'rolEtiqueta' => $this->etiquetaRol($admin !== null ? $admin->rol : session('admin_rol')),
        ]);
    }

    public function mostrarAdministradores()
    {
        if (! $this->esRolAdminPrincipal()) {
            return redirect()->route('admin.perfil')->with('error', 'No tienes permiso para gestionar administradores.');
        }

        $administradores = AdminUser::orderBy('nombre')->get();

        return view('admin.administradores', [
            'administradores' => $administradores,
            'rolesDisponibles' => $this->rolesDisponibles(),
        ]);
    }

    public function guardarAdministrador(Request $request)
    {
        if (! $this->esRolAdminPrincipal()) {
            return redirect()->route('admin.perfil')->with('error', 'No tienes permiso para gestionar administradores.');
        }

        $rolesPermitidos = array_keys($this->rolesDisponibles());

        $request->validate([
            'nombre' => 'required|string|max:255',
            'correo' => 'required|email|max:255|unique:admin_users,correo',
            'usuario' => 'required|string|max:255|unique:admin_users,usuario',
            'password' => 'required|string|min:8|confirmed',
            'rol' => 'required|string|in:'.implode(',', $rolesPermitidos),
        ]);

        AdminUser::create([
            'nombre' => $request->nombre,
            'correo' => $request->correo,
            'usuario' => $request->usuario,
            'password' => Hash::make($request->password),
            'rol' => $request->rol,
            'activo' => true,
        ]);

        Log::info('Admin creado por administrador principal', [
            'creado_por' => session('admin_usuario'),
            'nuevo_usuario' => $request->usuario,
            'rol' => $request->rol,
        ]);

        return back()->with('mensaje', 'Administrador dado de alta: '.$request->usuario);
    }

    public function actualizarPerfil(Request $request)
    {
        $admin = AdminUser::find(session('admin_id'));
        if (! $admin) {
            abort(404);
        }

        $request->validate([
            'nombre' => 'required|string|max:255',
            'correo' => 'required|email|max:255|unique:admin_users,correo,'.$admin->id,
        ]);

        $admin->update([
            'nombre' => $request->nombre,
            'correo' => $request->correo,
        ]);

        session([
            'admin_nombre' => $admin->nombre,
            'admin_correo' => $admin->correo,
        ]);

        return back()->with('mensaje', 'Datos actualizados correctamente.');
    }

    public function cambiarPassword(Request $request)
    {
        $admin = AdminUser::find(session('admin_id'));
        if (! $admin) {
            abort(404);
        }

        $request->validate([
            'password_actual' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if (! Hash::check($request->password_actual, $admin->password)) {
            return back()->with('error_password', 'La contraseña actual no es correcta.');
        }

        $admin->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('mensaje', 'Contraseña actualizada correctamente.');
    }

    public function subirFoto(Request $request)
    {
        $request->validate([
            'foto' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $admin = AdminUser::find(session('admin_id'));
        if (!$admin) {
            abort(404);
        }

        // Eliminar foto anterior si existe
        if ($admin->foto && \Storage::disk('public')->exists($admin->foto)) {
            \Storage::disk('public')->delete($admin->foto);
        }

        $path = $request->file('foto')->store('admin-fotos', 'public');
        $admin->update(['foto' => $path]);

        return back()->with('mensaje', 'Foto actualizada correctamente.');
    }

    private function esRolAdminPrincipal(): bool
    {
        return session('admin_rol') === 'admin';
    }

    /** @return array<string, string> */
    private function rolesDisponibles(): array
    {
        return [
            'admin' => 'Administrador',
            'gerente' => 'Gerente',
            'materia_prima' => 'Materia prima',
            'material_empaque' => 'Material empaque',
        ];
    }

    private function etiquetaRol(?string $rol): string
    {
        return $this->rolesDisponibles()[$rol ?? '']
            ?? ($rol ? ucfirst(str_replace('_', ' ', $rol)) : '—');
    }
}
