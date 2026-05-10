<?php

namespace App\Core;

use RuntimeException;

class View
{
    protected string $basePath;

    protected ?array $translations = null;

    public function __construct(?string $basePath = null)
    {
        $this->basePath = $basePath ?? ROOT_PATH . '/app/Views';
    }

    public function render(string $template, array $data = []): string
    {
        $templatePath = $this->resolvePath($template);

        if (!is_file($templatePath)) {
            throw new RuntimeException('View not found: ' . $template);
        }

        $layout = null;
        $templateVars = [];
        $content = $this->evaluate($templatePath, $data, $layout, $templateVars);

        if (is_string($layout) && $layout !== '') {
            $layoutPath = $this->resolvePath($layout);

            if (!is_file($layoutPath)) {
                throw new RuntimeException('Layout not found: ' . $layout);
            }

            $layoutData = array_merge($data, $templateVars, ['content' => $content]);
            $unusedLayout = null;
            $content = $this->evaluate($layoutPath, $layoutData, $unusedLayout);
        }

        return $content;
    }

    public function escape(string|null $string): string
    {
        return htmlspecialchars((string) $string, ENT_QUOTES, 'UTF-8');
    }

    public function t(string $key, ?string $locale = null, array $params = []): string
    {
        $locale = in_array($locale, ['zh', 'en', 'pt'], true) ? $locale : 'zh';
        $translations = $this->translations();
        $value = $this->resolveTranslation($translations[$locale] ?? [], $key);

        if (!is_string($value)) {
            $value = $this->resolveTranslation($translations['zh'] ?? [], $key);
        }

        if (!is_string($value)) {
            $value = $key;
        }

        foreach ($params as $name => $replacement) {
            $value = str_replace(':' . $name, (string) $replacement, $value);
        }

        return $value;
    }

    protected function resolvePath(string $template): string
    {
        return rtrim($this->basePath, '/\\') . '/' . str_replace(['.', '\\'], '/', $template) . '.php';
    }

    protected function evaluate(string $path, array $data, ?string &$layout, ?array &$exported = null): string
    {
        $locale = in_array($data['locale'] ?? null, ['zh', 'en', 'pt'], true) ? $data['locale'] : 'zh';
        $data['t'] ??= fn (string $key, array $params = []): string => $this->t($key, $locale, $params);

        extract($data, EXTR_SKIP);

        ob_start();
        include $path;
        $exported = get_defined_vars();
        unset($exported['path'], $exported['data'], $exported['layout'], $exported['exported']);

        return (string) ob_get_clean();
    }

    protected function translations(): array
    {
        if ($this->translations !== null) {
            return $this->translations;
        }

        $path = ROOT_PATH . '/config/i18n.php';
        $translations = is_file($path) ? require $path : [];
        $this->translations = is_array($translations) ? $translations : [];

        return $this->translations;
    }

    protected function resolveTranslation(array $source, string $key): mixed
    {
        return array_reduce(
            explode('.', $key),
            static fn (mixed $value, string $segment): mixed => is_array($value) && array_key_exists($segment, $value) ? $value[$segment] : null,
            $source
        );
    }
}
