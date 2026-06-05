<?php
$base = dirname(__DIR__) . '/assets/img/certificate/';
$w = 900;
$h = 640;
$canvas = imagecreatetruecolor($w, $h);
$white = imagecolorallocate($canvas, 255, 255, 255);
imagefilledrectangle($canvas, 0, 0, $w, $h, $white);

$top = imagecreatefromjpeg($base . 'lcp_cert_deco_top_sm.jpeg');
$bot = imagecreatefromjpeg($base . 'lcp_cert_deco_bottom_sm.jpeg');
$logo = imagecreatefromjpeg($base . 'lcp_cert_logo.jpeg');

$tw = imagesx($top);
$th = imagesy($top);
imagecopy($canvas, $top, $w - $tw - 12, 8, 0, 0, $tw, $th);

$bw = imagesx($bot);
$bh = imagesy($bot);
imagecopy($canvas, $bot, 8, $h - $bh - 8, 0, 0, $bw, $bh);

$lw = imagesx($logo);
$lh = imagesy($logo);
imagecopy($canvas, $logo, (int) (($w - $lw) / 2), 28, 0, 0, $lw, $lh);

$out = $base . 'lcp_cert_bg.jpg';
imagejpeg($canvas, $out, 72);
imagedestroy($canvas);
imagedestroy($top);
imagedestroy($bot);
imagedestroy($logo);
echo filesize($out) . PHP_EOL;
