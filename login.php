<?php
session_start(); // Una sola vez al principio

// Include config file
require_once 'config.php';

// Check if the user is already logged in
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: welcome.php");
    exit;
}

$username = $password = "";
$username_err = $password_err = $captcha_err = $login_err = "";

// Procesar datos cuando se envía el formulario
if($_SERVER["REQUEST_METHOD"] == "POST"){

    // Validación del Captcha
    if (isset($_POST['captcha']) && !empty(trim($_POST['captcha']))) {
        if (empty($_SESSION['numeroaleatorio']) || strtolower($_SESSION['numeroaleatorio']) != strtolower(trim($_POST['captcha']))) {
            $captcha_err = "El código de verificación es incorrecto.";
        }
    } else {
        $captcha_err = "Por favor introduce el código de verificación.";
    }

    // Validar si username está vacío
    if(empty(trim($_POST["username"]))){
        $username_err = "Please enter username.";
    } else{
        $username = trim($_POST["username"]);
    }
    
    // Validar si password está vacío
    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter your password.";
    } else{
        $password = trim($_POST["password"]);
    }        
    
    // Validar credenciales si no hay errores en los campos ni en el captcha
    if(empty($username_err) && empty($password_err) && empty($captcha_err)){
        $sql = "SELECT id, username, password FROM users WHERE username = ?";
        
        if($stmt = mysqli_prepare($link, $sql)){
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            $param_username = $username;
            
            if(mysqli_stmt_execute($stmt)){
                mysqli_stmt_store_result($stmt);
                
                if(mysqli_stmt_num_rows($stmt) == 1){                    
                    mysqli_stmt_bind_result($stmt, $id, $username, $hashed_password);
                    if(mysqli_stmt_fetch($stmt)){
                        if(password_verify($password, $hashed_password)){
                            // El login es correcto, guardamos datos
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["username"] = $username;

                            header("location: welcome.php");
                            exit(); 
                        } else {
                            $login_err = "Invalid username or password.";
                        }
                    }
                } else {
                    $login_err = "Invalid username or password.";
                }
            } else {
                echo "Oops! Something went wrong.";
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
    <title>Login</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php 
    if(!empty($login_err)){
        echo '<div class="alert alert-danger">' . $login_err . '</div>';
    }        
    ?>
    <header>
        <nav>
            <a href="index.php">Inicio</a>
            <a href="registro.php">Registro</a>
            <a href="login.php" class="active">Login</a>
        </nav>
    </header>

    <main>
        <h1>Iniciar sesión</h1>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        
            <div class="form-group">
                <label for="username">Usuario:</label>
                <input type="text" name="username" placeholder="Escriba el usuario" id="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($username); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="password">Clave:</label>
                <input type="password" id="password" placeholder="Escriba la clave" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="captcha">Verificación:</label>
                <div><img src="captcha_image.php" alt="CAPTCHA"></div>
                <input type="text" name="captcha" placeholder="Escriba lo que ve" class="form-control <?php echo (!empty($captcha_err)) ? 'is-invalid' : ''; ?>" id="captcha" required>
                <?php if(!empty($captcha_err)): ?>
                    <span style="color: red; display: block;"><?php echo $captcha_err; ?></span>
                <?php endif; ?>
            </div>
            
            <button type="submit" class="btn btn-primary">Entrar</button>
        </form>
    </main>

    <footer>
        <p>&copy; 2026 Mi Sitio Web. Todos los derechos reservados.</p>
    </footer>
</body>
</html>