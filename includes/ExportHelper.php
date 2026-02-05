<?php
/**
 * ExportHelper - Handles data export to CSV and Excel formats
 */

class ExportHelper {
    
    /**
     * Export data to CSV format
     * 
     * @param array $data Array of associative arrays
     * @param string $filename Output filename
     * @param array $headers Optional custom headers
     */
    public static function toCSV($data, $filename, $headers = null) {
        if (empty($data)) {
            return false;
        }
        
        // Set headers for download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Open output stream
        $output = fopen('php://output', 'w');
        
        // Add BOM for UTF-8 (helps Excel recognize UTF-8)
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Use custom headers or extract from first row
        if ($headers === null) {
            $headers = array_keys($data[0]);
        }
        
        // Write headers
        fputcsv($output, $headers);
        
        // Write data rows
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Export data to Excel-compatible HTML format
     * This creates an HTML table that Excel can open
     * 
     * @param array $data Array of associative arrays
     * @param string $filename Output filename
     * @param string $title Sheet title
     * @param array $headers Optional custom headers
     */
    public static function toExcel($data, $filename, $title = 'Export', $headers = null) {
        if (empty($data)) {
            return false;
        }
        
        // Set headers for Excel download
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Use custom headers or extract from first row
        if ($headers === null) {
            $headers = array_keys($data[0]);
        }
        
        // Start Excel HTML
        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
        echo '<head>';
        echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
        echo '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet>';
        echo '<x:Name>' . htmlspecialchars($title) . '</x:Name>';
        echo '<x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions>';
        echo '</x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->';
        echo '<style>';
        echo 'table { border-collapse: collapse; width: 100%; }';
        echo 'th { background-color: #2d5016; color: white; font-weight: bold; padding: 8px; border: 1px solid #ddd; }';
        echo 'td { padding: 8px; border: 1px solid #ddd; }';
        echo 'tr:nth-child(even) { background-color: #f2f2f2; }';
        echo '</style>';
        echo '</head>';
        echo '<body>';
        echo '<h2>' . htmlspecialchars($title) . '</h2>';
        echo '<table>';
        
        // Write headers
        echo '<thead><tr>';
        foreach ($headers as $header) {
            echo '<th>' . htmlspecialchars($header) . '</th>';
        }
        echo '</tr></thead>';
        
        // Write data rows
        echo '<tbody>';
        foreach ($data as $row) {
            echo '<tr>';
            foreach ($row as $cell) {
                echo '<td>' . htmlspecialchars($cell ?? '') . '</td>';
            }
            echo '</tr>';
        }
        echo '</tbody>';
        
        echo '</table>';
        echo '</body>';
        echo '</html>';
        exit;
    }
    
    /**
     * Prepare data for export by selecting specific columns and formatting
     * 
     * @param array $data Raw data array
     * @param array $columns Column mapping ['displayName' => 'dataKey']
     * @param callable $formatter Optional formatter function
     * @return array Formatted data ready for export
     */
    public static function prepareData($data, $columns, $formatter = null) {
        $prepared = [];
        
        foreach ($data as $row) {
            $newRow = [];
            foreach ($columns as $displayName => $dataKey) {
                // Handle nested keys (e.g., 'user.name')
                if (strpos($dataKey, '.') !== false) {
                    $keys = explode('.', $dataKey);
                    $value = $row;
                    foreach ($keys as $key) {
                        $value = $value[$key] ?? null;
                    }
                    $newRow[$displayName] = $value;
                } else {
                    $newRow[$displayName] = $row[$dataKey] ?? '';
                }
            }
            
            // Apply custom formatter if provided
            if ($formatter !== null) {
                $newRow = $formatter($newRow, $row);
            }
            
            $prepared[] = $newRow;
        }
        
        return $prepared;
    }
    
    /**
     * Generate filename with timestamp
     * 
     * @param string $prefix Filename prefix
     * @param string $extension File extension (csv or xls)
     * @return string Generated filename
     */
    public static function generateFilename($prefix, $extension = 'csv') {
        $timestamp = date('Y-m-d_His');
        return $prefix . '_' . $timestamp . '.' . $extension;
    }
}
