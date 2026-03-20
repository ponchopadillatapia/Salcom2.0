<!DOCTYPE html>
<html>
<head>
<style>
body { font-family: Arial; }
.header {
    background:#611232;
    color:white;
    padding:10px;
}
.section {
    margin-top:20px;
}
.qr {
    position:absolute;
    top:20px;
    right:20px;
}
</style>
</head>
<body>

<div class="header">
    <h2>CONSTANCIA DE SITUACIÓN FISCAL</h2>
</div>

<div class="qr">
    <img src="data:image/png;base64,{{ $qr }}">
</div>

<div class="section">
    <p><b>RFC:</b> {{ $rfc }}</p>
    <p><b>Nombre:</b> {{ $nombre }}</p>
    <p><b>Régimen Fiscal:</b> {{ $regimen }}</p>
    <p><b>Fecha de emisión:</b> {{ $fecha }}</p>
</div>

<hr>

<p>Este documento es una representación simulada del SAT.</p>

</body>
</html>