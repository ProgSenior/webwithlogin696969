<?php
// Incluir configuración de base de datos
require_once "config.php";

// Consultar publicaciones
$publicaciones = [];
$sql = "SELECT p.ruta_archivo, p.tipo_archivo, u.username FROM publicaciones p INNER JOIN users u ON p.usuario_id = u.id ORDER BY p.fecha_carga DESC";
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
    <title>Inicio - Galería</title>
    <link rel="stylesheet" href="styles.css">
<style>
        .galeria { display: flex; flex-wrap: wrap; gap: 20px; margin-top: 20px; }
        .item-galeria { width: 300px; border: 1px solid #ddd; padding: 10px; border-radius: 5px; box-shadow: 2px 2px 5px rgba(0,0,0,0.1); }
        .item-galeria img, .item-galeria video { width: 100%; height: auto; border-radius: 3px; }

        /* --- SOLUCCIÓN AL PROBLEMA DEL MODAL --- */
        .modal-pantalla-completa {
            display: none; /* Oculto por defecto */
            position: fixed; /* Se fija a la pantalla, ignorando el scroll y el footer */
            z-index: 9999; /* Se posiciona por encima de CUALQUIER elemento (incluido el footer) */
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto; /* Permite scroll si la pantalla es muy pequeña */
            background-color: rgba(0, 0, 0, 0.9); /* Fondo negro semitransparente */
        }

        /* Contenedor interno para centrar la imagen o video */
        #contenedorMultimediaModal {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            height: 100%;
        }

        /* Tamaño máximo del contenido para que no se deforme */
        .contenido-modal {
            max-width: 90%;
            max-height: 85vh;
            object-fit: contain;
            box-shadow: 0px 4px 20px rgba(255, 255, 255, 0.2);
        }

        /* Botón de cerrar (X) */
        .cerrar-modal {
            position: absolute;
            top: 20px;
            right: 35px;
            color: #f1f1f1;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
            z-index: 10000; /* Por encima del contenido multimedia */
        }

        .cerrar-modal:hover {
            color: #bbb;
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <a href="index.php" class="active">Inicio</a>
            <a href="registro.php">Registro</a>
            <a href="login.php">Login</a>
        </nav>
    </header>

    <main>
        <h1>Bienvenido a nuestra plataforma</h1>
        <p>("Actualiza la pagina para reiniciar la galeria"). Echa un vistazo a los últimos videos e imágenes subidos por los usuarios:</p>

        <div class="galeria">
    <?php if(empty($publicaciones)): ?>
        <p>Aún no hay archivos multimedia subidos.</p>
    <?php else: ?>
        <?php foreach($publicaciones as $pub): ?>
            <div class="item-galeria">
                <p>Publicado por: <b><?php echo htmlspecialchars($pub['username']); ?></b></p>
                
                <?php if($pub['tipo_archivo'] == 'image'): ?>
                    <img src="<?php echo $pub['ruta_archivo']; ?>" alt="Imagen pública" class="clic-escala" onclick="abrirPantallaCompleta('<?php echo $pub['ruta_archivo']; ?>', 'image')" style="cursor: pointer; width: 100%; height: auto;">
                <?php elseif($pub['tipo_archivo'] == 'video'): ?>
                    <video src="<?php echo $pub['ruta_archivo']; ?>" controls class="clic-escala" onclick="abrirPantallaCompleta('<?php echo $pub['ruta_archivo']; ?>', 'video')" style="cursor: pointer; width: 100%; height: auto;"></video>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
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
        // Evita abrir el modal si se hace clic en la barra de controles inferior de un video
        if (tipo === 'video' && event && event.target.tagName === 'VIDEO' && event.offsetY > event.target.clientHeight - 50) {
            return; 
        }

        var modal = document.getElementById("miModalVisualizador");
        var contenedor = document.getElementById("contenedorMultimediaModal");
        
        contenedor.innerHTML = ""; // Limpiar contenido previo
        
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

    // Cerrar si hacen clic fuera de la multimedia (en el fondo negro)
    window.onclick = function(event) {
        var modal = document.getElementById("miModalVisualizador");
        if (event.target == modal) {
            cerrarPantallaCompleta();
        }
    }
    </script>
</body>
</html>