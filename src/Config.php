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
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Form\Button;
use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Form\Color;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Fieldset;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Image;
use Dotclear\Helper\Html\Form\Input;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Legend;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Table;
use Dotclear\Helper\Html\Form\Tbody;
use Dotclear\Helper\Html\Form\Td;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Th;
use Dotclear\Helper\Html\Form\Thead;
use Dotclear\Helper\Html\Form\Tr;
use Dotclear\Helper\Html\Html;
use Exception;

class Config extends Process
{
    /**
     * @var     array<string, mixed>    $default_images
     */
    private static array $default_images = [];
    /**
     * @var     array<string, mixed>    $conf_images
     */
    private static array $conf_images = [];

    /**
     * @var     array<string, mixed>    $default_style
     */
    private static array $default_style = [];
    /**
     * @var     array<string, mixed>    $conf_style
     */
    private static array $conf_style = [];

    /**
     * @var     array<string, mixed>    $default_featured
     */
    private static array $default_featured = [];
    /**
     * @var     array<string, mixed>    $conf_featured
     */
    private static array $conf_featured = [];

    /**
     * @var     array<int, string>    $stickers_images
     */
    private static array $stickers_images = [];
    /**
     * @var     array<int, mixed>    $conf_stickers
     */
    private static array $conf_stickers = [];

    public static function init(): bool
    {
        // limit to backend permissions
        if (!self::status(My::checkContext(My::CONFIG))) {
            return false;
        }

        $decode = function (string $setting): array {
            $res = App::blog()->settings()->get('themes')->get(App::blog()->settings()->get('system')->get('theme') . '_' . $setting);
            $res = unserialize($res) ?: [];
            return is_array($res) ? $res : [];
        };

        // set default values
        self::$default_images = [
            'default_image_url'             => My::fileURL('/images/image-placeholder-1920x1080.jpg'),
            'default_image_tb_url'          => My::fileURL('/images/.image-placeholder-1920x1080_s.jpg'),
            'default_image_media_alt'       => '',
            'default_small_image_url'       => My::fileURL('/images/image-placeholder-600x338.jpg'),
            'default_small_image_tb_url'    => My::fileURL('/images/.image-placeholder-600x338_s.jpg'),
            'default_small_image_media_alt' => '',
            'images_disabled'               => false,
        ];
        self::$default_style = [
            'main_color' => '#f56a6a',
        ];
        self::$default_featured = [
            'featured_post_url' => '',
        ];
        self::$stickers_images = [
            'fab fa-diaspora',
            'fas fa-rss',
            'fab fa-linkedin-in',
            'fab fa-gitlab',
            'fab fa-github',
            'fab fa-twitter',
            'fab fa-facebook-f',
            'fab fa-instagram',
            'fab fa-mastodon',
            'fab fa-pinterest',
            'fab fa-snapchat',
            'fab fa-soundcloud',
            'fab fa-youtube',
        ];

        My::l10n('admin');

        App::backend()->standalone_config = (bool) App::themes()->moduleInfo(App::blog()->settings()->system->theme, 'standalone_config');

        // Load contextual help
        App::themes()->loadModuleL10Nresources(My::id(), App::lang()->getLang());

        # default or user defined images settings
        self::$conf_style    = array_merge(self::$default_style, $decode('style'));
        self::$conf_images   = array_merge(self::$default_images, $decode('images'));
        self::$conf_featured = array_merge(self::$default_featured, $decode('featured'));
        $stickers = $decode('stickers');

        // Get all sticker images already used
        $stickers_full = [];
        foreach ($stickers as $v) {
            $stickers_full[] = $v['image'];
        }

        // Add stickers images not already used
        // If you add stickers, remember to add them in myTable function into titles array
        foreach (self::$stickers_images as $v) {
            if (!in_array($v, $stickers_full)) {
                // image not already used
                $stickers[] = [
                    'label' => null,
                    'url'   => null,
                    'image' => $v, ];
            }
        }

        self::$conf_stickers = $stickers;

        App::backend()->conf_tab = $_POST['conf_tab'] ?? ($_GET['conf_tab'] ?? 'presentation');

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

        $encode = function (string $setting): void {
            App::blog()->settings()->get('themes')->put(
                App::blog()->settings()->get('system')->get('theme') . '_' . $setting, serialize(self::${'conf_' . $setting})
            );
        };

        if (!empty($_POST)) {
            try {
                // HTML
                if (App::backend()->conf_tab === 'presentation') {
                    if (isset($_POST['featured_post_url'])) {
                        self::$conf_featured['featured_post_url'] = $_POST['featured_post_url'];
                    }
                    $encode('featured');

                    if (isset($_POST['main_color'])) {
                        self::$conf_style['main_color'] = $_POST['main_color'];
                    }
                    $encode('style');

                    if (App::plugins()->moduleExists('featuredMedia')) {
                        //BIG IMAGE
                        # default image setting
                        self::$conf_images['default_image_url'] = $_POST['default_image_url'] ?: self::$default_images['default_image_url'];
                        # default image thumbnail settings
                        self::$conf_images['default_image_tb_url'] = $_POST['default_image_tb_url'] ?: self::$default_images['default_image_tb_url'];
                        # default image media alt settings
                        self::$conf_images['default_image_media_alt'] = $_POST['default_image_media_alt'] ?: self::$default_images['default_image_media_alt'];

                        //SMALL IMAGE
                        # default small image setting
                        self::$conf_images['default_small_image_url'] = $_POST['default_small_image_url'] ?: self::$default_images['default_small_image_url'];
                        # default small image settings
                        self::$conf_images['default_small_image_tb_url'] = $_POST['default_small_image_tb_url'] ?: self::$default_images['default_small_image_tb_url'];
                        # default small image media alt settings
                        self::$conf_images['default_small_image_media_alt'] = $_POST['default_small_image_media_alt'] ?: self::$default_images['default_small_image_media_alt'];

                        self::$conf_images['images_disabled'] = !empty($_POST['images_disabled']);

                        $encode('images');
                    }
                } elseif (App::backend()->conf_tab === 'stickers') {
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

                    self::$conf_stickers = $stickers;
                    $encode('stickers');
                }

                // Blog refresh
                App::blog()->triggerBlog();

                // Template cache reset
                App::cache()->emptyTemplatesCache();

                Notices::addSuccessNotice(__('Theme configuration upgraded.'));
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

        //Presentation tab
        echo
        (new Div('presentation'))
            ->class('multi-part')
            ->title(__('Presentation'))
            ->items([
                (new Form('theme_presentation'))
                ->action(App::backend()->url()->get('admin.blog.theme', ['conf' => '1', 'conf_tab' => 'presentation']))
                ->method('post')
                ->fields([
                    (new Fieldset())->class('fieldset')->legend((new Legend(__('Blog\'s featured publication'))))->fields([
                        (new Para())->items([
                            (new Label(__('Entry URL:') . ' ', Label::INSIDE_LABEL_BEFORE))->for('featured_post_url')->class('classic')
                                ->class('classic'),
                            (new Input('featured_post_url'))
                                ->size(50)
                                ->maxlength(255)
                                ->value(self::$conf_featured['featured_post_url']),
                            (new Button('featured_post_url_selector', __('Choose an entry')))
                                ->class('button')
                                ->type('button')
                                ->id('featured_post_url_selector'),
                        ]),
                        (new Note())
                            ->class(['form-note', 'info'])
                            ->text(__('Leave this field empty to use the default presentation (latest post)')),

                    ]),
                    (new Fieldset())->class('fieldset')->legend((new Legend(__('Colors'))))->fields([
                        (new Para())->items([
                            (new Label(__('Links and buttons\' color:'), Label::INSIDE_LABEL_BEFORE))->for('main_color'),
                            (new Color('main_color'))
                                ->size(30)
                                ->maxlength(255)
                                ->value(self::$conf_style['main_color']),
                        ]),
                    ]),
                    ... self::myFeatured(),

                    (new Para())->items([
                        (new Input('base_url'))
                            ->type('hidden')
                            ->value(App::blog()->url()),
                        (new Input('theme-url'))
                            ->type('hidden')
                            ->value(My::fileURL('')),
                        (new Input('change-button-id'))
                            ->type('hidden')
                            ->value(''),
                        (new Input('conf_tab'))
                            ->type('hidden')
                            ->value('presentation'),
                    ]),
                    (new Para())->items([
                        (new Submit(['presentation'], __('Save'))),
                        App::nonce()->formNonce(),

                    ]),
                ]),
            ])
        ->render();

        //Stickers tab
        echo
        (new Div('stickers'))
            ->class('multi-part')
            ->title(__('Stickers'))
            ->items([
                (new Form('theme_links'))
                ->action(App::backend()->url()->get('admin.blog.theme', ['conf' => '1', 'conf_tab' => 'stickers']))
                ->method('post')
                ->fields([
                    ... self::myTable(),
                    (new Para())->items([
                        (new Input('conf_tab'))
                            ->type('hidden')
                            ->value('stickers'),
                    ]),

                    (new Para())->items([
                        (new Submit(['stickers'], __('Save'))),
                        App::nonce()->formNonce(),
                    ]),
                ]),
            ])
        ->render();

        Page::helpBlock('editorial');
    }

    /**
     * @brief featuredMedia settings
     *
     * @return  array<int, Fieldset>
     */
    public static function myFeatured(): array
    {
        if (App::plugins()->moduleExists('featuredMedia')) {
            $fields = [
                (new Fieldset())->class('fieldset')->legend((new Legend(__('Placeholder images'))))->fields([
                    (new Div())
                    ->class(['box', 'theme'])->items([
                        (new Para())->items([
                            (new Label(__('Big image'), Label::INSIDE_LABEL_BEFORE))->for('default_image_tb_url')
                            ->class('classic'),
                        ]),
                        (new Para())->items([
                            (new Image(self::$conf_images['default_image_tb_url'], 'default_image_tb_src'))
                            ->alt(__('Thumbnail'))
                            ->width(240)
                            ->height(160)
                            ->disabled(true),
                        ]),
                        (new Para())->items([
                            (new Button('default_image_selector', __('Change')))
                                ->type('button')
                                ->id('default_image_selector'),
                            (new Text('span', ' ')),
                            (new Button('default_image_selector_reset', __('Reset')))
                                ->class('delete')
                                ->type('button')
                                ->id('default_image_selector_reset'),
                        ]),
                        (new Hidden('default_image_url'))
                            ->value(self::$conf_images['default_image_url']),
                        (new Hidden('default_image_tb_url'))
                            ->value(self::$conf_images['default_image_tb_url']),
                        (new Hidden('default_image_media_alt'))
                            ->value(self::$conf_images['default_image_media_alt']),
                    ]),
                    (new Div())
                        ->class(['box', 'theme'])->items([
                            (new Para())->items([
                                (new Label(__('Small image'), Label::INSIDE_LABEL_BEFORE))->for('default_small_image_tb_url')
                                ->class('classic'),
                            ]),
                            (new Para())->items([
                                (new Image(self::$conf_images['default_small_image_tb_url'], 'default_small_image_tb_src'))
                                ->alt(__('Thumbnail'))
                                ->width(240)
                                ->height(160)
                                ->disabled(true),
                            ]),
                            (new Para())->items([
                                (new Button('default_small_image_selector', __('Change')))
                                    ->type('button')
                                    ->id('default_small_image_selector'),
                                (new Text('span', ' ')),
                                (new Button('default_small_image_selector_reset', __('Reset')))
                                    ->class('delete')
                                    ->type('button')
                                    ->id('default_small_image_selector_reset'),
                            ]),
                            (new Hidden('default_small_image_url'))
                                ->value(self::$conf_images['default_small_image_url']),
                            (new Hidden('default_small_image_tb_url'))
                                ->value(self::$conf_images['default_small_image_tb_url']),
                            (new Hidden('default_small_image_media_alt'))
                                ->value(self::$conf_images['default_small_image_media_alt']),
                        ]),
                    (new Fieldset())->class('fieldset')->legend((new Legend(__('Option'))))->fields([
                        (new Para())->items([

                            (new Checkbox('images_disabled', self::$conf_images['images_disabled']))

                                ->label((new Label(__('Disable featured images'), Label::INSIDE_TEXT_AFTER))),
                            (new Note())
                                ->class(['form-note', 'info'])
                                ->text(__('This will disable all featured images, including the substitute ones. Images in your entries content will not be affected')),
                        ]),
                    ]),
                ]),
            ];
        } else {
            $fields = [
                (new Fieldset())->class('fieldset')->legend((new Legend(__('Featured images'))))->fields([
                    (new Para())->items([

                        (new Note())
                            ->class(['form-note', 'info'])
                            ->text(__('This theme needs the featuredMedia plugin to manage featured images.')),
                    ]),
                ]),
            ];
        }

        return $fields;
    }

    /**
     * @brief Stickers settings
     *
     * @return  array<int, Table>
     */
    public static function myTable(): array
    {
        $count = 0;

        $fields = [
            (new Table())->class('dragable')->items([
                (new Thead())->items([
                    (new Tr())->items([
                        (new Th())->text(''),
                        (new Th())->text(__('Image')),
                        (new Th())->scope('row')->text(__('Label')),
                        (new Th())->text(__('URL')),
                    ]),
                ]),
                (new Tbody())->id('stickerslist')->items(
                    array_map(function ($i, $v) use (&$count) {
                        $count++;

                        // Define title based on the sticker image. Add more icons as needed.
                        // Don't forget to add them into stickers_images array in init() function !
                        $titles = [
                            'fab fa-github'      => 'GitHub',
                            'fab fa-twitter'     => 'Twitter',
                            'fab fa-facebook-f'  => 'Facebook',
                            'fab fa-instagram'   => 'Instagram',
                            'fab fa-gitlab'      => 'GitLab',
                            'fas fa-rss'         => 'RSS',
                            'fab fa-linkedin-in' => 'LinkedIn',
                            'fab fa-youtube'     => 'YouTube',
                            'fab fa-pinterest'   => 'Pinterest',
                            'fab fa-snapchat'    => 'Snapchat',
                            'fab fa-soundcloud'  => 'SoundCloud',
                            'fab fa-mastodon'    => 'Mastodon',
                            'fab fa-diaspora'    => 'Diaspora',
                        ];
                        $title = $titles[$v['image']] ?? '';

                        return (new Tr())
                            ->class('line')
                            ->id('l_' . $i)
                            ->items([
                                (new Td())->class('handle')->items([
                                    (new Hidden('order[' . $i . ']'))
                                        ->min(0)
                                        ->max(count(self::$conf_stickers))
                                        ->value($count)
                                        ->class('position'),
                                    (new Hidden('dynorder[]'))->value($i),
                                    (new Hidden('dynorder-' . $i))->value($i),
                                    (new Hidden('ds_order'))->value(''),
                                ]),
                                (new Td())->class('linkimg')->title($title)->items([
                                    (new Hidden('sticker_image[]'))->value($v['image']),
                                    (new Text('i', ''))->class($v['image'])->title($v['label'] ?? $title),
                                ]),
                                (new Td())->items([
                                    (new Input('sticker_label[]'))
                                        ->size(20)
                                        ->maxlength(255)
                                        ->value($v['label'] ?? '')
                                        ->title(empty($v['label']) ? $title : $v['label']),
                                ]),
                                (new Td())->items([
                                    (new Input('sticker_url[]'))
                                        ->size(40)
                                        ->maxlength(255)
                                        ->value($v['url'] ?? '')
                                        ->title(empty($v['url']) ? __('Your URL:') : $v['url']),
                                ]),
                            ]);
                    }, array_keys(self::$conf_stickers), self::$conf_stickers)
                ),
            ]),
        ];

        return $fields;
    }
}
