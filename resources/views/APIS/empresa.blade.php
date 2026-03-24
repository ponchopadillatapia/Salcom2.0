<!DOCTYPE html>
<html>
<head>
    <title>Validación</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #F4F6F5;
        }

        .card-custom {
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }

        .titulo {
            color: #0F3D2E;
            font-weight: bold;
        }

        .btn-custom {
            background-color: #0F3D2E;
            color: white;
            border: none;
        }

        .btn-custom:hover {
            background-color: #1F6F54;
        }

        .badge-verde {
            background-color: #1F6F54;
        }

        .badge-amarillo {
            background-color: #FFC107;
            color: black;
        }

        .badge-rojo {
            background-color: #DC3545;
        }
    </style>
</head>
<body>

<div class="container mt-5">

    <div class="card card-custom p-4">

        <h3 class="titulo mb-4">Validación de documentos</h3>

        @if(session('mensaje'))
            <div class="alert alert-secondary">
                {{ session('mensaje') }}
            </div>
        @endif

        @if(session('empresa'))
            <div class="card p-3 mb-4">

                <p><strong>RFC:</strong> {{ session('empresa')['rfc'] }}</p>
                <p><strong>Nombre:</strong> {{ session('empresa')['nombre'] }}</p>
                <p><strong>Tipo:</strong> {{ session('empresa')['tipo'] }}</p>

                <p>
                    <strong>Estado:</strong>

                    @if(session('empresa')['estado'] == 'verde')
                        <span class="badge badge-verde">Aprobado</span>
                    @elseif(session('empresa')['estado'] == 'amarillo')
                        <span class="badge badge-amarillo">Revisión</span>
                    @else
                        <span class="badge badge-rojo">Rechazado</span>
                    @endif

                </p>

            </div>
        @endif

        <form action="/empresa" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="mb-3">
                <label>Constancia de Situación Fiscal</label>
                <input type="file" name="cif_pdf" class="form-control" required>
            </div>

            <div class="mb-3">
                <label>Opinión de cumplimiento</label>
                <input type="file" name="opinion_pdf" class="form-control" required>
            </div>

            <div class="mb-3">
                <label>Acta constitutiva</label>
                <input type="file" name="acta_pdf" class="form-control" required>
            </div>

            <button class="btn btn-custom w-100">Validar documentos</button>
        </form>

    </div>

</div>

</body>
</html>