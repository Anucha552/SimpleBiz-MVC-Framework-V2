<?php

namespace App\Core;

final class ModuleManager
{
    /**
     * @return array<int, class-string<ModuleInterface>>
     */
    public function enabledModules(): array
    {
        $configPath = __DIR__ . '/../../config/modules.php';
        if (!file_exists($configPath)) {
            return [];
        }

        $modules = require $configPath;
        if (!is_array($modules)) {
            return [];
        }

        // Normalize numeric arrays only
        $normalized = [];
        foreach ($modules as $moduleClass) {
            if (is_string($moduleClass) && $moduleClass !== '') {
                $normalized[] = $moduleClass;
            }
        }

        return $normalized;
    }

    public function registerEnabled(Router $router): void
    {
        foreach ($this->enabledModules() as $moduleClass) {
            if (!class_exists($moduleClass)) {
                throw new \RuntimeException("Module class not found: {$moduleClass}");
            }

            $module = new $moduleClass();
            if (!$module instanceof ModuleInterface) {
                throw new \RuntimeException("Module must implement ModuleInterface: {$moduleClass}");
            }

            $module->register($router);
        }
    }
}
