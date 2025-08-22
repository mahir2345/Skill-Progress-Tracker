<?php

class ChartHelper {
    
    /**
     * Generate an array of colors for charts
     * 
     * @param int $count Number of colors needed
     * @return array Array of color hex codes
     */
    public static function generateColors($count) {
        $colors = [
            '#0d6efd', '#198754', '#dc3545', '#ffc107', '#0dcaf0',
            '#6f42c1', '#fd7e14', '#20c997', '#e83e8c', '#6c757d'
        ];
        
        $result = [];
        for ($i = 0; $i < $count; $i++) {
            $result[] = $colors[$i % count($colors)];
        }
        
        return $result;
    }
    
    /**
     * Get default chart options (for future use)
     * 
     * @return array Default chart configuration
     */
    public static function getDefaultOptions() {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false
        ];
    }
}