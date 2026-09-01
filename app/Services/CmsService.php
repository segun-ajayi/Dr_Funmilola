<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\CmsPage;
use App\Models\CmsVersion;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CmsService
{
    public function __construct(private readonly CmsMediaService $media) {}

    public const TYPES = ['hero', 'text', 'image', 'image_text', 'cards', 'services', 'cta', 'publications', 'career_timeline', 'achievements', 'faq', 'gallery', 'stats', 'contact', 'appointment', 'video', 'divider', 'spacer'];

    private const CONTENT_KEYS = [
        'hero' => ['eyebrow', 'eyebrow_marks', 'heading', 'heading_marks', 'text', 'text_marks', 'primary_label', 'primary_url', 'primary_action', 'primary_style', 'primary_icon', 'primary_icon_position', 'primary_target', 'primary_visibility', 'secondary_label', 'secondary_url', 'secondary_action', 'secondary_style', 'secondary_icon', 'secondary_icon_position', 'secondary_target', 'secondary_visibility'],
        'text' => ['eyebrow', 'eyebrow_marks', 'heading', 'heading_marks', 'body', 'body_marks'],
        'image' => ['heading', 'heading_marks', 'image_url', 'image_media_id', 'image_alt', 'image_is_decorative', 'caption', 'caption_marks', 'image_link'],
        'image_text' => ['eyebrow', 'eyebrow_marks', 'heading', 'heading_marks', 'text', 'text_marks', 'image_url', 'image_media_id', 'image_alt', 'image_is_decorative', 'image_link'],
        'cards' => ['eyebrow', 'eyebrow_marks', 'heading', 'heading_marks', 'text', 'text_marks', 'items'],
        'services' => ['eyebrow', 'eyebrow_marks', 'heading', 'heading_marks', 'text', 'text_marks', 'items'],
        'cta' => ['eyebrow', 'eyebrow_marks', 'heading', 'heading_marks', 'text', 'text_marks', 'button_label', 'button_url', 'button_action', 'button_style', 'button_icon', 'button_icon_position', 'button_target', 'button_visibility'],
        'publications' => ['eyebrow', 'eyebrow_marks', 'heading', 'heading_marks', 'text', 'text_marks', 'items'],
        'career_timeline' => ['eyebrow', 'eyebrow_marks', 'heading', 'heading_marks', 'text', 'text_marks', 'items'],
        'achievements' => ['eyebrow', 'eyebrow_marks', 'heading', 'heading_marks', 'text', 'text_marks', 'items'],
        'faq' => ['eyebrow', 'eyebrow_marks', 'heading', 'heading_marks', 'text', 'text_marks', 'items'],
        'gallery' => ['eyebrow', 'eyebrow_marks', 'heading', 'heading_marks', 'text', 'text_marks', 'items'],
        'stats' => ['eyebrow', 'eyebrow_marks', 'heading', 'heading_marks', 'text', 'text_marks', 'items'],
        'contact' => ['eyebrow', 'eyebrow_marks', 'heading', 'heading_marks', 'text', 'text_marks', 'email', 'telephone', 'address'],
        'appointment' => ['eyebrow', 'eyebrow_marks', 'heading', 'heading_marks', 'text', 'text_marks', 'button_label', 'button_url', 'button_action', 'button_style', 'button_icon', 'button_icon_position', 'button_target', 'button_visibility'],
        'video' => ['eyebrow', 'eyebrow_marks', 'heading', 'heading_marks', 'text', 'text_marks', 'video_url', 'poster_url', 'caption', 'caption_marks'],
        'divider' => ['heading', 'label', 'label_marks'],
        'spacer' => ['heading'],
    ];

    private const ITEM_KEYS = [
        'cards' => ['key', 'heading', 'text', 'url', 'is_visible'],
        'services' => ['key', 'heading', 'text', 'url', 'is_visible'],
        'publications' => ['key', 'title', 'meta', 'url', 'is_visible'],
        'career_timeline' => ['key', 'year', 'heading', 'text', 'is_visible'],
        'achievements' => ['key', 'value', 'heading', 'text', 'is_visible'],
        'faq' => ['key', 'question', 'answer', 'is_visible'],
        'gallery' => ['key', 'image_url', 'image_media_id', 'image_alt', 'image_is_decorative', 'caption', 'is_visible'],
        'stats' => ['key', 'value', 'label', 'is_visible'],
    ];

    private const ITEM_REQUIRED = [
        'cards' => ['heading', 'text'], 'services' => ['heading', 'text'], 'publications' => ['title', 'meta'], 'career_timeline' => ['year', 'heading'], 'achievements' => ['value', 'heading'], 'faq' => ['question', 'answer'], 'gallery' => [], 'stats' => ['value', 'label'],
    ];

    public function validateContent(string $type, array $content): array
    {
        if (! in_array($type, self::TYPES, true)) {
            throw ValidationException::withMessages(['type' => 'Choose a supported section type.']);
        }$unknown = array_diff(array_keys($content), self::CONTENT_KEYS[$type]);
        if ($unknown) {
            throw ValidationException::withMessages(['content' => 'This section contains unsupported fields.']);
        }
        foreach ($content as $key => $value) {
            if ($key === 'items') {
                $content[$key] = $this->items($type, $value);

                continue;
            }if (str_ends_with($key, '_marks')) {
                $this->marks($key, $value, $content);

                continue;
            }if ($key === 'image_is_decorative') {
                if (! is_bool($value)) {
                    throw ValidationException::withMessages(["content.{$key}" => 'Decorative state must be true or false.']);
                }

                continue;
            }if ($key === 'image_media_id') {
                if (! is_string($value)) {
                    throw ValidationException::withMessages(["content.{$key}" => 'Choose a media-library asset.']);
                }if ($value !== '') {
                    $this->media->assertUsableReference($value, "content.{$key}");
                }

                continue;
            }if (! is_string($value)) {
                throw ValidationException::withMessages(["content.{$key}" => 'Content must be text.']);
            }if (str_ends_with($key, '_url') || $key === 'image_link') {
                $this->safeUrl($value);
            } elseif ($key === 'email' && $value !== '' && ! filter_var($value, FILTER_VALIDATE_EMAIL)) {
                throw ValidationException::withMessages(['content.email' => 'Enter a valid email address.']);
            } elseif ($key === 'telephone' && $value !== '' && ! preg_match('/^\+?[0-9 ()-]{7,30}$/', $value)) {
                throw ValidationException::withMessages(['content.telephone' => 'Enter a valid telephone number.']);
            } elseif (str_ends_with($key, '_action') && ! in_array($value, ['internal', 'external', 'email', 'telephone'], true)) {
                throw ValidationException::withMessages(["content.{$key}" => 'Choose a supported action type.']);
            } elseif (str_ends_with($key, '_style') && ! in_array($value, ['primary', 'outline', 'light'], true)) {
                throw ValidationException::withMessages(["content.{$key}" => 'Choose a supported button style.']);
            } elseif (str_ends_with($key, '_icon') && ! in_array($value, ['none', 'arrow', 'calendar', 'external', 'mail', 'phone', 'heart'], true)) {
                throw ValidationException::withMessages(["content.{$key}" => 'Choose a supported button icon.']);
            } elseif (str_ends_with($key, '_icon_position') && ! in_array($value, ['left', 'right'], true)) {
                throw ValidationException::withMessages(["content.{$key}" => 'Choose a supported icon position.']);
            } elseif (str_ends_with($key, '_target') && ! in_array($value, ['_self', '_blank'], true)) {
                throw ValidationException::withMessages(["content.{$key}" => 'Choose a safe target.']);
            } elseif (str_ends_with($key, '_visibility') && ! in_array($value, ['show', 'hide'], true)) {
                throw ValidationException::withMessages(["content.{$key}" => 'Choose whether the button is shown.']);
            } else {
                $this->plain($value, $key === 'body' || $key === 'text' ? 4000 : 500);
            }
        }foreach (['primary', 'secondary', 'button'] as $prefix) {
            if (isset($content["{$prefix}_action"])) {
                $this->actionUrl($content["{$prefix}_action"], $content["{$prefix}_url"] ?? '');
            }
        }
        if (in_array($type, ['image', 'image_text'], true)) {
            $this->validateImageAccessibility($content, 'content');
        }
        if (empty($content['heading'])) {
            throw ValidationException::withMessages(['content.heading' => 'A heading is required.']);
        }

        return $content;
    }

    public function validatePresentation(array $data): array
    {
        $allowed = ['background' => ['ivory', 'white', 'wine', 'blush'], 'background_gradient' => ['none', 'rose_glow', 'wine_blush', 'ivory_light'], 'background_pattern' => ['none', 'dots', 'grid', 'waves'], 'contour' => ['none', 'subtle', 'strong'], 'overlay_color' => ['none', 'wine', 'black', 'white'], 'overlay_opacity' => ['0', '10', '20', '30', '40', '50', '60'], 'alignment' => ['left', 'center'], 'width' => ['normal', 'narrow', 'wide', 'full'], 'spacing' => ['none', 'compact', 'normal', 'generous'], 'margin' => ['none', 'small', 'normal', 'large'], 'min_height' => ['auto', 'short', 'medium', 'tall', 'screen'], 'max_height' => ['none', 'short', 'medium', 'tall'], 'layout' => ['block', 'flex', 'grid'], 'columns' => ['1', '2', '3', '4'], 'content_alignment' => ['start', 'center', 'end', 'stretch'], 'gap' => ['none', 'small', 'normal', 'large'], 'border_style' => ['none', 'solid', 'dashed'], 'border_width' => ['0', '1', '2', '3'], 'border_color' => ['transparent', 'wine', 'rose', 'line', 'white'], 'radius' => ['none', 'soft', 'round'], 'shadow' => ['none', 'soft', 'strong'], 'font_family' => ['site', 'editorial', 'modern'], 'font_size' => ['small', 'normal', 'large'], 'font_weight' => ['regular', 'medium', 'bold'], 'emphasis' => ['none', 'italic', 'underline', 'italic_underline'], 'text_color' => ['default', 'wine', 'muted', 'white'], 'line_height' => ['snug', 'normal', 'relaxed'], 'image_width' => ['small', 'medium', 'large', 'full'], 'image_height' => ['auto', 'small', 'medium', 'large'], 'image_alignment' => ['left', 'center', 'right'], 'image_radius' => ['none', 'soft', 'round', 'pill'], 'image_fit' => ['cover', 'contain', 'fill'], 'crop_position' => ['top_left', 'top', 'top_right', 'left', 'center', 'right', 'bottom_left', 'bottom', 'bottom_right'], 'image_overlay_color' => ['none', 'wine', 'black', 'white'], 'image_overlay_opacity' => ['0', '10', '20', '30', '40', '50', '60'], 'image_opacity' => ['20', '40', '60', '80', '100']];
        if (array_diff(array_keys($data), [...array_keys($allowed), 'background_image', 'background_media_id', 'responsive', 'text_styles', 'button_styles'])) {
            throw ValidationException::withMessages(['presentation' => 'Unsupported presentation setting.']);
        }
        foreach ($data as $key => $value) {
            if ($key === 'text_styles') {
                $data[$key] = $this->textStyles($value);

                continue;
            }if ($key === 'button_styles') {
                $data[$key] = $this->buttonStyles($value);

                continue;
            }if ($key === 'responsive') {
                $data[$key] = $this->responsivePresentation($value, $allowed);

                continue;
            }if ($key === 'background_image') {
                if (! is_string($value)) {
                    throw ValidationException::withMessages(['presentation.background_image' => 'Choose a safe image URL.']);
                }$this->safeUrl($value);

                continue;
            }if ($key === 'background_media_id') {
                if (! is_string($value)) {
                    throw ValidationException::withMessages(['presentation.background_media_id' => 'Choose a media-library asset.']);
                }if ($value !== '') {
                    $this->media->assertUsableReference($value, 'presentation.background_media_id');
                }

                continue;
            }if (! is_string($value) || ! in_array($value, $allowed[$key], true)) {
                throw ValidationException::withMessages(["presentation.{$key}" => 'Choose a supported option.']);
            }
        }

        return $data;
    }

    public function snapshot(CmsPage $page): array
    {
        return ['schema_version' => 3, 'title' => $page->title, 'slug' => $page->slug, 'template' => $page->template, 'seo' => ['title' => $page->seo_title, 'description' => $page->seo_description], 'sections' => $page->sections()->get()->map(fn ($s) => $s->only(['id', 'section_key', 'type', 'sort_order', 'is_visible', 'content', 'presentation']))->all()];
    }

    public function validateDocumentSections(CmsPage $page, array $sections): array
    {
        if (count($sections) > 100) {
            throw ValidationException::withMessages(['sections' => 'A page may contain no more than 100 sections.']);
        }
        $ids = [];
        $keys = [];
        $known = $page->sections()->pluck('id')->map(fn ($id) => (int) $id)->all();
        $validated = [];
        foreach (array_values($sections) as $order => $section) {
            if (! is_array($section) || array_diff(array_keys($section), ['id', 'section_key', 'type', 'sort_order', 'is_visible', 'content', 'presentation'])) {
                throw ValidationException::withMessages(["sections.{$order}" => 'This section has an invalid structured schema.']);
            }
            $id = isset($section['id']) ? (int) $section['id'] : null;
            if ($id && (! in_array($id, $known, true) || in_array($id, $ids, true))) {
                throw ValidationException::withMessages(["sections.{$order}.id" => 'This section does not belong to the page or appears more than once.']);
            }if ($id) {
                $ids[] = $id;
            }
            $key = (string) ($section['section_key'] ?? '');
            if ($key === '' && ! $id) {
                $key = (string) Str::uuid();
            }if (! Str::isUuid($key) || in_array($key, $keys, true)) {
                throw ValidationException::withMessages(["sections.{$order}.section_key" => 'Each section needs one unique structured identifier.']);
            }$keys[] = $key;
            $type = (string) ($section['type'] ?? '');
            $content = $section['content'] ?? null;
            $presentation = $section['presentation'] ?? [];
            if (! is_array($content) || ! is_array($presentation) || ! is_bool($section['is_visible'] ?? null)) {
                throw ValidationException::withMessages(["sections.{$order}" => 'Section content, presentation and visibility are required.']);
            }
            $validated[] = ['id' => $id, 'section_key' => $key, 'type' => $type, 'sort_order' => $order, 'is_visible' => $section['is_visible'], 'content' => $this->validateContent($type, $content), 'presentation' => $this->validatePresentation($presentation)];
        }

        return $validated;
    }

    public function applyDocumentSections(CmsPage $page, array $sections): void
    {
        $kept = [];
        foreach ($sections as $section) {
            $id = $section['id'];
            $attributes = Arr::except($section, 'id');
            if ($id) {
                $page->sections()->whereKey($id)->update($attributes);
                $kept[] = $id;
            } elseif ($existing = $page->sections()->where('section_key', $section['section_key'])->first()) {
                $existing->update($attributes);
                $kept[] = $existing->id;
            } else {
                $kept[] = $page->sections()->create($attributes)->id;
            }
        }
        $page->sections()->whereNotIn('id', $kept)->delete();
    }

    public function version(CmsPage $page, User $actor, string $reason): CmsVersion
    {
        $next = ((int) $page->versions()->max('version')) + 1;
        $version = $page->versions()->create(['version' => $next, 'reason' => $reason, 'snapshot' => $this->snapshot($page), 'created_by' => $actor->id]);
        $this->audit($actor, 'cms.version_created', $page, ['version' => $next, 'reason' => $reason]);

        return $version;
    }

    public function audit(User $actor, string $action, CmsPage $page, array $metadata = []): void
    {
        AuditLog::create(['actor_id' => $actor->id, 'action' => $action, 'subject_type' => CmsPage::class, 'subject_id' => $page->id, 'metadata' => $metadata]);
    }

    private function items(string $type, mixed $items): array
    {
        if (! isset(self::ITEM_KEYS[$type]) || ! is_array($items) || count($items) > 12) {
            throw ValidationException::withMessages(['content.items' => 'This component must contain no more than twelve structured items.']);
        }
        foreach ($items as $index => $item) {
            if (! is_array($item) || array_diff(array_keys($item), self::ITEM_KEYS[$type])) {
                throw ValidationException::withMessages(["content.items.{$index}" => 'This item contains unsupported fields.']);
            }foreach (self::ITEM_REQUIRED[$type] as $required) {
                if (! isset($item[$required]) || $item[$required] === '') {
                    throw ValidationException::withMessages(["content.items.{$index}.{$required}" => 'This item field is required.']);
                }
            }foreach ($item as $key => $value) {
                if ($key === 'is_visible' || $key === 'image_is_decorative') {
                    if (! is_bool($value)) {
                        throw ValidationException::withMessages(["content.items.{$index}.{$key}" => 'This item state must be true or false.']);
                    }

                    continue;
                }if ($key === 'image_media_id') {
                    if (! is_string($value)) {
                        throw ValidationException::withMessages(["content.items.{$index}.{$key}" => 'Choose a media-library asset.']);
                    }if ($value !== '') {
                        $this->media->assertUsableReference($value, "content.items.{$index}.{$key}");
                    }

                    continue;
                }if (! is_string($value)) {
                    throw ValidationException::withMessages(["content.items.{$index}.{$key}" => 'Item content must be text.']);
                }if ($key === 'key') {
                    if (! Str::isUuid($value)) {
                        throw ValidationException::withMessages(["content.items.{$index}.key" => 'Each item key must be a unique identifier.']);
                    }

                    continue;
                }if ($key === 'url' || str_ends_with($key, '_url')) {
                    $this->safeUrl($value);
                } else {
                    $this->plain($value, in_array($key, ['text', 'answer'], true) ? 4000 : 500);
                }
            }if ($type === 'gallery') {
                $this->validateImageAccessibility($item, "content.items.{$index}");
            }
        }
        $keys = array_values(array_filter(array_column($items, 'key')));
        if (count($keys) !== count(array_unique($keys))) {
            throw ValidationException::withMessages(['content.items' => 'Each structured item key must be unique.']);
        }

        return $items;
    }

    private function plain(string $value, int $max): void
    {
        if (mb_strlen($value) > $max || $value !== strip_tags($value)) {
            throw ValidationException::withMessages(['content' => 'Content must be plain text within the allowed length.']);
        }
    }

    private function safeUrl(string $value): void
    {
        if ($value === '') {
            return;
        }if (str_starts_with($value, '/') && preg_match('/^\/[A-Za-z0-9\/_~.?&=%+#-]*$/', $value)) {
            return;
        }if (str_starts_with($value, 'mailto:') && filter_var(substr($value, 7), FILTER_VALIDATE_EMAIL)) {
            return;
        }if (str_starts_with($value, 'tel:') && preg_match('/^tel:\+?[0-9 ()-]{7,30}$/', $value)) {
            return;
        }if (! filter_var($value, FILTER_VALIDATE_URL) || ! in_array(parse_url($value, PHP_URL_SCHEME), ['https', 'http'], true)) {
            throw ValidationException::withMessages(['content' => 'Links must be an internal path, email, telephone, or safe HTTP(S) URL.']);
        }
    }

    private function actionUrl(string $action, string $url): void
    {
        $valid = $action === 'internal' && str_starts_with($url, '/') || $action === 'external' && filter_var($url, FILTER_VALIDATE_URL) && in_array(parse_url($url, PHP_URL_SCHEME), ['https', 'http'], true) || $action === 'email' && str_starts_with($url, 'mailto:') && filter_var(substr($url, 7), FILTER_VALIDATE_EMAIL) || $action === 'telephone' && preg_match('/^tel:\+?[0-9 ()-]{7,30}$/', $url);
        if (! $valid) {
            throw ValidationException::withMessages(['content' => 'The selected action and destination do not match.']);
        }
    }

    private function marks(string $key, mixed $marks, array $content): void
    {
        $field = substr($key, 0, -6);
        $length = mb_strlen((string) ($content[$field] ?? ''));
        if (! is_array($marks) || count($marks) > 30) {
            throw ValidationException::withMessages(["content.{$key}" => 'Text formatting must be a short list of safe ranges.']);
        }$lastEnd = 0;
        foreach ($marks as $mark) {
            if (! is_array($mark) || array_diff(array_keys($mark), ['type', 'start', 'end', 'url', 'target', 'action']) || ! in_array($mark['type'] ?? null, ['bold', 'italic', 'underline', 'link'], true) || ! is_int($mark['start'] ?? null) || ! is_int($mark['end'] ?? null) || $mark['start'] < $lastEnd || $mark['start'] < 0 || $mark['end'] <= $mark['start'] || $mark['end'] > $length) {
                throw ValidationException::withMessages(["content.{$key}" => 'Text formatting ranges must be ordered, non-overlapping and inside the text.']);
            }if ($mark['type'] === 'link') {
                if (empty($mark['url'])) {
                    throw ValidationException::withMessages(["content.{$key}" => 'A link formatting range needs a safe URL.']);
                }$this->safeUrl((string) $mark['url']);
                if (isset($mark['target']) && ! in_array($mark['target'], ['_self', '_blank'], true)) {
                    throw ValidationException::withMessages(["content.{$key}" => 'Choose a safe link target.']);
                }if (isset($mark['action'])) {
                    if (! in_array($mark['action'], ['internal', 'external', 'email', 'telephone'], true)) {
                        throw ValidationException::withMessages(["content.{$key}" => 'Choose a supported link action.']);
                    }$this->actionUrl($mark['action'], $mark['url']);
                }
            } elseif (isset($mark['url']) || isset($mark['target']) || isset($mark['action'])) {
                throw ValidationException::withMessages(["content.{$key}" => 'Only link formatting accepts a URL, target and action.']);
            }$lastEnd = $mark['end'];
        }
    }

    private function textStyles(mixed $styles): array
    {
        if (! is_array($styles) || count($styles) > 30) {
            throw ValidationException::withMessages(['presentation.text_styles' => 'Text styles must be a short structured map.']);
        }$fields = ['eyebrow', 'heading', 'text', 'body', 'label', 'caption', 'primary_label', 'secondary_label', 'button_label'];
        $allowed = ['font_family' => ['site', 'editorial', 'modern'], 'font_size' => ['xs', 'sm', 'base', 'lg', 'xl', '2xl', '3xl', '4xl', '5xl'], 'font_weight' => ['400', '500', '600', '700', '800'], 'bold' => [true, false], 'italic' => [true, false], 'underline' => [true, false], 'color' => ['default', 'wine', 'muted', 'white', 'black'], 'alignment' => ['left', 'center', 'right', 'justify'], 'line_height' => ['1', '1.2', '1.4', '1.6', '1.8', '2'], 'letter_spacing' => ['-0.04em', '-0.02em', '0', '0.02em', '0.05em', '0.1em'], 'text_decoration' => ['none', 'underline', 'line-through', 'overline']];
        foreach ($styles as $field => $style) {
            if (! in_array($field, $fields, true) || ! is_array($style) || array_diff(array_keys($style), array_keys($allowed))) {
                throw ValidationException::withMessages(["presentation.text_styles.{$field}" => 'This text element contains unsupported styles.']);
            }foreach ($style as $key => $value) {
                if (! in_array($value, $allowed[$key], true)) {
                    throw ValidationException::withMessages(["presentation.text_styles.{$field}.{$key}" => 'Choose an approved text style value.']);
                }
            }
        }

        return $styles;
    }

    private function buttonStyles(mixed $styles): array
    {
        if (! is_array($styles) || count($styles) > 3) {
            throw ValidationException::withMessages(['presentation.button_styles' => 'Button styles must be a short structured map.']);
        }$fields = ['primary', 'secondary', 'button'];
        $allowed = ['alignment' => ['left', 'center', 'right'], 'size' => ['small', 'normal', 'large'], 'font_family' => ['site', 'editorial', 'modern'], 'font_size' => ['sm', 'base', 'lg', 'xl', '2xl'], 'font_weight' => ['400', '500', '600', '700', '800'], 'background_color' => ['primary', 'wine', 'white', 'transparent', 'blush', 'black'], 'text_color' => ['white', 'wine', 'black'], 'border_style' => ['none', 'solid', 'dashed'], 'border_width' => ['0', '1', '2', '3'], 'border_color' => ['transparent', 'wine', 'white', 'black', 'rose'], 'border_radius' => ['0', '6', '12', '24', '999'], 'padding_x' => ['8', '12', '16', '20', '24', '32'], 'padding_y' => ['6', '8', '10', '12', '16', '20'], 'margin' => ['0', '4', '8', '12', '16', '24']];
        foreach ($styles as $field => $style) {
            if (! in_array($field, $fields, true) || ! is_array($style) || array_diff(array_keys($style), array_keys($allowed))) {
                throw ValidationException::withMessages(["presentation.button_styles.{$field}" => 'This button contains unsupported styles.']);
            }foreach ($style as $key => $value) {
                if (! in_array($value, $allowed[$key], true)) {
                    throw ValidationException::withMessages(["presentation.button_styles.{$field}.{$key}" => 'Choose an approved button style value.']);
                }
            }
        }

        return $styles;
    }

    private function responsivePresentation(mixed $responsive, array $allowed): array
    {
        if (! is_array($responsive) || array_diff(array_keys($responsive), ['desktop', 'tablet', 'mobile'])) {
            throw ValidationException::withMessages(['presentation.responsive' => 'Responsive presentation supports desktop, tablet and mobile only.']);
        }foreach ($responsive as $scope => $values) {
            if (! is_array($values) || array_diff(array_keys($values), [...array_keys($allowed), 'background_image', 'background_media_id'])) {
                throw ValidationException::withMessages(["presentation.responsive.{$scope}" => 'This viewport contains unsupported presentation settings.']);
            }foreach ($values as $key => $value) {
                if (! is_string($value)) {
                    throw ValidationException::withMessages(["presentation.responsive.{$scope}.{$key}" => 'Choose a supported option.']);
                }if ($key === 'background_image') {
                    $this->safeUrl($value);
                } elseif ($key === 'background_media_id') {
                    if ($value !== '') {
                        $this->media->assertUsableReference($value, "presentation.responsive.{$scope}.background_media_id");
                    }
                } elseif (! in_array($value, $allowed[$key], true)) {
                    throw ValidationException::withMessages(["presentation.responsive.{$scope}.{$key}" => 'Choose a supported option.']);
                }
            }
        }

        return $responsive;
    }

    private function validateImageAccessibility(array $content, string $field): void
    {
        $hasImage = ($content['image_media_id'] ?? '') !== '' || ($content['image_url'] ?? '') !== '';
        if (! $hasImage) {
            return;
        }$decorative = $content['image_is_decorative'] ?? false;
        $alt = trim((string) ($content['image_alt'] ?? ''));
        if (! is_bool($decorative)) {
            throw ValidationException::withMessages(["{$field}.image_is_decorative" => 'Decorative state must be true or false.']);
        }if ($decorative && $alt !== '') {
            throw ValidationException::withMessages(["{$field}.image_alt" => 'Decorative images must use empty alternative text.']);
        }if (! $decorative && $alt === '') {
            throw ValidationException::withMessages(["{$field}.image_alt" => 'Add alternative text or explicitly mark the image as decorative.']);
        }
    }
}
