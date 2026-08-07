<?php

$src = __DIR__ . '/../public/images/fond-ecran-studio-danse-430.jpg';
$dest = __DIR__ . '/../public/images/default-hero.jpg';

if (!function_exists('imagecreatefromjpeg')) {
    fwrite(STDERR, "GD JPEG support missing\n");
    exit(1);
}

$img = @imagecreatefromjpeg($src);
if (!$img) {
    fwrite(STDERR, "Cannot load source image\n");
    exit(1);
}

$w = imagesx($img);
$h = imagesy($img);

// Landscape crop for news cards (16:9), centered
$targetRatio = 16 / 9;
$srcRatio = $w / $h;
if ($srcRatio > $targetRatio) {
    $cropH = $h;
    $cropW = (int) ($h * $targetRatio);
    $sx = (int) (($w - $cropW) / 2);
    $sy = 0;
} else {
    $cropW = $w;
    $cropH = (int) ($w / $targetRatio);
    $sx = 0;
    $sy = (int) (($h - $cropH) / 2);
}

$outW = 1200;
$outH = 675;
$out = imagecreatetruecolor($outW, $outH);
imagecopyresampled($out, $img, 0, 0, $sx, $sy, $outW, $outH, $cropW, $cropH);

// Dark overlay to match black site design (no text/logo)
imagealphablending($out, true);
$overlay = imagecreatetruecolor($outW, $outH);
imagealphablending($overlay, false);
$dark = imagecolorallocatealpha($overlay, 0, 0, 0, 55); // ~57% opacity black
imagefilledrectangle($overlay, 0, 0, $outW, $outH, $dark);
imagecopy($out, $overlay, 0, 0, 0, 0, $outW, $outH);

// Subtle vignette corners
for ($i = 0; $i < 80; ++$i) {
    $alpha = (int) (100 + ($i * 0.4));
    if ($alpha > 127) {
        $alpha = 127;
    }
    $c = imagecolorallocatealpha($out, 0, 0, 0, $alpha);
    imagerectangle($out, $i, $i, $outW - 1 - $i, $outH - 1 - $i, $c);
}

imagejpeg($out, $dest, 88);
imagedestroy($overlay);
imagedestroy($out);
imagedestroy($img);

echo "Created {$dest}\n";
