<!DOCTYPE html>
<html>
<head>
    <title>Registro de Proveedor</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #f5f7fa;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>

<div class="container mt-5">

    <div class="card p-4">
        <h3 class="mb-4">Registro de Empresa</h3>

        <!-- MENSAJE -->
        @if(session('mensaje'))
            <div class="alert 
                @if(session('estado') == 'correcto') alert-success 
                @elseif(session('estado') == 'advertencia') alert-warning 
                @else alert-danger @endif">

                {{ session('mensaje') }}
            </div>
        @endif

        <form action="/empresa" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="mb-3">
                <label>RFC</label>
                <input type="text" name="rfc" class="form-control" required>
            </div>

            <div class="mb-3">
                <label>Nombre / Razón Social</label>
                <input type="text" name="nombre" class="form-control" required>
            </div>

            <div class="mb-3">
                <label>Opinión de Cumplimiento (PDF)</label>
                <input type="file" name="opinion_pdf" class="form-control" required>
            </div>

            <div class="mb-3">
                <label>Acta Constitutiva (PDF)</label>
                <input type="file" name="acta_pdf" class="form-control" required>
            </div>

            <button class="btn btn-primary w-100">Guardar y Validar</button>
        </form>
    </div>

    <!-- Vista previa -->
    <div class="card p-4 mt-4">
        <h5>Vista previa de Opinión</h5>
        <iframe src="/ver-pdf" width="100%" height="500px"></iframe>
    </div>

</div>

</body>
</html>