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

namespace Dotclear\Theme\editorial;

use Dotclear\App;
use Dotclear\Helper\Process\TraitProcess;

class Backend
{
    use TraitProcess;
    
    public static function init(): bool
    {
        return self::status(My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        App::behavior()->addBehavior('adminPageHTMLHead', function () {
            if (App::blog()->settings()->get('system')->get('theme') !== My::id()) {
                return;
            }

            echo
            My::jsLoad('admin.js') . "\n" .
            My::jsLoad('popup_posts.js') . "\n" .
            My::jsLoad('popup_media.js') . "\n" .
            My::cssLoad('admin.css') . "\n" ;

            if (!App::auth()->prefs()->get('accessibility')->get('nodragdrop')) {
                echo
                App::backend()->page()->jsLoad('js/jquery/jquery-ui.custom.js') .
                App::backend()->page()->jsLoad('js/jquery/jquery.ui.touch-punch.js');
            }
        });

        return true;
    }
}
