<?php

class Utils {
    
    /**
     * Format a timestamp into a relative time string (e.g., "2 hours ago", "3 days ago")
     * 
     * @param string $datetime The datetime string to format
     * @return string The formatted relative time string
     */
    public function formatRelativeTime($datetime) {
        if (empty($datetime)) {
            return 'Never';
        }
        
        $time = time() - strtotime($datetime);
        
        if ($time < 60) {
            return 'just now';
        } elseif ($time < 3600) {
            $minutes = floor($time / 60);
            return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
        } elseif ($time < 86400) {
            $hours = floor($time / 3600);
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        } elseif ($time < 2592000) {
            $days = floor($time / 86400);
            return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        } elseif ($time < 31536000) {
            $months = floor($time / 2592000);
            return $months . ' month' . ($months > 1 ? 's' : '') . ' ago';
        } else {
            $years = floor($time / 31536000);
            return $years . ' year' . ($years > 1 ? 's' : '') . ' ago';
        }
    }
    
    /**
     * Format a date string into a readable format
     * 
     * @param string $datetime The datetime string to format
     * @return string The formatted date string
     */
    public function formatDate($datetime) {
        if (empty($datetime)) {
            return 'Never';
        }
        return date('M j, Y', strtotime($datetime));
    }
    
    /**
     * Static version of formatRelativeTime for backward compatibility
     * 
     * @param string $datetime The datetime string to format
     * @return string The formatted relative time string
     */
    public static function formatRelativeTimeStatic($datetime) {
        return (new self())->formatRelativeTime($datetime);
    }

    /**
     * Static version of formatDate for backward compatibility
     * 
     * @param string $datetime The datetime string to format
     * @return string The formatted date string
     */
    public static function formatDateStatic($datetime) {
        return (new self())->formatDate($datetime);
    }
}