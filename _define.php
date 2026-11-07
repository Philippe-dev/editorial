<?php
/**
 * @brief Editorial, a theme for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Themes
 *
 * @copyright Philippe aka amalgame and HTML5 UP
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

if (isset($this) && is_object($this) && method_exists($this, 'registerModule') && isset($this->id) && is_string($this->id)) {
    $this->registerModule(
        'Éditorial',
        'A theme for Dotclear',
        'Philippe aka amalgame and contributors',
        '10.6',
        [
            'date'              => '2026-11-07T10:00:00+0100',
            'requires'          => [['core', '2.40']],
            'standalone_config' => true,
            'type'              => 'theme',
            'tplset'            => 'dotty',
            'overload'          => true,
        ]
    );
}
