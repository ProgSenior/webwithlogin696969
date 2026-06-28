<?php
// Iniciar sesión al principio del archivo obligatoriamente
session_start();

// Include config file
require_once "config.php";

$username = $password = $confirm_password = "";
$username_err = $password_err = $confirm_password_err = $captcha_err = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. VALIDACIÓN CAPTCHA (Manejado por variable para no recargar)
    if (isset($_POST['captcha']) && !empty(trim($_POST['captcha']))) {
        if (empty($_SESSION['numeroaleatorio']) || strtolower($_SESSION['numeroaleatorio']) != strtolower(trim($_POST['captcha']))) {
            $captcha_err = "El código de verificación es incorrecto.";
        }
    } else {
        $captcha_err = "Por favor introduce el código de verificación.";
    }

    // 2. VALIDACIÓN USERNAME
    if(empty(trim($_POST["username"]))){
        $username_err = "Please enter a username.";
    } elseif(!preg_match('/^[a-zA-Z0-9_]+$/', trim($_POST["username"]))){
        $username_err = "Username can only contain letters, numbers, and underscores.";
    } else{
        $sql = "SELECT id FROM users WHERE username = ?";
        if($stmt = mysqli_prepare($link, $sql)){
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            $param_username = trim($_POST["username"]);
            if(mysqli_stmt_execute($stmt)){
                mysqli_stmt_store_result($stmt);
                if(mysqli_stmt_num_rows($stmt) == 1){
                    $username_err = "This username is already taken.";
                } else{
                    $username = trim($_POST["username"]);
                }
            }
            mysqli_stmt_close($stmt);
        }
    }
    
    // 3. VALIDACIÓN DE CONTRASEÑAS (Corregido y completado)
    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter a password.";     
    } elseif(strlen(trim($_POST["password"])) < 6){ // validación mínima opcional de longitud
        $password_err = "Password must have at least 6 characters.";
    } else{
        $password = trim($_POST["password"]);
    }
    
    if(empty(trim($_POST["confirm_password"]))){
        $confirm_password_err = "Please confirm password.";     
    } else{
        $confirm_password = trim($_POST["confirm_password"]);
        if(empty($password_err) && ($password != $confirm_password)){
            $confirm_password_err = "Password did not match.";
        }
    }

    // 4. INSERTAR EN BD (Si no hay ningún error)
    if(empty($username_err) && empty($password_err) && empty($confirm_password_err) && empty($captcha_err)){
        $sql = "INSERT INTO users (username, password) VALUES (?, ?)";
        if($stmt = mysqli_prepare($link, $sql)){
            mysqli_stmt_bind_param($stmt, "ss", $param_username, $param_password);
            
            $param_username = $username;
            $param_password = password_hash($password, PASSWORD_DEFAULT); // Cifra la contraseña real
            
            if(mysqli_stmt_execute($stmt)){
                header("location: login.php");
                exit();
            } else{
                echo "Error al insertar en la base de datos.";
            }
            mysqli_stmt_close($stmt);
        }
    }
    mysqli_close($link);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <nav>
            <a href="index.php">Inicio</a>
            <a href="registro.php" class="active">Registro</a>
            <a href="login.php">Login</a>
        </nav>
    </header>

    <main>
        <h1>Registro de Usuario</h1>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">

            <div class="form-group">
                <label for="username">Usuario:</label>
                <input type="text" id="username" placeholder="Escriba el usuario" name="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($username); ?>" required>
                <span class="invalid-feedback" style="color: red; display: block;"><?php echo $username_err; ?></span>
            </div>

            <div class="form-group">
                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" placeholder="Escriba la clave" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" required>
                <span class="invalid-feedback" style="color: red; display: block;"><?php echo $password_err; ?></span>
            </div>

            <div class="form-group">
                <label for="confirm_password">Repetir contraseña:</label>
                <input type="password" id="confirm_password" placeholder="Escriba la clave" name="confirm_password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>" required>
                <span class="invalid-feedback" style="color: red; display: block;"><?php echo $confirm_password_err; ?></span>
            </div>

            <div class="form-group">
                <label for="captcha">Verificación:</label>
                <div><img src="captcha_image.php" alt="CAPTCHA"></div>
                <input type="text" name="captcha" id="captcha" class="form-control <?php echo (!empty($captcha_err)) ? 'is-invalid' : ''; ?>" placeholder="Escriba lo que ve" required>
                <?php if(!empty($captcha_err)): ?>
                    <span class="invalid-feedback" style="color: red; display: block;"><?php echo $captcha_err; ?></span>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary">Registrarse</button>
            <input type="reset" class="btn btn-secondary ml-2" value="Reset">
            <p>¿Ya tienes cuenta? <a href="login.php">Iniciar sesión</a>.</p>

        </form>
    </main>

    <footer>
        <p>&copy; 2026 Mi Sitio Web. Todos los derechos reservados.</p>
    </footer>
</body>
</html>