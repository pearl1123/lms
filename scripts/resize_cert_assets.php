<?php
$base = dirname(__DIR__) . '/assets/img/certificate/';

function resize_jpeg($src, $dst, $maxW)
{
    $info = @getimagesize($src);
    if ( ! $info) {
        return false;
    }
    [$w, $h] = $info;
    $nw = min($w, $maxW);
    $nh = (int) round($h * $nw / $w);
    $srcImg = imagecreatefromjpeg($src);
    $dstImg = imagecreatetruecolor($nw, $nh);
    imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $nw, $nh, $w, $h);
    imagejpeg($dstImg, $dst, 82);
    imagedestroy($srcImg);
    imagedestroy($dstImg);

    return filesize($dst);
}

echo resize_jpeg($base . 'lcp_cert_deco_top.jpeg', $base . 'lcp_cert_deco_top_sm.jpeg', 320) . PHP_EOL;
echo resize_jpeg($base . 'lcp_cert_deco_bottom.jpeg', $base . 'lcp_cert_deco_bottom_sm.jpeg', 420) . PHP_EOL;
