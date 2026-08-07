<?php

$src = __DIR__ . '/../public/images/fond-ecran-studio-danse-430.jpg';
$logoPath = __DIR__ . '/../public/images/logo.studio-danse-430.jpg';
$dest = __DIR__ . '/../public/images/default-news.jpg';

if (!function_exists('imagecreatefromjpeg')) {
    fwrite(STDERR, "GD JPEG support missing\n");
    exit(1);
}

$bg = @imagecreatefromjpeg($src) ?: @imagecreatefromjpeg($logoPath);
if (!$bg) {
    fwrite(STDERR, "Cannot load source image\n");
    exit(1);
}

$targetW = 960;
$targetH = 540;
$out = imagecreatetruecolor($targetW, $targetH);
$black = imagecolorallocate($out, 0, 0, 0);
imagefilledrectangle($out, 0, 0, $targetW, $targetH, $black);

$sw = imagesx($bg);
$sh = imagesy($bg);
$scale = max($targetW / $sw, $targetH / $sh);
$nw = (int) ($sw * $scale);
$nh = (int) ($sh * $scale);
$dx = (int) (($targetW - $nw) / 2);
$dy = (int) (($targetH - $nh) / 2);
imagecopyresampled($out, $bg, $dx, $dy, 0, 0, $nw, $nh, $sw, $sh);

// Soft dark overlay for readability on cards
$overlay = imagecreatetruecolor($targetW, $targetH);
imagealphablending($overlay, false);
$semi = imagecolorallocatealpha($overlay, 0, 0, 0, 70);
imagefilledrectangle($overlay, 0, 0, $targetW, $targetH, $semi);
imagealphablending($out, true);
imagecopy($out, $overlay, 0, 0, 0, 0, $targetW, $targetH);

$logo = @imagecreatefromjpeg($logoPath);
if ($logo) {
    $lw = imagesx($logo);
    $lh = imagesy($logo);
    $logoSize = 160;
    $ls = min($logoSize / $lw, $logoSize / $lh);
    $lnw = (int) ($lw * $ls);
    $lnh = (int) ($lh * $ls);
    $lx = (int) (($targetW - $lnw) / 2);
    $ly = (int) (($targetH - $lnh) / 2);
    imagecopyresampled($out, $logo, $lx, $ly, 0, 0, $lnw, $lnh, $lw, $lh);
    imagedestroy($logo);
}

imagejpeg($out, $dest, 85);
imagedestroy($overlay);
imagedestroy($out);
imagedestroy($bg);

echo "Created {$dest}\n";
