<!DOCTYPE html>
<html>
<head>
    <title>Validación de RFC - SAT</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body {
            font-family: Arial;
            background-color: #f4f4f4;
        }
        .container {
            width: 400px;
            margin: 80px auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0px 0px 10px #ccc;
        }
        h2 {
            text-align: center;
            color: #611232;
        }
        input {
            width: 100%;
            padding: 10px;
            margin-top: 10px;
        }
        button {
            width: 100%;
            padding: 10px;
            background-color: #611232;
            color: white;
            border: none;
            margin-top: 10px;
        }
        .resultado {
            margin-top: 15px;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Consulta RFC</h2>

    <input type="text" id="rfc" placeholder="Ingrese RFC">
    <button onclick="validarRFC()">Validar</button>

    <div class="resultado" id="resultado"></div>
</div>

<script>
function validarRFC() {
    let rfc = document.getElementById('rfc').value;

    fetch('/validar-rfc', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ rfc: rfc })
    })
    .then(res => res.json())
    .then(data => {
        let resultado = document.getElementById('resultado');

        if (data.success) {
            resultado.innerHTML = `
                <p><b>RFC:</b> ${data.rfc}</p>
                <p><b>Nombre:</b> ${data.nombre}</p>
                <p><b>Estatus:</b> ${data.estatus}</p>
                <p><b>Régimen:</b> ${data.regimen}</p>
            `;
        } else {
            resultado.innerHTML = `<p style="color:red;">${data.mensaje}</p>`;
        }
    });
}
</script>

</body>
</html>