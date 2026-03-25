<!DOCTYPE html>
<html>
<head>
    <title>Validación</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #f4f6f5;
            font-family: Arial, sans-serif;
        }

        /* NAVBAR */
        .navbar-wiese {
            background-color: #0F3D2E;
            color: white;
            padding: 15px;
            font-weight: bold;
            text-align: center;
        }

        /* CONTENEDOR */
        .contenedor {
            max-width: 600px;
            margin: 40px auto;
        }

        /* TARJETA */
        .card-wiese {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0px 4px 10px rgba(0,0,0,0.1);
        }

        /* BOTÓN */
        .btn-wiese {
            background-color: #0F3D2E;
            color: white;
            border: none;
        }

        .btn-wiese:hover {
            background-color: #1F6F54;
        }

        /* RESULTADO */
        .resultado {
            margin-top: 20px;
            padding: 15px;
            border-radius: 10px;
            background: #f8f9fa;
        }

        /* SEMÁFORO */
        .verde { color: green; font-weight: bold; }
        .amarillo { color: orange; font-weight: bold; }
        .rojo { color: red; font-weight: bold; }
    </style>
</head>

<body>

<!-- NAVBAR -->
<div class="navbar-wiese">
    SISTEMA DE VALIDACIÓN
</div>

<div class="contenedor">
    <div class="card-wiese">

        <h4 class="mb-3">Subir documentos</h4>

        <!-- TUS INPUTS (SIN CAMBIOS) -->
        <input type="file" id="cif" class="form-control mb-3">
        <input type="file" id="opinion" class="form-control mb-3">
        <input type="file" id="acta" class="form-control mb-3">

        <br>

        <!-- BOTÓN -->
        <button onclick="enviar()" class="btn btn-wiese w-100">
            Validar
        </button>

        <!-- RESULTADO -->
        <div id="resultado" class="resultado"></div>

  
    </div>

</div>

<script>
function enviar() {

    let formData = new FormData();

    formData.append('cif_pdf', document.getElementById('cif').files[0]);
    formData.append('opinion_pdf', document.getElementById('opinion').files[0]);
    formData.append('acta_pdf', document.getElementById('acta').files[0]);

    fetch('/empresa', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: formData
    })
    .then(res => res.json())
    .then(data => {

        let estado = data.empresa.estado;

        let color = estado === 'verde' ? 'verde' :
                    estado === 'amarillo' ? 'amarillo' : 'rojo';

        document.getElementById('resultado').innerHTML =
            "RFC: " + data.empresa.rfc + "<br>" +
            "Nombre: " + data.empresa.nombre + "<br>" +
            "Estado: <span class='" + color + "'>" + estado + "</span>";
    })
    .catch(error => {
        console.error(error);
    });
}
</script>

</body>
</html>