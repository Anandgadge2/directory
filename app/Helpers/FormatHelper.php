<?php

namespace App\Helpers;

class FormatHelper
{
    /**
     * Format name to Title Case consistently
     * Example: "ANANd gadge" -> "Anand Gadge"
     */
    public static function formatName(?string $name): string
    {
        if (empty($name)) {
            return '';
        }

        // Convert to lowercase first, then title case
        $formatted = mb_convert_case(trim($name), MB_CASE_TITLE, 'UTF-8');
        
        return $formatted;
    }

    /**
     * Format multiple names in a collection
     */
    public static function formatNames($items, string $field = 'name'): array
    {
        if (is_array($items)) {
            return array_map(function($item) use ($field) {
                if (is_object($item)) {
                    $item->{$field} = self::formatName($item->{$field} ?? '');
                } elseif (is_array($item)) {
                    $item[$field] = self::formatName($item[$field] ?? '');
                }
                return $item;
            }, $items);
        }

        return $items;
    }
}

