<<!DOCTYPE html>
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

        .navbar-wiese {
            background-color: #0F3D2E;
            color: white;
            padding: 15px;
            font-weight: bold;
            text-align: center;
        }

        .contenedor {
            max-width: 600px;
            margin: 40px auto;
        }

        .card-wiese {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0px 4px 10px rgba(0,0,0,0.1);
        }

        .btn-wiese {
            background-color: #0F3D2E;
            color: white;
            border: none;
        }

        .btn-wiese:hover {
            background-color: #1F6F54;
        }

        .resultado {
            margin-top: 20px;
            padding: 15px;
            border-radius: 10px;
            background: #f8f9fa;
        }

        .verde { color: green; font-weight: bold; }
        .amarillo { color: orange; font-weight: bold; }
        .rojo { color: red; font-weight: bold; }
    </style>
</head>

<body>

<div class="navbar-wiese">
    SISTEMA DE VALIDACIÓN
</div>

<div class="contenedor">
    <div class="card-wiese">

        <h4 class="mb-3">Subir documentos</h4>

        <label><strong>Constancia de Situación Fiscal (CIF) - Mes actual</strong></label>
        <input type="file" id="cif" class="form-control mb-1" onchange="verArchivo('cif')">
        <small id="cif_nombre" class="text-success"></small>

        <br>

        <label><strong>Opinión de cumplimiento (POSITIVA) - Mes actual</strong></label>
        <input type="file" id="opinion" class="form-control mb-1" onchange="verArchivo('opinion')">
        <small id="opinion_nombre" class="text-success"></small>

        <br>

        <label><strong>Acta constitutiva</strong></label>
        <input type="file" id="acta" class="form-control mb-1" onchange="verArchivo('acta')">
        <small id="acta_nombre" class="text-success"></small>

        <br>

        <button onclick="enviar()" class="btn btn-wiese w-100">
            Validar
        </button>

        <div id="resultado" class="resultado"></div>

    </div>
</div>

<script>

// 🔥 NUEVO: detectar archivo seleccionado
function verArchivo(id) {
    let archivo = document.getElementById(id).files[0];

    if (archivo) {
        console.log("Archivo seleccionado:", archivo.name);

        document.getElementById(id + "_nombre").innerHTML =
            "Archivo: " + archivo.name;
    }
}

// 🔥 FUNCIÓN PRINCIPAL
function enviar() {

    let cif = document.getElementById('cif').files[0];
    let opinion = document.getElementById('opinion').files[0];
    let acta = document.getElementById('acta').files[0];

    if (!cif || !opinion || !acta) {
        alert("Debes subir todos los documentos");
        return;
    }

    console.log("Enviando archivos...");

    let formData = new FormData();
    formData.append('cif_pdf', cif);
    formData.append('opinion_pdf', opinion);
    formData.append('acta_pdf', acta);

    fetch('/empresa', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: formData
    })
    .then(res => res.json())
    .then(data => {

        console.log("RESPUESTA:", data);

        if (!data.empresa) {
            document.getElementById('resultado').innerHTML =
                "<span class='rojo'>"+ (data.mensaje || "Error en el servidor") +"</span>";
            return;
        }

        let estado = data.empresa.estado;

        let color = estado === 'verde' ? 'verde' :
                    estado === 'amarillo' ? 'amarillo' : 'rojo';

       document.getElementById('resultado').innerHTML =
    "RFC: " + data.empresa.rfc + "<br>" +
    "RFC válido: " + data.empresa.rfc_valido + "<br>" +
    "CIF: " + data.empresa.cif_valido + "<br>" +
    "Estado: " + data.empresa.estado;
    })
    .catch(error => {
        console.error("ERROR:", error);

        document.getElementById('resultado').innerHTML =
            "<span class='rojo'>Error de conexión con el servidor</span>";
    });
}
</script>

</body>
</html>