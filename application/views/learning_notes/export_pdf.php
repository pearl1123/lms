<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Learning Notes PDF — DOMPDF · A4 portrait
 * Visual language inspired by Enterprise SaaS certificate (template_pdf).
 */

$notes         = isset($notes) && is_array($notes) ? $notes : [];
$owner_name    = trim((string) ($owner_name ?? 'Learner'));
$exported_at   = trim((string) ($exported_at ?? date('F j, Y g:i A')));
$lms_name      = trim((string) ($lms_name ?? 'kaBAGA Academy'));
$logo_src      = (string) ($logo_src ?? '');
$org_name      = trim((string) ($org_name ?? ''));
$is_collection = ! empty($is_collection) && count($notes) > 1;

$h = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
@page { size: A4 portrait; margin: 0; }

* { margin: 0; padding: 0; box-sizing: border-box; }

html, body {
    font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
    color: #0F172A;
    background: #ffffff;
}

.page {
    position: relative;
    width: 595pt;
    min-height: 842pt;
    page-break-after: always;
    background: #ffffff;
    overflow: hidden;
}

.page:last-child {
    page-break-after: auto;
}

.frame {
    margin: 28pt 32pt 24pt 32pt;
    border: 1pt solid #E2E8F0;
    border-radius: 8pt;
    min-height: 786pt;
    overflow: hidden;
}

.hdr-table {
    width: 100%;
    border-collapse: collapse;
}

.hdr-table td {
    vertical-align: middle;
    padding: 22pt 28pt 16pt 28pt;
}

.hdr-left { width: 58%; }

.hdr-logo {
    width: 34pt;
    height: auto;
    vertical-align: middle;
}

.hdr-lms-name {
    font-size: 12pt;
    font-weight: bold;
    color: #0F172A;
    letter-spacing: 0.3pt;
    padding-left: 10pt;
    line-height: 1.2;
}

.hdr-lms-sub {
    font-size: 7.5pt;
    color: #64748B;
    letter-spacing: 0.8pt;
    text-transform: uppercase;
    padding-left: 10pt;
    padding-top: 2pt;
}

.hdr-right {
    text-align: right;
    width: 42%;
}

.hdr-doc-type {
    font-size: 8pt;
    font-weight: bold;
    color: #2563EB;
    letter-spacing: 1.2pt;
    text-transform: uppercase;
    line-height: 1.35;
}

.hdr-doc-sub {
    font-size: 7pt;
    color: #94A3B8;
    padding-top: 3pt;
    line-height: 1.35;
}

.accent-bar {
    width: 100%;
    height: 3pt;
    background: #2563EB;
}

.main-wrap {
    padding: 24pt 28pt 20pt 28pt;
}

.doc-label {
    font-size: 8pt;
    font-weight: bold;
    color: #2563EB;
    letter-spacing: 2pt;
    text-transform: uppercase;
    padding-bottom: 10pt;
}

.doc-title {
    font-size: 20pt;
    font-weight: bold;
    color: #0F172A;
    line-height: 1.25;
    padding-bottom: 14pt;
}

.doc-subtitle {
    font-size: 10pt;
    color: #64748B;
    line-height: 1.5;
    padding-bottom: 18pt;
}

.meta-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 8pt;
    margin-bottom: 18pt;
}

.meta-table td {
    width: 50%;
    vertical-align: top;
    padding-right: 8pt;
}

.meta-box {
    background: #F8FAFC;
    border: 1pt solid #E2E8F0;
    border-radius: 6pt;
    padding: 9pt 11pt;
}

.meta-label {
    font-size: 6.5pt;
    font-weight: bold;
    color: #94A3B8;
    letter-spacing: 0.8pt;
    text-transform: uppercase;
    padding-bottom: 3pt;
}

.meta-value {
    font-size: 8.5pt;
    font-weight: bold;
    color: #0F172A;
    line-height: 1.35;
    word-break: break-word;
}

.content-box {
    background: #ffffff;
    border: 1pt solid #E2E8F0;
    border-left: 4pt solid #2563EB;
    border-radius: 6pt;
    padding: 16pt 18pt;
    min-height: 120pt;
}

.content-heading {
    font-size: 7pt;
    font-weight: bold;
    color: #64748B;
    letter-spacing: 1pt;
    text-transform: uppercase;
    padding-bottom: 10pt;
}

.content-body {
    font-size: 10pt;
    color: #334155;
    line-height: 1.65;
    white-space: pre-wrap;
    word-break: break-word;
}

.footer-strip {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    border-top: 1pt solid #E2E8F0;
    background: #F8FAFC;
    padding: 10pt 28pt;
    text-align: center;
    font-size: 6.5pt;
    color: #94A3B8;
    letter-spacing: 0.4pt;
    line-height: 1.45;
}

.cover-stat-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 10pt;
    margin-top: 8pt;
}

.cover-stat-table td {
    width: 50%;
    padding-right: 8pt;
}

.cover-stat {
    background: #F8FAFC;
    border: 1pt solid #E2E8F0;
    border-radius: 8pt;
    padding: 14pt;
    text-align: center;
}

.cover-stat-num {
    font-size: 22pt;
    font-weight: bold;
    color: #2563EB;
    line-height: 1;
    padding-bottom: 4pt;
}

.cover-stat-label {
    font-size: 7pt;
    color: #64748B;
    text-transform: uppercase;
    letter-spacing: 0.8pt;
}

.empty-msg {
    font-size: 11pt;
    color: #64748B;
    line-height: 1.6;
    padding: 24pt 0;
}
</style>
</head>
<body>

<?php if ($is_collection): ?>
<div class="page">
    <div class="frame">
        <table class="hdr-table" cellpadding="0" cellspacing="0">
            <tr>
                <td class="hdr-left">
                    <table cellpadding="0" cellspacing="0"><tr>
                        <?php if ($logo_src !== ''): ?>
                        <td><img class="hdr-logo" src="<?= $h($logo_src) ?>" alt=""></td>
                        <?php endif; ?>
                        <td>
                            <div class="hdr-lms-name"><?= $h($lms_name) ?></div>
                            <div class="hdr-lms-sub">Learning Management System</div>
                        </td>
                    </tr></table>
                </td>
                <td class="hdr-right">
                    <div class="hdr-doc-type">Notes Export</div>
                    <div class="hdr-doc-sub">Personal learning workspace</div>
                </td>
            </tr>
        </table>
        <div class="accent-bar"></div>
        <div class="main-wrap">
            <div class="doc-label">My Learning Notes</div>
            <div class="doc-title">Exported collection</div>
            <div class="doc-subtitle">Prepared for <?= $h($owner_name) ?> · <?= $h($exported_at) ?></div>
            <table class="cover-stat-table" cellpadding="0" cellspacing="0">
                <tr>
                    <td>
                        <div class="cover-stat">
                            <div class="cover-stat-num"><?= count($notes) ?></div>
                            <div class="cover-stat-label">Notes included</div>
                        </div>
                    </td>
                    <td>
                        <div class="cover-stat">
                            <div class="cover-stat-num" style="font-size:14pt;padding-top:4pt;">PDF</div>
                            <div class="cover-stat-label">Plain text format inside</div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
        <div class="footer-strip">
            <?= $h($lms_name) ?> · Personal learning notes · Not an official credential
            <?php if ($org_name !== ''): ?> · <?= $h($org_name) ?><?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($notes === []): ?>
<div class="page">
    <div class="frame">
        <div class="accent-bar"></div>
        <div class="main-wrap">
            <div class="doc-label">Learning Note</div>
            <div class="doc-title">No notes found</div>
            <p class="empty-msg">No notes matched your filters at the time of export.</p>
        </div>
        <div class="footer-strip"><?= $h($lms_name) ?> · Exported <?= $h($exported_at) ?></div>
    </div>
</div>
<?php endif; ?>

<?php foreach ($notes as $note):
    $title   = trim((string) ($note->note_title ?? '')) ?: 'Untitled note';
    $content = trim((string) ($note->note_content ?? ''));
    $course  = trim((string) ($note->course_title ?? ''));
    $module  = trim((string) ($note->module_title ?? ''));
    $context = trim((string) ($note->context_label ?? ''));
    $tags    = is_array($note->tags ?? null) ? $note->tags : [];
    $updated = (string) ($note->updated_at ?? $note->created_at ?? '');
    $updated_label = $updated !== '' ? date('F j, Y g:i A', strtotime($updated)) : '—';
    $title_len = function_exists('mb_strlen') ? mb_strlen($title) : strlen($title);
    $title_size = $title_len > 56 ? '16pt' : ($title_len > 40 ? '18pt' : '20pt');
?>
<div class="page">
    <div class="frame">
        <table class="hdr-table" cellpadding="0" cellspacing="0">
            <tr>
                <td class="hdr-left">
                    <table cellpadding="0" cellspacing="0"><tr>
                        <?php if ($logo_src !== ''): ?>
                        <td><img class="hdr-logo" src="<?= $h($logo_src) ?>" alt=""></td>
                        <?php endif; ?>
                        <td>
                            <div class="hdr-lms-name"><?= $h($lms_name) ?></div>
                            <div class="hdr-lms-sub">Learning Management System</div>
                        </td>
                    </tr></table>
                </td>
                <td class="hdr-right">
                    <div class="hdr-doc-type">Learning Note</div>
                    <div class="hdr-doc-sub"><?= $h($owner_name) ?></div>
                </td>
            </tr>
        </table>
        <div class="accent-bar"></div>
        <div class="main-wrap">
            <div class="doc-label">Personal notes</div>
            <div class="doc-title" style="font-size:<?= $title_size ?>;"><?= $h($title) ?></div>

            <table class="meta-table" cellpadding="0" cellspacing="0">
                <tr>
                    <td>
                        <div class="meta-box">
                            <div class="meta-label">Course</div>
                            <div class="meta-value"><?= $h($course !== '' ? $course : '—') ?></div>
                        </div>
                    </td>
                    <td>
                        <div class="meta-box">
                            <div class="meta-label">Module</div>
                            <div class="meta-value"><?= $h($module !== '' ? $module : '—') ?></div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="meta-box">
                            <div class="meta-label">Context</div>
                            <div class="meta-value"><?= $h($context !== '' ? $context : '—') ?></div>
                        </div>
                    </td>
                    <td>
                        <div class="meta-box">
                            <div class="meta-label">Last updated</div>
                            <div class="meta-value"><?= $h($updated_label) ?></div>
                        </div>
                    </td>
                </tr>
                <?php if ($tags !== []): ?>
                <tr>
                    <td colspan="2">
                        <div class="meta-box">
                            <div class="meta-label">Tags</div>
                            <div class="meta-value"><?= $h(implode(', ', $tags)) ?></div>
                        </div>
                    </td>
                </tr>
                <?php endif; ?>
            </table>

            <div class="content-box">
                <div class="content-heading">Note content</div>
                <div class="content-body"><?= $h($content) ?></div>
            </div>
        </div>
        <div class="footer-strip">
            Exported <?= $h($exported_at) ?> · <?= $h($lms_name) ?> · Personal use only
        </div>
    </div>
</div>
<?php endforeach; ?>

</body>
</html>
