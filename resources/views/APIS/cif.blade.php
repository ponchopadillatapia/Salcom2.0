<!DOCTYPE html>
<html>
<head>
    <title>Constancia de Situación Fiscal</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body { font-family: Arial; background:#eef2f3; }
        .container {
            width: 450px;
            margin: 60px auto;
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px #aaa;
        }
        h2 { color:#611232; text-align:center; }
        input, button {
            width:100%; padding:10px; margin-top:10px;
        }
        button {
            background:#611232;
            color:white;
            border:none;
            cursor:pointer;
        }
        .btn-cif { background:#2e7d32; }
    </style>
</head>
<body>

<div class="container">
    <h2>Consulta de Constancia Fiscal</h2>

    <input type="text" id="rfc" placeholder="Ingrese RFC">

    <button onclick="validarRFC()">Validar RFC</button>

    <div id="resultado"></div>

    <button id="btnCIF" class="btn-cif" onclick="descargarCIF()" style="display:none;">
        Descargar CIF (PDF)
    </button>
</div>

<script>
function validarRFC() {
    let rfc = document.getElementById('rfc').value;

    let formData = new FormData();
    formData.append('rfc', rfc);

    fetch('/validar-rfc', {
        method:'POST',
        headers:{
            'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content
        },
        body:formData
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

    let formData = new FormData();
    formData.append('rfc', rfc);

    fetch('/generar-cif', {
        method:'POST',
        headers:{
            'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content
        },
        body:formData
    })
    .then(res => res.blob())
    .then(blob => {
        let url = window.URL.createObjectURL(blob);
        let a = document.createElement('a');
        a.href = url;
        a.download = "CIF_"+rfc+".pdf";
        a.click();
    });
}
</script>

</body>
</html>