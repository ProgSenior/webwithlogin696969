<?php
session_start();

$ancho = 120; // Lo ensanchamos un poco para que quepan bien las letras
$alto = 40;
$imagen = imagecreatetruecolor($ancho, $alto);

// Colores
$amarillo = imagecolorallocate($imagen, 255, 255, 0);
$rojo = imagecolorallocate($imagen, 255, 0, 0);
$gris = imagecolorallocate($imagen, 200, 200, 200);

imagefill($imagen, 0, 0, $amarillo);

// 1. Definir los caracteres permitidos (quitamos la 'O' y el '0' para evitar confusiones)
$caracteres = '23456789abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ';
$captcha_string = '';
$longitud = 6;

// 2. Generar la cadena aleatoria
for ($i = 0; $i < $longitud; $i++) {
    $captcha_string .= $caracteres[rand(0, strlen($caracteres) - 1)];
}

// Guardar en la sesión para validar después
$_SESSION['numeroaleatorio'] = $captcha_string;

// 3. Dibujar el texto con un poco de desorden para que sea más seguro
for ($i = 0; $i < $longitud; $i++) {
    $x = 15 + ($i * 15); // Espaciado entre letras
    $y = rand(5, 15);    // Altura aleatoria para cada letra
    imagestring($imagen, 5, $x, $y, $captcha_string[$i], $rojo);
}

// 4. Añadir ruido (puntos aleatorios)
for ($i = 0; $i < 100; $i++) {
    imagesetpixel($imagen, rand(0, $ancho), rand(0, $alto), $gris);
}

// 5. Añadir líneas
for ($i = 0; $i < 5; $i++) {
    imageline($imagen, 0, rand(0, $alto), $ancho, rand(0, $alto), $rojo);
}

ob_clean();
header("Content-type: image/jpeg");
imagejpeg($imagen);
imagedestroy($imagen);
