<?php
session_start();

// Validar si el usuario está logueado
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

require_once "config.php";

// Procesar la eliminación del archivo
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["eliminar_id"]) && isset($_POST["ruta_eliminar"])){
    $eliminar_id = intval($_POST["eliminar_id"]);
    $ruta_eliminar = $_POST["ruta_eliminar"];
    $usuario_actual = $_SESSION["id"];

    // 1. Verificar por seguridad que el archivo realmente pertenezca al usuario logueado
    $sql_verificar = "SELECT id FROM publicaciones WHERE id = ? AND usuario_id = ?";
    if($stmt_verif = mysqli_prepare($link, $sql_verificar)){
        mysqli_stmt_bind_param($stmt_verif, "ii", $eliminar_id, $usuario_actual);
        mysqli_stmt_execute($stmt_verif);
        mysqli_stmt_store_result($stmt_verif);

        if(mysqli_stmt_num_rows($stmt_verif) == 1){
            mysqli_stmt_close($stmt_verif);

            // 2. Eliminar el archivo físico de la carpeta usando la ruta absoluta
            $ruta_fisica_archivo = __DIR__ . "/" . $ruta_eliminar; 
            if(file_exists($ruta_fisica_archivo)){
                unlink($ruta_fisica_archivo); 
            }

            // 3. Eliminar el registro en la Base de Datos
            $sql_delete = "DELETE FROM publicaciones WHERE id = ?";
            if($stmt_del = mysqli_prepare($link, $sql_delete)){
                mysqli_stmt_bind_param($stmt_del, "i", $eliminar_id);
                if(mysqli_stmt_execute($stmt_del)){
                    $mensaje = "<div class='alert alert-success'>Archivo eliminado correctamente.</div>";
                } else {
                    $mensaje = "<div class='alert alert-danger'>Error al eliminar el registro de la base de datos.</div>";
                }
                mysqli_stmt_close($stmt_del);
            }
        } else {
            $mensaje = "<div class='alert alert-danger'>No tienes permisos para eliminar este archivo.</div>";
            mysqli_stmt_close($stmt_verif);
        }
    }
}

$mensaje = "";

// Procesar la carga del archivo
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["multimedia"])){
    $carpeta_destino = "archivos/";
    $nombre_archivo = basename($_FILES["multimedia"]["name"]);
    
    $tipo_extension = strtolower(pathinfo($nombre_archivo, PATHINFO_EXTENSION));
    $nuevo_nombre_archivo = uniqid() . "." . $tipo_extension;
    $ruta_final = $carpeta_destino . $nuevo_nombre_archivo;
    
    $formatos_imagenes = array("jpg", "jpeg", "png", "gif");
    $formatos_videos = array("mp4", "webm", "ogg");
    
    $tipo_final = "";
    if(in_array($tipo_extension, $formatos_imagenes)){
        $tipo_final = "image";
    } elseif(in_array($tipo_extension, $formatos_videos)){
        $tipo_final = "video";
    }

    if(!empty($tipo_final)){
        if(move_uploaded_file($_FILES["multimedia"]["tmp_name"], $ruta_final)){
            $sql = "INSERT INTO publicaciones (usuario_id, ruta_archivo, tipo_archivo) VALUES (?, ?, ?)";
            if($stmt = mysqli_prepare($link, $sql)){
                mysqli_stmt_bind_param($stmt, "iss", $_SESSION["id"], $ruta_final, $tipo_final);
                if(mysqli_stmt_execute($stmt)){
                    $mensaje = "<div class='alert alert-success'>Archivo subido con éxito.</div>";
                } else{
                    $mensaje = "<div class='alert alert-danger'>Error al guardar en la BD.</div>";
                }
                mysqli_stmt_close($stmt);
            }
        } else{
            $mensaje = "<div class='alert alert-danger'>Error al mover el archivo.</div>";
        }
    } else {
        $mensaje = "<div class='alert alert-danger'>Formato no permitido. Solo imágenes (jpg, png, gif) o videos (mp4, webm).</div>";
    }
}

// Obtener todas las publicaciones
$publicaciones = [];
$sql = "SELECT p.id, p.ruta_archivo, p.tipo_archivo, u.username, p.usuario_id FROM publicaciones p INNER JOIN users u ON p.usuario_id = u.id ORDER BY p.fecha_carga DESC";
if($result = mysqli_query($link, $sql)){
    while($row = mysqli_fetch_assoc($result)){
        $publicaciones[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Bienvenido</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* --- ARREGLO PARA EVITAR COLISEOS CON EL FOOTER --- */
        main { 
            padding-bottom: 100px; /* Genera un espacio abajo para que el footer fijo nunca tape los botones */
        }

        .galeria { display: flex; flex-wrap: wrap; gap: 20px; margin-top: 20px; }
        .item-galeria { width: 300px; border: 1px solid #ddd; padding: 10px; border-radius: 5px; box-shadow: 2px 2px 5px rgba(0,0,0,0.1); background: #fff; }
        .item-galeria img, .item-galeria video { width: 100%; height: auto; border-radius: 3px; }

        /* Estilos del Modal */
        .modal-pantalla-completa {
            display: none; 
            position: fixed; 
            z-index: 9999; 
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto; 
            background-color: rgba(0, 0, 0, 0.9); 
        }

        #contenedorMultimediaModal {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            height: 100%;
        }

        .contenido-modal {
            max-width: 90%;
            max-height: 85vh;
            object-fit: contain;
            box-shadow: 0px 4px 20px rgba(255, 255, 255, 0.2);
        }

        .cerrar-modal {
            position: absolute;
            top: 20px;
            right: 35px;
            color: #f1f1f1;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
            z-index: 10000; 
        }

        .cerrar-modal:hover { color: #bbb; }
    </style>
</head>
<body>
    <header>
        <nav>
            <a href="index.php">Inicio</a>
            <a href="welcome.php" class="active">Panel Control</a>
            <a href="logout.php">Cerrar Sesión</a>
        </nav>
    </header>

    <main>
        <h1>Hola, <b><?php echo htmlspecialchars($_SESSION["username"]); ?></b>. Bienvenido a tu panel.</h1>
        
        <?php echo $mensaje; ?>

        <section style="background: #f4f4f4; padding: 20px; border-radius: 8px;">
            <h3>Subir nueva imagen o video</h3>
            <form action="welcome.php" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <input type="file" name="multimedia" required>
                </div>
                <button type="submit" class="btn btn-primary" style="margin-top: 10px;">Subir Archivo</button>
            </form>
        </section>

        <h2>Tus archivos y los de la comunidad</h2>
        
        <div class="galeria">
            <?php foreach($publicaciones as $pub): ?>
                <div class="item-galeria">
                    <p>Subido por: <b><?php echo htmlspecialchars($pub['username']); ?></b></p>
                    
                    <?php if($pub['tipo_archivo'] == 'image'): ?>
                        <img src="<?php echo $pub['ruta_archivo']; ?>" alt="Imagen" class="clic-escala" onclick="abrirPantallaCompleta('<?php echo $pub['ruta_archivo']; ?>', 'image')" style="cursor: pointer; width: 100%; height: auto;">
                    <?php elseif($pub['tipo_archivo'] == 'video'): ?>
                        <video src="<?php echo $pub['ruta_archivo']; ?>" controls class="clic-escala" onclick="abrirPantallaCompleta('<?php echo $pub['ruta_archivo']; ?>', 'video')" style="cursor: pointer; width: 100%; height: auto;"></video>
                    <?php endif; ?>

                    <?php if($pub['usuario_id'] == $_SESSION["id"]): ?>
                        <form action="welcome.php" method="post" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este archivo de forma permanente?');" style="margin-top: 10px;">
                            <input type="hidden" name="eliminar_id" value="<?php echo $pub['id']; ?>">
                            <input type="hidden" name="ruta_eliminar" value="<?php echo $pub['ruta_archivo']; ?>">
                            <button type="submit" style="background-color: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; width: 100%;">
                                Eliminar
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <footer>
        <p>&copy; 2026 Mi Sitio Web. Todos los derechos reservados.</p>
    </footer>

    <div id="miModalVisualizador" class="modal-pantalla-completa">
        <span class="cerrar-modal" onclick="cerrarPantallaCompleta()">&times;</span>
        <div id="contenedorMultimediaModal"></div>
    </div>

    <script>
    function abrirPantallaCompleta(ruta, tipo) {
        if (tipo === 'video' && event && event.target.tagName === 'VIDEO' && event.offsetY > event.target.clientHeight - 50) {
            return; 
        }

        var modal = document.getElementById("miModalVisualizador");
        var contenedor = document.getElementById("contenedorMultimediaModal");
        
        contenedor.innerHTML = ""; 
        
        if(tipo === 'image') {
            var img = document.createElement("img");
            img.src = ruta;
            img.className = "contenido-modal";
            contenedor.appendChild(img);
        } else if(tipo === 'video') {
            var video = document.createElement("video");
            video.src = ruta;
            video.className = "contenido-modal";
            video.controls = true;
            video.autoplay = true;
            contenedor.appendChild(video);
        }
        
        modal.style.display = "block";
    }

    function cerrarPantallaCompleta() {
        var modal = document.getElementById("miModalVisualizador");
        document.getElementById("contenedorMultimediaModal").innerHTML = "";
        modal.style.display = "none";
    }

    window.onclick = function(event) {
        var modal = document.getElementById("miModalVisualizador");
        if (event.target == modal) {
            cerrarPantallaCompleta();
        }
    }
    </script>
</body>
</html>