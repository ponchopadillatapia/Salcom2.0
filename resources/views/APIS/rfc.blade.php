<!DOCTYPE html>
<html>
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Validar RFC</title>

    <style>
        body { font-family: Arial; padding: 30px; }
        input { padding: 10px; width: 250px; }
        button { padding: 10px; }
        #resultado { margin-top: 20px; font-weight: bold; }
    </style>
</head>
<body>

<h2>Validar RFC</h2>

<input type="text" id="rfc" placeholder="Ingresa RFC">
<button onclick="validarRFC()">Validar</button>

<div id="resultado"></div>

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

        if (data.valido) {
            resultado.innerHTML = "✅ " + data.mensaje;
            resultado.style.color = "green";
        } else {
            resultado.innerHTML = "❌ " + data.mensaje;
            resultado.style.color = "red";
        }
    });
}
</script>

</body>
</html>