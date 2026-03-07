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

use ArrayObject;
use Dotclear\App;
use Dotclear\Helper\Process\TraitProcess;

class Frontend
{
    use TraitProcess;

    public static function init(): bool
    {
        return self::status(My::checkContext(My::FRONTEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        # load locales
        My::l10n('main');

        # Templates

        App::frontend()->template()->addBlock('editorialDefaultIf', self::editorialDefaultIf(...));
        App::frontend()->template()->addBlock('editorialFeaturedIf', self::editorialFeaturedIf(...));

        App::frontend()->template()->addBlock('editorialImagesIf', self::editorialImagesIf(...));

        App::frontend()->template()->addValue('editorialBigImage', self::editorialBigImage(...));
        App::frontend()->template()->addValue('editorialSmallImage', self::editorialSmallImage(...));

        App::frontend()->template()->addValue('editorialBigImageAlt', self::editorialBigImageAlt(...));
        App::frontend()->template()->addValue('editorialSmallImageAlt', self::editorialSmallImageAlt(...));

        App::frontend()->template()->addValue('editorialUserColors', self::editorialUserColors(...));

        App::frontend()->template()->addValue('editorialSocialLinks', self::editorialSocialLinks(...));

        App::behavior()->addBehavior('templateBeforeBlockV2', self::templateBeforeBlock(...));

        App::frontend()->template()->addBlock('TagsIfFirstLetter', self::TagsIfFirstLetter(...));
        App::frontend()->template()->addValue('TagsFirstLetter', self::TagsFirstLetter(...));

        return true;
    }

    /**
     * Get all first letters of tags and sort tags by first letter
     *
     * @param ArrayObject $attr
     * @param string $content
     * @return string
     */
    public static function TagsIfFirstLetter(ArrayObject $attr, string $content): string
    {
        return
        '<?php ' .
        'if (!empty(App::frontend()->context()->TagsFirstLetter)) {' .
        'if (mb_strlen(App::frontend()->context()->TagsFirstLetter) == 0) {App::frontend()->context()->TagsFirstLetter = null;}' .
        '}' .
        'App::frontend()->context()->TagsFirstLetter = mb_strtoupper(Dotclear\Helper\Text::cutString(App::frontend()->context()->meta->meta_id_lower,1));' .
        'if (App::frontend()->context()->TagsFirstLetter != App::frontend()->context()->TagsFirstLetter_next) : ' .
        '?>' .
        $content .
        '<?php endif;' .
        'App::frontend()->context()->TagsFirstLetter_next = App::frontend()->context()->TagsFirstLetter;' .
        ' ?>';
    }

    /**
     * Get first letter of a tag
     *
     * @param ArrayObject $attr
     * @return string
     */
    public static function TagsFirstLetter(ArrayObject $attr): string
    {
        $f = App::frontend()->template()->getFilters($attr);

        return '<?php echo ' . sprintf($f, 'App::frontend()->context()->TagsFirstLetter') . '; ?>';
    }

    /**
     * @param   ArrayObject<string, mixed>  $attr   The attributes
     */
    public static function editorialDefaultIf(ArrayObject $attr, string $content): string
    {
        return '<?php if (' . self::class . '::defaultIf()) { ?>' . $content . '<?php } ?>';
    }

    public static function defaultIf(): bool
    {
        $featured = self::decode('featured');

        return empty($featured['featured_post_url'] ?? '');
    }

    /**
     * @param   ArrayObject<string, mixed>  $attr   The attributes
     */
    public static function editorialFeaturedIf(ArrayObject $attr, string $content): string
    {
        return '<?php if (!' . self::class . '::defaultIf()) { ?>' . $content . '<?php } ?>';
    }

    /**
     * @param   ArrayObject<string, mixed>  $attr   The attributes
     */
    public static function editorialImagesIf(ArrayObject $attr, string $content): string
    {
        return '<?php if (' . self::class . '::imagesIf()) { ?>' . $content . '<?php } ?>';
    }

    public static function imagesIf(): bool
    {
        $images = self::decode('images');

        $images['images_disabled'] ??= false;

        if (!App::plugins()->moduleExists('featuredMedia')) {
            $images['images_disabled'] = true;
        }

        return $images['images_disabled'] === false;
    }

    /**
     * @param   ArrayObject<string, mixed>  $attr   The attributes
     */
    public static function editorialBigImage(ArrayObject $attr): string
    {
        return '<?php echo ' . self::class . '::bigImageHelper(); ?>';
    }

    public static function bigImageHelper(): string
    {
        $default_image_url = My::fileURL('/images/image-placeholder-1920x1080.jpg');

        $images = App::blog()->settings->themes->get(App::blog()->settings->system->theme . '_images');
        $images = $images ? (unserialize($images) ?: []) : [];

        if (!is_array($images)) {
            $images = [];
        }
        if (!isset($images['default_image_url']) || empty($images['default_image_url'])) {
            $images['default_image_url'] = $default_image_url;
        }

        return $images['default_image_url'];
    }

    /**
     * @param   ArrayObject<string, mixed>  $attr   The attributes
     */
    public static function editorialBigImageAlt(ArrayObject $attr): string
    {
        return '<?= ' . self::class . '::bigImageAltHelper() ?>';
    }

    public static function bigImageAltHelper(): string
    {
        $images = self::decode('images');

        return $images['default_image_media_alt'] ?? '';
    }

    /**
     * @param   ArrayObject<string, mixed>  $attr   The attributes
     */
    public static function editorialSmallImage(ArrayObject $attr): string
    {
        return '<?= ' . self::class . '::smallImageHelper() ?>';
    }

    public static function smallImageHelper(): string
    {
        $default_image_url = My::fileURL('/images/image-placeholder-600x338.jpg');

        $images = App::blog()->settings->themes->get(App::blog()->settings->system->theme . '_images');
        $images = $images ? (unserialize($images) ?: []) : [];

        if (!is_array($images)) {
            $images = [];
        }
        if (!isset($images['default_small_image_url']) || empty($images['default_small_image_url'])) {
            $images['default_small_image_url'] = $default_image_url;
        }

        return $images['default_small_image_url'];
    }

    /**
     * @param   ArrayObject<string, mixed>  $attr   The attributes
     */
    public static function editorialSmallImageAlt(ArrayObject $attr): string
    {
        return '<?= ' . self::class . '::smallImageAltHelper() ?>';
    }

    public static function smallImageAltHelper(): string
    {
        $images = self::decode('images');

        return $images['default_small_image_media_alt'] ?? '';
    }

    /**
     * @param   ArrayObject<string, mixed>  $attr   The attributes
     */
    public static function editorialUserColors(ArrayObject $attr): string
    {
        return '<?= ' . self::class . '::editorialUserColorsHelper() ?>';
    }

    public static function editorialUserColorsHelper(): string
    {
        $style           = self::decode('style');
        $main_color      = $style['main_color']      ?? '#EA1010';
        $main_dark_color = $style['main_dark_color'] ?? '#F37C7C';
        $mode            = $style['mode']            ?? 'auto';

        $schemes = [
            'auto'  => 'light dark',
            'light' => 'light',
            'dark'  => 'dark',
        ];

        $colors = '<style>' . "\n" .
        'html {' . "\n" .
        ' color-scheme: ' . $schemes[$mode] . ';' . "\n" .
        ' --main-color: light-dark(' . $main_color . ',' . $main_dark_color . ');' . "\n" .
        '}' . "\n" .
        '</style>' . "\n";

        return $colors;
    }

    /**
     * @param   ArrayObject<string, mixed>  $attr   The attributes
     */
    public static function editorialSocialLinks(ArrayObject $attr): string
    {
        return '<?= ' . self::class . '::editorialSocialLinksHelper() ?>';
    }

    public static function editorialSocialLinksHelper(): string
    {
        # Social media links
        $res = '';

        $stickers = self::decode('stickers');
        $stickers = array_filter($stickers, self::cleanSocialLinks(...));

        $count = 0;
        foreach ($stickers as $sticker) {
            $res .= self::setSocialLink($count, ($count == count($stickers)), $sticker['label'], $sticker['url'], $sticker['image']);
            $count++;
        }

        return $res;
    }

    protected static function setSocialLink(int $position, bool $last, string $label, string $url, string $image): string
    {
        $svgurl = My::path() . '/svg/' . $image;

        $svgData = @file_get_contents($svgurl);
        if ($svgData === false) {
            die("Failed to fetch SVG from: $svgurl");
        }

        $svg = simplexml_load_string($svgData);
        $svg->registerXPathNamespace('svg', 'http://www.w3.org/2000/svg');
        $paths = $svg->xpath('//svg:path');
        $d     = count($paths) ? $paths[0]['d'] : '';

        return
            '<li><a class="social-icon" title="' . $label . '" href="' . $url . '">' .
            '<span class="sr-only">' . $label . '</span>' .
            '<svg class="svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">' .
            '<path fill="currentColor" d="' . $d . '" />' .
            '</svg></a></li>' . "\n";
    }

    protected static function cleanSocialLinks(mixed $style): bool
    {
        if (is_array($style)) {
            if (isset($style['label']) && isset($style['url']) && isset($style['image'])) {
                if ($style['label'] && $style['url'] && $style['image']) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return  array<string, mixed>
     */
    public static function decode(string $setting): array
    {
        $res = App::blog()->settings()->get('themes')->get(App::blog()->settings()->get('system')->get('theme') . '_' . $setting);
        $res = $res ? (unserialize($res) ?: []) : [];

        if (!is_array($res)) {
            $res = [];
        }

        return $res;
    }

    /**
     * @param   ArrayObject<string, mixed>  $attr   The attributes
     */
    public static function templateBeforeBlock(string $block, ArrayObject $attr): string
    {
        if ($block === 'Entries' && isset($attr['featured_url']) && (bool) $attr['featured_url']) {
            return
            "<?php\n" .
            '$featured = ' . self::class . "::decode('featured') ?? ''" . ";\n" .
            "\$url = urldecode(\$featured['featured_post_url'] ?? '');\n" .
            "if (!isset(\$params)) { \$params['post_type'] = ['post', 'page', 'related']; }\n" .
            "if (!isset(\$params['sql'])) { \$params['sql'] = ''; }\n" .
            "\$params['sql'] .= \"AND P.post_url = '\$url' \";\n" .
                "?>\n";
        } elseif ($block == 'Entries' && isset($attr['featured_url']) && $attr['featured_url'] == 0) {
            return
            "<?php\n" .
            '$featured = ' . self::class . "::decode('featured') ?? ''" . ";\n" .
            "\$url = urldecode(\$featured['featured_post_url'] ?? '');\n" .
            "if (!isset(\$params)) { \$params = []; }\n" .
            "if (!isset(\$params['sql'])) { \$params['sql'] = ''; }\n" .
            "\$params['sql'] .= \"AND P.post_url != '\$url' \";\n" .
                "?>\n";
        }

        return '';
    }
}
