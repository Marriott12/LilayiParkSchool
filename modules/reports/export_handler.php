<?php
/**
 * Report export helper
 * Provides `exportToExcel($reportType,$data,$title)` and `exportToPDF($reportType,$data,$title)`
 */

// Ensure bootstrap if called directly
if (!defined('APP_INIT')) {
    require_once __DIR__ . '/../../includes/bootstrap.php';
}

/**
 * Export dataset to Excel-friendly HTML (served with XLS headers and UTF-8 BOM)
 */
function exportToExcel($reportType, array $data, $title = '') {
    $filename = ($title ?: $reportType) . '_' . date('Y-m-d') . '.xls';
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . str_replace(' ', '_', $filename) . '"');
    header('Cache-Control: max-age=0');
    echo "\xEF\xBB\xBF"; // UTF-8 BOM

    echo '<!doctype html><html><head><meta charset="UTF-8"><style>table{border-collapse:collapse;}th,td{border:1px solid #ccc;padding:6px;text-align:left;}</style></head><body>';
    echo '<h2>' . htmlspecialchars($title) . '</h2>';
    echo '<p>Generated: ' . date('Y-m-d H:i:s') . '</p>';

    // Render tables for known report types
    if ($reportType === 'fees_paid' || $reportType === 'fees_outstanding') {
        echo '<table><thead><tr><th>First Name</th><th>Last Name</th><th>Class</th><th>Amount Paid (ZMW)</th><th>Balance (ZMW)</th></tr></thead><tbody>';
        foreach ($data as $row) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['fName'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($row['lName'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($row['className'] ?? '') . '</td>';
            echo '<td>' . number_format((float)($row['amountPaid'] ?? 0), 2) . '</td>';
            echo '<td>' . number_format((float)($row['balance'] ?? 0), 2) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    } else {
        // Generic table: use associative keys from first row
        if (!empty($data)) {
            $first = reset($data);
            echo '<table><thead><tr>';
            foreach (array_keys($first) as $col) echo '<th>' . htmlspecialchars($col) . '</th>';
            echo '</tr></thead><tbody>';
            foreach ($data as $row) {
                echo '<tr>';
                foreach ($row as $cell) echo '<td>' . htmlspecialchars((string)$cell) . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<p>No data available</p>';
        }
    }

    echo '</body></html>';
    exit;
}

/**
 * Export dataset to PDF using Dompdf
 */
function exportToPDF($reportType, array $data, $title = '') {
    $autoload = __DIR__ . '/../../vendor/autoload.php';
    if (!file_exists($autoload)) {
        header('Content-Type: text/plain');
        echo "PDF export requires Composer dependencies (run composer install).";
        exit;
    }
    require_once $autoload;

    $html = '<!doctype html><html><head><meta charset="UTF-8"><style>body{font-family: DejaVu Sans, Arial, Helvetica, sans-serif;}table{width:100%;border-collapse:collapse;}th,td{border:1px solid #ccc;padding:6px;text-align:left;}th{background:#f8f9fa;}</style></head><body>';
    $html .= '<h2>' . htmlspecialchars($title) . '</h2>';
    $html .= '<p>Generated: ' . date('Y-m-d H:i:s') . '</p>';

    if ($reportType === 'fees_paid' || $reportType === 'fees_outstanding') {
        $html .= '<table><thead><tr><th>First Name</th><th>Last Name</th><th>Class</th><th>Amount Paid (ZMW)</th><th>Balance (ZMW)</th></tr></thead><tbody>';
        foreach ($data as $row) {
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($row['fName'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($row['lName'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($row['className'] ?? '') . '</td>';
            $html .= '<td>K ' . number_format((float)($row['amountPaid'] ?? 0), 2) . '</td>';
            $html .= '<td>K ' . number_format((float)($row['balance'] ?? 0), 2) . '</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
    } else {
        if (!empty($data)) {
            $first = reset($data);
            $html .= '<table><thead><tr>';
            foreach (array_keys($first) as $col) $html .= '<th>' . htmlspecialchars($col) . '</th>';
            $html .= '</tr></thead><tbody>';
            foreach ($data as $row) {
                $html .= '<tr>';
                foreach ($row as $cell) $html .= '<td>' . htmlspecialchars((string)$cell) . '</td>';
                $html .= '</tr>';
            }
            $html .= '</tbody></table>';
        } else {
            $html .= '<p>No data available</p>';
        }
    }

    $html .= '</body></html>';

    try {
        $dompdf = new Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $filename = str_replace(' ', '_', ($title ?: $reportType)) . '_' . date('Y-m-d') . '.pdf';
        $dompdf->stream($filename, ['Attachment' => true]);
    } catch (Exception $e) {
        header('Content-Type: text/plain');
        echo 'PDF generation failed: ' . $e->getMessage();
    }

    exit;
}

