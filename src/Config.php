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
use Dotclear\Core\Process;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Backend\Notices;
use Exception;
use form;

class Config extends Process
{
    public static function init(): bool
    {
        // limit to backend permissions
        if (!self::status(My::checkContext(My::CONFIG))) {
            return false;
        }

        My::l10n('admin');

        App::backend()->standalone_config = (bool) App::themes()->moduleInfo(App::blog()->settings->system->theme, 'standalone_config');

        // Load contextual help
        App::themes()->loadModuleL10Nresources(My::id(), App::lang()->getLang());

        $featured = App::blog()->settings->themes->get(App::blog()->settings->system->theme . '_featured');
        $featured = $featured ? (unserialize($featured) ?: []) : [];

        if (!is_array($featured)) {
            $featured = [];
        }
        if (!isset($featured['featured_post_url'])) {
            $featured['featured_post_url'] = '';
        }

        $style = App::blog()->settings->themes->get(App::blog()->settings->system->theme . '_style');
        $style = $style ? (unserialize($style) ?: []) : [];
        if (!isset($style['main_color'])) {
            $style['main_color'] = '#f56a6a';
        }

        $stickers = App::blog()->settings->themes->get(App::blog()->settings->system->theme . '_stickers');
        $stickers = $stickers ? (unserialize($stickers) ?: []) : [];

        $stickers_full = [];
        // Get all sticker images already used
        if (is_array($stickers)) {
            foreach ($stickers as $v) {
                $stickers_full[] = $v['image'];
            }
        }
        // Get social media images
        $stickers_images = ['fab fa-diaspora', 'fas fa-rss', 'fab fa-linkedin-in', 'fab fa-gitlab', 'fab fa-github', 'fab fa-twitter', 'fab fa-facebook-f',
            'fab fa-instagram', 'fab fa-mastodon', 'fab fa-pinterest', 'fab fa-snapchat', 'fab fa-soundcloud', 'fab fa-youtube', ];
        if (is_array($stickers_images)) {
            foreach ($stickers_images as $v) {
                if (!in_array($v, $stickers_full)) {
                    // image not already used
                    $stickers[] = [
                        'label' => null,
                        'url'   => null,
                        'image' => $v, ];
                }
            }
        }

        App::backend()->featured = $featured;
        App::backend()->style    = $style;
        App::backend()->stickers = $stickers;

        App::backend()->conf_tab = $_POST['conf_tab'] ?? 'presentation';

        return self::status();
    }

    /**
     * Processes the request(s).
     */
    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        if (!empty($_POST)) {
            try {
                // HTML
                if (App::backend()->conf_tab === 'presentation') {
                    $featured                      = [];
                    $style                         = [];
                    $featured['featured_post_url'] = $_POST['featured_post_url'];
                    $style['main_color']           = $_POST['main_color'];

                    App::backend()->featured = $featured;
                    App::backend()->style    = $style;
                }

                if (App::backend()->conf_tab === 'links') {
                    $stickers = [];
                    for ($i = 0; $i < count($_POST['sticker_image']); $i++) {
                        $stickers[] = [
                            'label' => $_POST['sticker_label'][$i],
                            'url'   => $_POST['sticker_url'][$i],
                            'image' => $_POST['sticker_image'][$i],
                        ];
                    }

                    $order = [];
                    if (empty($_POST['ds_order']) && !empty($_POST['order'])) {
                        $order = $_POST['order'];
                        asort($order);
                        $order = array_keys($order);
                    }
                    if (!empty($order)) {
                        $new_stickers = [];
                        foreach ($order as $i => $k) {
                            $new_stickers[] = [
                                'label' => $stickers[$k]['label'],
                                'url'   => $stickers[$k]['url'],
                                'image' => $stickers[$k]['image'],
                            ];
                        }
                        $stickers = $new_stickers;
                    }
                    App::backend()->stickers = $stickers;
                }
                App::blog()->settings->themes->put(App::blog()->settings->system->theme . '_featured', serialize(App::backend()->featured));
                App::blog()->settings->themes->put(App::blog()->settings->system->theme . '_style', serialize(App::backend()->style));
                App::blog()->settings->themes->put(App::blog()->settings->system->theme . '_stickers', serialize(App::backend()->stickers));

                // Blog refresh
                App::blog()->triggerBlog();

                // Template cache reset
                App::cache()->emptyTemplatesCache();

                Notices::message(__('Theme configuration upgraded.'), true, true);
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        return true;
    }

    /**
     * Renders the page.
     */
    public static function render(): void
    {
        if (!self::status()) {
            return;
        }

        if (!App::backend()->standalone_config) {
            echo '</form>';
        }
        echo '<div class="multi-part" id="themes-list' . (App::backend()->conf_tab === 'presentation' ? '' : '-presentation') . '" title="' . __('Presentation') . '">';

        echo '<form id="theme_config" action="' . App::backend()->url()->get('admin.blog.theme', ['conf' => '1']) .
            '" method="post" enctype="multipart/form-data">';

        echo '<div class="fieldset">';

        echo '<h3>' . __('Blog\'s featured publication') . '</h3>';

        echo '<p><label for="featured_post_url" class="classic">' . __('Entry URL:') . '</label> ' .
            form::field('featured_post_url', 30, 255, App::backend()->featured['featured_post_url']) .
            ' <button type="button" id="featured_post_url_selector">' . __('Choose an entry') . '</button>' .
            '</p>' .
            '<p class="form-note info maximal">' . __('Leave this field empty to use the default presentation (latest post)') . '</p> ';
        echo '</div>';
        echo '<div class="fieldset">';
        echo '<h3>' . __('Colors') . '</h3>';

        echo '<p class="field"><label for="main_color">' . __('Links and buttons\' color:') . '</label> ' .
            form::color('main_color', 30, 255, App::backend()->style['main_color']) . '</p>' ;
        echo '</div>';
        echo '<p><input type="hidden" name="conf_tab" value="presentation"></p>';
        echo '<p class="clear"><input type="submit" value="' . __('Save') . '">' . App::nonce()->getFormNonce() . '</p>';
        echo form::hidden(['base_url'], App::blog()->url);
        echo '</form>';

        echo '</div>'; // Close tab

        echo '<div class="multi-part" id="themes-list' . (App::backend()->conf_tab === 'links' ? '' : '-links') . '" title="' . __('Stickers') . '">';
        echo '<form id="theme_config" action="' . App::backend()->url()->get('admin.blog.theme', ['conf' => '1']) .
            '" method="post" enctype="multipart/form-data">';
        echo '<div class="fieldset">';
        echo '<h3>' . __('Social links') . '</h3>';

        echo
        '<div class="table-outer">' .
        '<table class="dragable">' . '<caption class="sr-only">' . __('Social links (header)') . '</caption>' .
        '<thead>' .
        '<tr>' .
        '<th scope="col">' . '</th>' .
        '<th scope="col">' . __('Image') . '</th>' .
        '<th scope="col">' . __('Label') . '</th>' .
        '<th scope="col">' . __('URL') . '</th>' .
            '</tr>' .
            '</thead>' .
            '<tbody id="stickerslist">';
        $count = 0;
        foreach (App::backend()->stickers as $i => $v) {
            $count++;
            $v['service'] = str_replace('-link.png', '', $v['image']);
            echo
            '<tr class="line" id="l_' . $i . '">' .
            '<td class="handle">' . form::number(['order[' . $i . ']'], [
                'min'     => 0,
                'max'     => count(App::backend()->stickers),
                'default' => $count,
                'class'   => 'position',
            ]) .
            form::hidden(['dynorder[]', 'dynorder-' . $i], $i) . '</td>' .
            '<td class="linkimg">' . form::hidden(['sticker_image[]'], $v['image']) . '<i class="' . $v['image'] . '" title="' . $v['label'] . '"></i> ' . '</td>' .
            '<td scope="row">' . form::field(['sticker_label[]', 'dsl-' . $i], 20, 255, $v['label']) . '</td>' .
            '<td>' . form::field(['sticker_url[]', 'dsu-' . $i], 40, 255, $v['url']) . '</td>' .
                '</tr>';
        }
        echo
            '</tbody>' .
            '</table></div>';
        echo '</div>';

        echo '<p><input type="hidden" name="conf_tab" value="links"></p>';
        echo '<p class="clear">' . form::hidden('ds_order', '') . '<input type="submit" value="' . __('Save') . '">' . App::nonce()->getFormNonce() . '</p>';
        echo '</form>';

        echo '</div>'; // Close tab

        Page::helpBlock('editorial');

        // Legacy mode
        if (!App::backend()->standalone_config) {
            echo '<form style="display:none">';
        }
    }
}
