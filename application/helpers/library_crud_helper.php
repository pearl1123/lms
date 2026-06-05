<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Render a list cell for registry-driven library tables.
 *
 * @param object $row
 * @param array  $col
 * @param array  $lib
 */
function libx_render_cell($row, array $col, array $lib)
{
    $field = $col['field'];
    $val   = $row->{$field} ?? '';
    $type  = $col['type'] ?? 'text';

    if ($type === 'status') {
        $archived = ! empty($lib['soft_delete']) && (
            (! empty($lib['soft_delete_invert']) && (int) $val !== 1)
            || (empty($lib['soft_delete_invert']) && (int) $val === 1)
        );
        if ($archived) {
            echo '<span class="libx-badge libx-badge--archived">Archived</span>';
        } else {
            echo '<span class="libx-badge libx-badge--active">Active</span>';
        }

        return;
    }

    if ($type === 'active_flag') {
        if ((int) $val === 1) {
            echo '<span class="libx-badge libx-badge--active">Active</span>';
        } else {
            echo '<span class="libx-badge libx-badge--archived">Inactive</span>';
        }

        return;
    }

    if ($type === 'badge') {
        echo '<span class="libx-badge libx-badge--neutral">' . htmlspecialchars((string) $val, ENT_QUOTES) . '</span>';

        return;
    }

    if ($type === 'color' && $val) {
        echo '<span class="libx-color-swatch" style="background:' . htmlspecialchars((string) $val, ENT_QUOTES) . '"></span> ';
        echo htmlspecialchars((string) $val, ENT_QUOTES);

        return;
    }

    if ($type === 'fk') {
        $fkField = $col['fk'] ?? $field;
        $label   = $row->{'fk_' . $fkField . '_label'} ?? $val;
        echo htmlspecialchars((string) $label, ENT_QUOTES);

        return;
    }

    $text = (string) $val;
    if ( ! empty($col['truncate'])) {
        $text = mb_strimwidth($text, 0, (int) $col['truncate'], '…');
    }

    echo htmlspecialchars($text, ENT_QUOTES);
}
