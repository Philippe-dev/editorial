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
use Dotclear\Helper\File\Files;
use Dotclear\Helper\Html\Form\Button;
use Dotclear\Helper\Html\Form\Caption;
use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Form\Color;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Fieldset;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Img;
use Dotclear\Helper\Html\Form\Input;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Legend;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Radio;
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

class Config
{
    use TraitProcess;
    
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
            $res = unserialize((string) $res) ?: [];

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
            'main_color'      => '#EA1010',
            'main_dark_color' => '#F37C7C',
            'mode'            => 'auto',
        ];
        self::$default_featured = [
            'featured_post_url' => '',
        ];
        self::$stickers_images = [];

        // If you add stickers above, remember to add them in myTable function into titles array

        My::l10n('admin');

        App::backend()->standalone_config = (bool) App::themes()->moduleInfo(App::blog()->settings()->system->theme, 'standalone_config');

        // Load contextual help
        App::themes()->loadModuleL10Nresources(My::id(), App::lang()->getLang());

        # default or user defined images settings
        self::$conf_style    = array_merge(self::$default_style, $decode('style'));
        self::$conf_images   = array_merge(self::$default_images, $decode('images'));
        self::$conf_featured = array_merge(self::$default_featured, $decode('featured'));
        $stickers            = $decode('stickers');

        // Get all sticker images already used
        $stickers_full = [];
        foreach ($stickers as $v) {
            $stickers_full[] = $v['image'];
        }

        $svg_path = My::path() . '/svg/';

        $stickers_images = Files::scandir($svg_path);
        if (is_array($stickers_images)) {
            foreach ($stickers_images as $v) {
                if (preg_match('/^(.*)\.svg$/', $v)) {
                    if (!in_array($v, $stickers_full)) {
                        // image not already used
                        $stickers[] = [
                            'label' => preg_replace('/\.svg$/', '', $v),
                            'url'   => null,
                            'image' => $v];
                    }
                }
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
                App::blog()->settings()->get('system')->get('theme') . '_' . $setting,
                serialize(self::${'conf_' . $setting})
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

                    if (isset($_POST['main_dark_color'])) {
                        self::$conf_style['main_dark_color'] = $_POST['main_dark_color'];
                    }

                    if (isset($_POST['mode'])) {
                        self::$conf_style['mode'] = $_POST['mode'];
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
                        # default small image thumbnail settings
                        self::$conf_images['default_small_image_tb_url'] = $_POST['default_small_image_tb_url'] ?: self::$default_images['default_small_image_tb_url'];
                        # default small image media alt settings
                        self::$conf_images['default_small_image_media_alt'] = $_POST['default_small_image_media_alt'] ?: self::$default_images['default_small_image_media_alt'];

                        self::$conf_images['images_disabled'] = !empty($_POST['images_disabled']);

                        $encode('images');

                        App::backend()->notices()->addSuccessNotice(__('Theme presentation has been updated.'));
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

                    App::backend()->notices()->addSuccessNotice(__('Theme stickers have been updated.'));
                }

                // Blog refresh
                App::blog()->triggerBlog();

                // Template cache reset
                App::cache()->emptyTemplatesCache();
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
                ->action(App::backend()->url()->get('admin.blog.theme', ['conf' => '1', 'conf_tab' => 'presentation']) . '#presentation')
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
                            ->class(['form-note'])
                            ->text(__('Leave this field empty to use the default presentation (latest post)')),

                    ]),
                    (new Fieldset())->class('fieldset')->legend((new Legend(__('Colors'))))->fields([
                        (new Div())
                        ->class('three-boxes')
                        ->items([
                            (new Para())->class('classic')->items([
                                (new Label(__('Theme:'), Label::INSIDE_LABEL_BEFORE))->for('mode'),
                                (new Radio(['mode'], (self::$conf_style['mode'] == 'light')))
                                    ->value('light')
                                    ->label((new Label(__('Light'), Label::INSIDE_TEXT_AFTER))),
                                (new Radio(['mode'], (self::$conf_style['mode'] == 'dark')))
                                    ->value('dark')
                                    ->label((new Label(__('Dark'), Label::INSIDE_TEXT_AFTER))),
                                (new Radio(['mode'], (self::$conf_style['mode'] == 'auto')))
                                    ->value('auto')
                                    ->label((new Label(__('Automatic'), Label::INSIDE_TEXT_AFTER))),
                            ]),
                        ]),
                        (new Div())
                            ->class('three-boxes')
                            ->items([
                                (new Para())->class('classic')->items([
                                    (new Label(__('Light theme main color:'), Label::INSIDE_LABEL_BEFORE))->for('main_color'),
                                    (new Color('main_color'))
                                        ->size(30)
                                        ->maxlength(255)
                                        ->value(self::$conf_style['main_color']),
                                ]),
                            ]),
                        (new Div())
                            ->class('three-boxes')
                            ->items([
                                (new Para())->class('classic')->items([
                                    (new Label(__('Dark theme main color:'), Label::INSIDE_LABEL_BEFORE))->for('main_dark_color'),
                                    (new Color('main_dark_color'))
                                        ->size(30)
                                        ->maxlength(255)
                                        ->value(self::$conf_style['main_dark_color']),
                                ]),
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
                            ->id('conf_tab_presentation')
                            ->type('hidden')
                            ->value('presentation'),
                    ]),
                    (new Para())->items([
                        (new Submit(['presentation'], __('Save presentation'))),
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
                ->action(App::backend()->url()->get('admin.blog.theme', ['conf' => '1', 'conf_tab' => 'stickers']) . '#stickers')
                ->method('post')
                ->fields([
                    ... self::myTable(),
                    (new Para())->items([
                        (new Input('conf_tab'))
                            ->id('conf_tab_stickers')
                            ->type('hidden')
                            ->value('stickers'),
                    ]),
                    (new Note())
                            ->class(['form-note', 'hidden-if-js', 'clear', 'table-note'])
                            ->text(__('To rearrange stickers order, change number at the begining of the line, then click on “Save stickers” button.')),
                    (new Note())
                        ->class(['form-note', 'hidden-if-no-js', 'clear', 'table-note'])
                        ->text(__('To rearrange stickers order, move items by drag and drop, then click on “Save stickers” button.')),

                    (new Para())->items([
                        (new Submit(['stickers'], __('Save stickers'))),
                        App::nonce()->formNonce(),
                    ]),
                ]),
            ])
        ->render();

        App::backend()->page()->helpBlock('editorial');
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
                    ->class('box')->items([
                        (new Para())->items([
                            (new Label(__('Big image'), Label::INSIDE_LABEL_BEFORE))->for('default_image_tb_url')
                            ->class('classic'),
                        ]),
                        (new Para())->items([
                            (new Img('default_image_tb_src'))
                                ->id('default_image_tb_src')
                                ->class('thumbnail')
                                ->src(self::$conf_images['default_image_tb_url'])
                                ->alt(self::$conf_images['default_image_media_alt'])
                                ->title(self::$conf_images['default_image_media_alt'])
                                ->width(240)
                                ->height(160),
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
                        ->class('box')->items([
                            (new Para())->items([
                                (new Label(__('Small image'), Label::INSIDE_LABEL_BEFORE))->for('default_small_image_tb_url')
                                ->class('classic'),
                            ]),
                            (new Para())->items([
                                (new Img('default_small_image_tb_src'))
                                    ->id('default_small_image_tb_src')
                                    ->class('thumbnail')
                                    ->src(self::$conf_images['default_small_image_tb_url'])
                                    ->alt(self::$conf_images['default_small_image_media_alt'])
                                    ->title(self::$conf_images['default_small_image_media_alt'])
                                    ->width(240)
                                    ->height(160),
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
                ]),
                (new Fieldset())->class('fieldset')->legend((new Legend(__('Option'))))->fields([
                    (new Para())->items([
                        (new Checkbox('images_disabled', self::$conf_images['images_disabled']))
                            ->label((new Label(__('Disable featured images'), Label::INSIDE_TEXT_AFTER))),
                    ]),
                    (new Note())
                        ->class(['form-note'])
                        ->text(__('This will disable all featured images, including the substitute ones. Images in your entries content will not be affected')),
                ]),
            ];
        } else {
            $fields = [
                (new Fieldset())->class('fieldset')->legend((new Legend(__('Featured images'))))->fields([
                    (new Note())
                        ->class(['form-note', 'info'])
                        ->text(__('This theme needs the featuredMedia plugin to manage featured images.')),
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
            (new Table())
                ->class('dragable')
                ->extra('aria-describedby="table-note"')
                ->caption((new Caption(__('Social links (header)')))->class('pretty-title'))
                ->items([
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
                                        (new Hidden('dynorder[]'))
                                            ->id('dynorder[' . $i . ']')
                                            ->value($i),
                                        (new Hidden('dynorder-' . $i))->value($i),
                                        (new Hidden('ds_order'))
                                            ->id('ds_order[' . $i . ']')
                                            ->value(''),
                                    ]),
                                    (new Td())->class('linkimg')->title($v['label'])->items([
                                        (new Hidden('sticker_image[]'))
                                            ->id('sticker_image[' . $i . ']')
                                            ->value($v['image']),
                                        (new Img('image[' . $i . ']'))
                                            ->class('svg')
                                            ->src(My::fileURL('/svg/' . $v['image']))
                                            ->alt($v['label'])
                                            ->title($v['label']),
                                    ]),
                                    (new Td())->items([
                                        (new Input('sticker_label[]'))
                                            ->id('sticker_label[' . $i . ']')
                                            ->size(20)
                                            ->maxlength(255)
                                            ->value($v['label'] ?? '')
                                            ->title(empty($v['label']) ? $v['label'] : $v['label']),
                                    ]),
                                    (new Td())->items([
                                        (new Input('sticker_url[]'))
                                            ->id('sticker_url[' . $i . ']')
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
