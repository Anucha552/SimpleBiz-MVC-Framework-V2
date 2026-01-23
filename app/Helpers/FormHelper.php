<?php

namespace App\Helpers;

use App\Core\Session;

class FormHelper
{
    /**
     * Read a flash message (available on the next request).
     */
    public static function flash(string $key, $default = null)
    {
        Session::start();
        return Session::getFlash($key, $default);
    }

    public static function hasFlash(string $key): bool
    {
        Session::start();
        return Session::hasFlash($key);
    }

    public static function allFlash(): array
    {
        Session::start();
        return Session::getAllFlash();
    }

    /**
     * Get old input (available on the next request).
     *
     * Intended for HTML attributes like value="...".
     */
    public static function old(string $key, $default = '', bool $escape = true): string
    {
        Session::start();
        $value = Session::old($key, $default);

        if (is_array($value) || is_object($value)) {
            $value = $default;
        }

        $stringValue = (string) $value;

        return $escape ? SecurityHelper::escape($stringValue) : $stringValue;
    }

    /**
     * Get raw old input (can be array).
     */
    public static function oldRaw(?string $key = null, $default = null)
    {
        Session::start();
        return Session::old($key, $default);
    }

    public static function hasOld(string $key): bool
    {
        Session::start();
        return Session::hasOldInput($key);
    }

    /**
     * Get validation errors flashed under "validation_errors".
     *
     * Structure: [field => [message1, message2, ...]]
     */
    public static function errors(?string $field = null): array
    {
        Session::start();
        $errors = Session::getFlash('validation_errors', []);

        if (!is_array($errors)) {
            return [];
        }

        if ($field === null) {
            return $errors;
        }

        $fieldErrors = $errors[$field] ?? [];
        return is_array($fieldErrors) ? $fieldErrors : [];
    }

    public static function hasError(string $field): bool
    {
        return !empty(self::errors($field));
    }

    /**
     * Get the first error message for a field.
     */
    public static function firstError(string $field, ?string $default = null, bool $escape = true): ?string
    {
        $messages = self::errors($field);
        $first = $messages[0] ?? $default;

        if ($first === null) {
            return null;
        }

        $stringValue = (string) $first;
        return $escape ? SecurityHelper::escape($stringValue) : $stringValue;
    }

    /**
     * Convenience for Bootstrap-ish inputs.
     */
    public static function invalidClass(string $field, string $class = 'is-invalid'): string
    {
        return self::hasError($field) ? $class : '';
    }

    public static function csrfField(): string
    {
        Session::start();
        return Session::csrfField();
    }

    public static function csrfMeta(): string
    {
        Session::start();
        return Session::csrfMeta();
    }
}
