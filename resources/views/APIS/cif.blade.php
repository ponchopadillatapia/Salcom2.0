<!DOCTYPE html>
<html>
<head>
    <title>Constancia de Situación Fiscal</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body { font-family: Arial; background:#f5f5f5; }
        .container {
            width: 450px;
            margin: 80px auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px #ccc;
        }
        h2 { color:#611232; text-align:center; }
        input, button {
            width:100%; padding:10px; margin-top:10px;
        }
        button {
            background:#611232; color:white; border:none;
        }
        .btn-cif {
            background:green;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Consulta CIF</h2>

    <input type="text" id="rfc" placeholder="Ingrese RFC">
    <button onclick="validarRFC()">Validar RFC</button>

    <div id="resultado"></div>

    <button class="btn-cif" onclick="descargarCIF()" style="display:none;" id="btnCIF">
        Descargar CIF del mes actual
    </button>
</div>

<script>
function validarRFC() {
    let rfc = document.getElementById('rfc').value;

    fetch('/validar-rfc', {
        method:'POST',
        headers:{
            'Content-Type':'application/json',
            'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ rfc:rfc })
    })
    .then(res=>res.json())
    .then(data=>{
        let div = document.getElementById('resultado');

        if(data.success){
            div.innerHTML = `
                <p><b>RFC:</b> ${data.rfc}</p>
                <p><b>Nombre:</b> ${data.nombre}</p>
                <p><b>Estatus:</b> ${data.estatus}</p>
                <p><b>Régimen:</b> ${data.regimen}</p>
            `;
            document.getElementById('btnCIF').style.display = 'block';
        } else {
            div.innerHTML = `<p style="color:red;">${data.mensaje}</p>`;
        }
    });
}

function descargarCIF(){
    let rfc = document.getElementById('rfc').value;

    fetch('/generar-cif', {
        method:'POST',
        headers:{
            'Content-Type':'application/json',
            'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ rfc:rfc })
    })
    .then(res => res.blob())
    .then(blob => {
        let url = window.URL.createObjectURL(blob);
        let a = document.createElement('a');
        a.href = url;
        a.download = "CIF.pdf";
        a.click();
    });
}
</script>

</body>
</html>