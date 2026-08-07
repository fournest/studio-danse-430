<?php

$src = __DIR__ . '/../public/images/logo.studio-danse-430.jpg';
if (!function_exists('imagecreatefromjpeg')) {
    fwrite(STDERR, "GD JPEG support missing\n");
    exit(1);
}

$img = @imagecreatefromjpeg($src);
if (!$img) {
    fwrite(STDERR, "Cannot load logo\n");
    exit(1);
}

foreach ([192, 512] as $size) {
    $out = imagecreatetruecolor($size, $size);
    imagealphablending($out, false);
    imagesavealpha($out, true);
    $black = imagecolorallocate($out, 0, 0, 0);
    imagefilledrectangle($out, 0, 0, $size, $size, $black);
    imagealphablending($out, true);

    $w = imagesx($img);
    $h = imagesy($img);
    $scale = min($size / $w, $size / $h) * 0.85;
    $nw = (int) ($w * $scale);
    $nh = (int) ($h * $scale);
    $dx = (int) (($size - $nw) / 2);
    $dy = (int) (($size - $nh) / 2);
    imagecopyresampled($out, $img, $dx, $dy, 0, 0, $nw, $nh, $w, $h);

    $dirs = [
        __DIR__ . '/../public/images',
        __DIR__ . '/../public/build/images',
    ];
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $path = $dir . "/icon-{$size}.png";
        imagepng($out, $path);
        echo "Created {$path}\n";
    }
    imagedestroy($out);
}

imagedestroy($img);
