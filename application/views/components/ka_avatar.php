<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
/**
 * Standard LMS avatar (profile photo or initials fallback).
 *
 * @var object|array|null $user
 * @var string            $size   sm|md|lg|xl
 * @var string            $class  extra classes
 * @var string            $alt
 */
$user  = $user ?? null;
$size  = in_array($size ?? 'md', ['sm', 'md', 'lg', 'xl'], true) ? $size : 'md';
$class = trim((string) ($class ?? ''));
$alt   = trim((string) ($alt ?? ''));

$full_name = '';
if (is_object($user)) {
    $full_name = (string) ($user->fullname ?? $user->name ?? 'User');
} elseif (is_array($user)) {
    $full_name = (string) ($user['fullname'] ?? $user['name'] ?? 'User');
}

$avatar_url = ka_user_avatar_url($user);
$initials   = ka_user_initials($full_name !== '' ? $full_name : 'User');
if ($alt === '') {
    $alt = $full_name !== '' ? $full_name : 'User avatar';
}
?>
<span class="ka-avatar ka-avatar--<?= htmlspecialchars($size, ENT_QUOTES) ?> <?= htmlspecialchars($class, ENT_QUOTES) ?>" role="img" aria-label="<?= htmlspecialchars($alt, ENT_QUOTES) ?>">
  <?php if ($avatar_url !== ''): ?>
  <img class="ka-avatar-img" src="<?= htmlspecialchars($avatar_url, ENT_QUOTES) ?>" alt="">
  <?php else: ?>
  <span class="ka-avatar-initials" aria-hidden="true"><?= htmlspecialchars($initials, ENT_QUOTES) ?></span>
  <?php endif; ?>
  <span class="ka-avatar-ring" aria-hidden="true"></span>
</span>
