<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Profile avatar processing — resize, thumbnail, cleanup.
 */
class Avatar_service {

    protected $max_dim = 400;
    protected $thumb_dim = 80;

    /**
     * Process uploaded image in place; returns full + thumb relative paths.
     *
     * @param string $abs_path Absolute path to saved upload
     * @param int    $user_id
     * @param string $ext jpg|png|webp
     * @return array{full:string,thumb:string}
     */
    public function process_saved_file($abs_path, $user_id, $ext)
    {
        $uid = (int) $user_id;
        $dir = FCPATH . 'uploads/avatars/';
        if ( ! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $use_ext = $ext === 'jpeg' ? 'jpg' : $ext;
        $base    = 'user_' . $uid . '_' . time();
        $full_rel  = 'uploads/avatars/' . $base . '.' . $use_ext;
        $thumb_rel = 'uploads/avatars/' . $base . '_thumb.' . $use_ext;
        $full_abs  = $dir . $base . '.' . $use_ext;
        $thumb_abs = $dir . $base . '_thumb.' . $use_ext;

        if ($abs_path !== $full_abs && is_file($abs_path)) {
            @rename($abs_path, $full_abs);
        }

        $this->_resize_image($full_abs, $use_ext, $this->max_dim, $this->max_dim);
        $this->_create_thumbnail($full_abs, $thumb_abs, $use_ext, $this->thumb_dim);

        return ['full' => $full_rel, 'thumb' => $thumb_rel];
    }

    /**
     * @param string $avatar_path Relative avatar path from DB
     */
    public function delete_avatar_files($avatar_path)
    {
        $rel = ltrim(str_replace(['../', '..\\'], '', (string) $avatar_path), '/\\');
        if ($rel === '') {
            return;
        }

        $abs = FCPATH . $rel;
        if (is_file($abs)) {
            @unlink($abs);
        }

        $thumb = preg_replace('/(\.[^.]+)$/', '_thumb$1', $rel);
        if ($thumb && is_file(FCPATH . $thumb)) {
            @unlink(FCPATH . $thumb);
        }
    }

    /**
     * @param string $abs
     * @param string $ext
     * @param int    $max_w
     * @param int    $max_h
     */
    private function _resize_image($abs, $ext, $max_w, $max_h)
    {
        if ( ! is_file($abs) || ! function_exists('imagecreatetruecolor')) {
            return;
        }

        $src = $this->_load_image($abs, $ext);
        if ( ! $src) {
            return;
        }

        $w = imagesx($src);
        $h = imagesy($src);
        if ($w < 1 || $h < 1) {
            imagedestroy($src);

            return;
        }

        $scale = min($max_w / $w, $max_h / $h, 1.0);
        $nw    = (int) max(1, round($w * $scale));
        $nh    = (int) max(1, round($h * $scale));

        if ($scale >= 1.0) {
            imagedestroy($src);

            return;
        }

        $dst = imagecreatetruecolor($nw, $nh);
        if ($ext === 'png' || $ext === 'webp') {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
        }

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
        $this->_save_image($dst, $abs, $ext);
        imagedestroy($src);
        imagedestroy($dst);
    }

    /**
     * @param string $src_abs
     * @param string $dst_abs
     * @param string $ext
     * @param int    $dim
     */
    private function _create_thumbnail($src_abs, $dst_abs, $ext, $dim)
    {
        if ( ! is_file($src_abs) || ! function_exists('imagecreatetruecolor')) {
            return;
        }

        $src = $this->_load_image($src_abs, $ext);
        if ( ! $src) {
            return;
        }

        $w = imagesx($src);
        $h = imagesy($src);
        $side = min($w, $h);
        $sx = (int) max(0, ($w - $side) / 2);
        $sy = (int) max(0, ($h - $side) / 2);

        $dst = imagecreatetruecolor($dim, $dim);
        if ($ext === 'png' || $ext === 'webp') {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
        }

        imagecopyresampled($dst, $src, 0, 0, $sx, $sy, $dim, $dim, $side, $side);
        $this->_save_image($dst, $dst_abs, $ext);
        imagedestroy($src);
        imagedestroy($dst);
    }

    /**
     * @param string $abs
     * @param string $ext
     * @return resource|false
     */
    private function _load_image($abs, $ext)
    {
        if ($ext === 'png') {
            return @imagecreatefrompng($abs);
        }
        if ($ext === 'webp' && function_exists('imagecreatefromwebp')) {
            return @imagecreatefromwebp($abs);
        }

        return @imagecreatefromjpeg($abs);
    }

    /**
     * @param resource $img
     * @param string   $abs
     * @param string   $ext
     */
    private function _save_image($img, $abs, $ext)
    {
        if ($ext === 'png') {
            imagepng($img, $abs, 6);
        } elseif ($ext === 'webp' && function_exists('imagewebp')) {
            imagewebp($img, $abs, 85);
        } else {
            imagejpeg($img, $abs, 88);
        }
    }
}
