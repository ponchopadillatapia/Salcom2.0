<!-- resources/views/proveedores/registro.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <title>Registro Proveedor</title>
</head>
<body>

<form method="POST" action="{{ route('proveedores.registro.guardar') }}">
    @csrf  {{-- esto es obligatorio en Laravel, protege el formulario --}}

    <input type="text"     name="nombre"    placeholder="Nombre completo" required>
    <input type="tel"      name="telefono"  placeholder="Teléfono" required>
    <input type="email"    name="correo"    placeholder="Correo electrónico" required>
    <input type="password" name="password"  placeholder="Contraseña" required>

    {{-- Captcha simple por ahora --}}
    <input type="text" name="captcha" placeholder="¿Cuánto es 3 + 4?" required>

    <button type="submit">Registrarme</button>
</form>

</body>
</html>