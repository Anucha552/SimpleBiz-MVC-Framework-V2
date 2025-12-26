<?php
/**
 * VIEW CLASS
 * 
 * Purpose: Handles view rendering with layout support
 * Features: Master layouts, sections, content blocks
 * 
 * This class is optional - controllers can render views directly
 * Use this when you need advanced features like layouts
 */

namespace App\Core;

class View
{
    /**
     * Current view file
     */
    private string $view;

    /**
     * Data to pass to view
     */
    private array $data;

    /**
     * Layout file (optional)
     */
    private ?string $layout = null;

    /**
     * Content sections
     */
    private array $sections = [];

    /**
     * Current section being captured
     */
    private ?string $currentSection = null;

    /**
     * Create new View instance
     * 
     * @param string $view View file path
     * @param array $data Data to pass to view
     */
    public function __construct(string $view, array $data = [])
    {
        $this->view = $view;
        $this->data = $data;
    }

    /**
     * Set layout for this view
     * 
     * @param string $layout Layout file name
     * @return self
     */
    public function layout(string $layout): self
    {
        $this->layout = $layout;
        return $this;
    }

    /**
     * Start a section
     * 
     * Usage in view file:
     * <?php $this->section('title'); ?>
     * My Page Title
     * <?php $this->endSection(); ?>
     * 
     * @param string $name Section name
     */
    public function section(string $name): void
    {
        $this->currentSection = $name;
        ob_start();
    }

    /**
     * End current section
     */
    public function endSection(): void
    {
        if ($this->currentSection) {
            $this->sections[$this->currentSection] = ob_get_clean();
            $this->currentSection = null;
        }
    }

    /**
     * Yield section content in layout
     * 
     * Usage in layout file:
     * <?= $this->yieldSection('title') ?>
     * 
     * @param string $name Section name
     * @param string $default Default content if section not set
     * @return string Section content
     */
    public function yieldSection(string $name, string $default = ''): string
    {
        return $this->sections[$name] ?? $default;
    }

    /**
     * Render the view
     * 
     * @return string Rendered HTML
     */
    public function render(): string
    {
        // Extract data to variables
        extract($this->data);

        // Render view content
        ob_start();
        $viewFile = __DIR__ . '/../Views/' . $this->view . '.php';
        
        if (!file_exists($viewFile)) {
            throw new \Exception("View file not found: {$this->view}");
        }

        require $viewFile;
        $content = ob_get_clean();

        // If no layout, return content directly
        if (!$this->layout) {
            return $content;
        }

        // Store content in 'content' section
        $this->sections['content'] = $content;

        // Render layout
        ob_start();
        $layoutFile = __DIR__ . '/../Views/layouts/' . $this->layout . '.php';
        
        if (!file_exists($layoutFile)) {
            throw new \Exception("Layout file not found: {$this->layout}");
        }

        require $layoutFile;
        return ob_get_clean();
    }

    /**
     * Output rendered view
     */
    public function show(): void
    {
        echo $this->render();
    }
}
