<!DOCTYPE html>
<html>
<head>
    <title>Login Proveedor</title>

    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f5f2f7;
        }

        .container {
            display: flex;
            height: 100vh;
        }

        .left {
            width: 50%;
            background: linear-gradient(135deg, #7b2cbf, #9d4edd);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 40px;
        }

        .left h1 {
            font-size: 40px;
        }

        .right {
            width: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-box {
            background: white;
            padding: 40px;
            border-radius: 10px;
            width: 300px;
            box-shadow: 0px 10px 30px rgba(0,0,0,0.1);
        }

        .login-box h2 {
            color: #7b2cbf;
            margin-bottom: 20px;
        }

        input {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        button {
            width: 100%;
            padding: 10px;
            background: #7b2cbf;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        button:hover {
            background: #5a189a;
        }

        .error {
            color: red;
            margin-bottom: 10px;
        }
    </style>

</head>
<body>

<div class="container">

    <!-- LADO IZQUIERDO -->
    <div class="left">
        <div>
            <h1>Wiese</h1>
            <p>Acceso para proveedores</p>
        </div>
    </div>

    <!-- LADO DERECHO -->
    <div class="right">
        <div class="login-box">

            <h2>Iniciar sesión</h2>

            @if(session('error'))
                <div class="error">{{ session('error') }}</div>
            @endif

            <form method="POST" action="/login-proveedor">
                @csrf

                <input type="text" name="usuario" placeholder="Usuario">

                <input type="password" name="password" placeholder="Contraseña">

                <input type="text" name="codigo" placeholder="Código de compras">

                <button type="submit">Entrar</button>
            </form>

        </div>
    </div>

</div>

</body>
</html>