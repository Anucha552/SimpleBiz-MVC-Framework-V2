<?php

namespace App\Core;

use App\Core\Cache;
use App\Core\Config;
use App\Core\Logger;

class View
{
    private Logger $logger;
    private string $view;
    private array $data;
    private ?string $layout = null;

    private array $sections = [];
    private ?string $currentSection = null;

    private array $slots = [];
    private ?string $currentSlot = null;

    private static array $shared = [];
    private static array $composers = [];

    private static string $basePath = '';
    private static bool $debug = true;

    private ?int $cacheTtl = null;

    private const MAX_LAYOUT_DEPTH = 10;

    private array $usedLayoutFiles = [];

    public function __construct(string $view, array $data = [])
    {
        self::setDebug(Config::get('app.debug') === true);

        $this->logger = new Logger();

        if (self::$basePath === '') {
            $path = realpath(__DIR__ . '/../Views');
            if ($path === false) {
                throw new \RuntimeException('Invalid view base path.');
            }
            self::$basePath = $path;
        }

        $this->view = self::normalizeTemplateName($view);
        $this->data = $data;
    }

    public static function setBasePath(string $path): void
    {
        $real = realpath($path);
        if ($real === false) {
            throw new \InvalidArgumentException('Invalid base path.');
        }

        self::$basePath = rtrim($real, '/');
    }

    /**
     * เปิดโหมด debug เพื่อแสดงข้อผิดพลาดที่ชัดเจนขึ้นในระหว่างการพัฒนา
     */
    public static function setDebug(bool $debug): void
    {
        self::$debug = $debug;
    }

    public static function isDebug(): bool
    {
        return self::$debug;
    }

    public function layout(string $layout): self
    {
        $layout = str_replace('\\', '/', trim($layout));
        $layout = preg_replace('#^layouts/#', '', $layout);
        $layout = preg_replace('#\.php$#', '', $layout);

        $this->layout = self::normalizeTemplateName($layout);
        return $this;
    }

    public function cache(int $seconds): self
    {
        $this->cacheTtl = $seconds > 0 ? $seconds : null;
        return $this;
    }

    /* ============================== */

    public function section(string $name): void
    {
        $this->currentSection = $name;
        ob_start();
    }

    public function endSection(): void
    {
        if ($this->currentSection !== null) {
            $this->sections[$this->currentSection] = ob_get_clean();
            $this->currentSection = null;
        }
    }

    public function yield(string $name, string $default = ''): string
    {
        return $this->sections[$name] ?? $default;
    }

    /* ============================== */

    public function slot(string $name): void
    {
        $this->currentSlot = $name;
        ob_start();
    }

    public function endSlot(): void
    {
        if ($this->currentSlot !== null) {
            $this->slots[$this->currentSlot] = ob_get_clean();
            $this->currentSlot = null;
        }
    }

    public function renderSlot(string $name, string $default = ''): string
    {
        return $this->slots[$name] ?? $default;
    }

    /* ============================== */

    public function partial(string $view, array $data = []): void
    {
        echo $this->component($view, $data);
    }

    public function component(string $view, array $data = []): string
    {
        if (self::$debug) {
            $this->logger->info('view.component.render', [
                'component' => $view
            ]);
        }

        $view = self::normalizeTemplateName($view);
        $file = self::$basePath . '/' . $view . '.php';

        if (!is_file($file)) {
            $this->handleError("Component not found: {$view}");
        }

        $data = $this->mergeData($data);
        $data = $this->applyComposers($view, $data);

        return $this->renderFile($file, $data);
    }

    /* ============================== */

    public static function share(string $key, mixed $value): void
    {
        self::$shared[$key] = $value;
    }

    public static function composer(string $view, callable $callback): void
    {
        self::$composers[$view][] = $callback;
    }

    private function applyComposers(string $view, array $data): array
    {
        foreach (self::$composers as $pattern => $callbacks) {
            if ($this->matchPattern($pattern, $view)) {
                if (self::$debug) {
                    $this->logger->info('view.composer.apply', [
                        'view'    => $view,
                        'pattern' => $pattern
                    ]);
                }

                foreach ($callbacks as $callback) {
                    $extra = $callback($this, $data);
                    if (is_array($extra)) {
                        $data = array_merge($data, $extra);
                    }
                }
            }
        }
        return $data;
    }

    private function matchPattern(string $pattern, string $view): bool
    {
        if ($pattern === $view) {
            return true;
        }

        if (str_contains($pattern, '*')) {
            $escaped = preg_quote($pattern, '#');
            $regex = '#^' . str_replace('\*', '.*', $escaped) . '$#';
            return (bool) preg_match($regex, $view);
        }

        return false;
    }

    private function mergeData(array $extra = []): array
    {
        return array_merge(self::$shared, $this->data, $extra);
    }

    /* ============================== */

    public function render(): string
    {
        $start = microtime(true);

        $this->logger->info('view.render.start', ['view' => $this->view]);

        try {
            $data = $this->mergeData();
            $data = $this->applyComposers($this->view, $data);

            $viewFile = self::$basePath . '/' . $this->view . '.php';
            $viewTime = is_file($viewFile) ? filemtime($viewFile) : 0;

            $html = $this->renderViewFile($this->view, $data);
            $html = $this->renderLayoutChain($html, $data);

            if ($this->cacheTtl !== null && self::$debug === false) {

                $layoutTimes = '';
                foreach ($this->usedLayoutFiles as $file) {
                    $layoutTimes .= is_file($file) ? filemtime($file) : '';
                }

                $dataHash = md5(json_encode($data, JSON_PARTIAL_OUTPUT_ON_ERROR));

                $cacheKey = 'view_' . md5(
                    $this->view .
                    $dataHash .
                    $viewTime .
                    $layoutTimes
                );

                if (Cache::has($cacheKey)) {
                    if (static::$debug) {
                            $this->logger->info('view.cache.hit', [
                            'view' => $this->view,
                            'key'  => $cacheKey
                        ]);
                    }

                    $this->resetState();
                    return Cache::get($cacheKey);
                }

                if (static::$debug) {
                    $this->logger->info('view.cache.miss', [
                        'view' => $this->view,
                        'key'  => $cacheKey
                    ]);
                }

                Cache::set($cacheKey, $html, $this->cacheTtl);
            }

            $duration = microtime(true) - $start;
            $this->logger->info('view.render.complete', ['view' => $this->view, 'duration' => $duration, 'layouts' => count($this->usedLayoutFiles)]);
            $this->resetState();

            $threshold = (float) Config::get('logging.view_slow_threshold', 0.5);
            if ($duration > $threshold) {
                $this->logger->warning('view.render.slow', ['view' => $this->view, 'duration' => $duration]);
            }

            return $html;
        } catch (\Throwable $e) {
            $duration = microtime(true) - $start;
            $this->logger->error('view.render.exception', ['view' => $this->view, 'error' => $e->getMessage(), 'duration' => $duration]);
            $this->resetState();
            throw $e;
        }
    }

    private function renderLayoutChain(string $content, array $data): string
    {
        $depth = 0;

        while ($this->layout !== null) {

            if (++$depth > self::MAX_LAYOUT_DEPTH) {
                $this->logger->error('view.layout.too_deep', ['view' => $this->view, 'layout' => $this->layout, 'depth' => $depth]);
                throw new \RuntimeException('Layout nesting too deep.');
            }

            $currentLayout = $this->layout;
            $this->layout = null;

            $layoutFile = self::$basePath . '/layouts/' . $currentLayout . '.php';
            $this->usedLayoutFiles[] = $layoutFile;

            // Don't overwrite an existing `content` section defined in the view
            if (!array_key_exists('content', $this->sections) || $this->sections['content'] === '') {
                $this->sections['content'] = $content;
            }

            $data = $this->applyComposers('layouts/' . $currentLayout, $data);

            if (static::$debug) {
                $this->logger->info('view.layout.apply', [
                    'view'   => $this->view,
                    'layout' => $currentLayout,
                    'depth'  => $depth
                ]);
            }

            $content = $this->renderViewFile('layouts/' . $currentLayout, $data);
        }

        return $content;
    }

    private function renderViewFile(string $view, array $data): string
    {
        $file = self::$basePath . '/' . $view . '.php';

        if (!is_file($file)) {
            $this->handleError("View not found: {$view}");
        }

        return $this->renderFile($file, $data);
    }

    private function renderFile(string $file, array $data): string
    {
        return (function () use ($file, $data) {
            try {
                extract($data, EXTR_SKIP);
                ob_start();
                require $file;
                return ob_get_clean();
            } catch (\Throwable $e) {
                $this->logger->error('view.renderfile.exception', ['file' => $file, 'error' => $e->getMessage()]);
                throw $e;
            }
        })->call($this);
    }

    public function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    private static function normalizeTemplateName(string $name): string
    {
        $name = str_replace('\\', '/', trim($name));
        $name = preg_replace('#\.php$#', '', $name);
        $name = trim($name, '/');

        if (
            $name === '' ||
            str_contains($name, '..') ||
            str_starts_with($name, '/')
        ) {
            throw new \InvalidArgumentException('Invalid template path');
        }

        return $name;
    }

    private function handleError(string $message): void
    {
        $this->logger->error('view.error', ['message' => $message, 'view' => $this->view ?? null]);

        if (self::$debug) {
            throw new \RuntimeException($message);
        }

        throw new \RuntimeException('View rendering error.');
    }

    private function resetState(): void
    {
        $this->sections = [];
        $this->slots = [];
        $this->layout = null;
        $this->usedLayoutFiles = [];
        $this->currentSection = null;
        $this->currentSlot = null;
    }

    public static function shared(?string $key = null, mixed $default = null)
    {
        if ($key === null) {
            return self::$shared;
        }

        return self::$shared[$key] ?? $default;
    }

    public function show(): void
    {
        echo $this->render();
    }
}