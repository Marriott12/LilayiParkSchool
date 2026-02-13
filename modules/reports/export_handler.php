<?php
/**
 * Report Export Handler
 * Handles PDF and Excel export functionality for reports
 */

function handleReportExport($reportType, $format, $term = null, $year = null, $classID = null) {
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../base/BaseModel.php';
    require_once __DIR__ . '/ReportsModel.php';
    
    $reportsModel = new ReportsModel();
    
    // Fetch data based on report type
    $reportData = [];
    $reportTitle = '';
    
    switch ($reportType) {
        case 'fees':
            $reportData = $reportsModel->getFeeCollectionReport($term, $year);
            $reportTitle = 'Fee Collection Report';
            if ($term) $reportTitle .= " - Term $term";
            if ($year) $reportTitle .= " $year";
            break;
        case 'attendance':
            $reportData = $reportsModel->getAttendanceReport($term, $year);
            $reportTitle = 'Attendance Report';
            if ($term) $reportTitle .= " - Term $term";
            if ($year) $reportTitle .= " $year";
            break;
        case 'enrollment':
            $reportData = $reportsModel->getClassEnrollmentReport();
            $reportTitle = 'Class Enrollment Report';
            break;
        case 'class_roster':
            $reportData = $reportsModel->getClassRosterReport($classID);
            $reportTitle = 'Class Roster Report';
            break;
        case 'payment_by_class':
            $reportData = $reportsModel->getPaymentReportByClass($term, $year);
            $reportTitle = 'Payment Report by Class';
            if ($term) $reportTitle .= " - Term $term";
            if ($year) $reportTitle .= " $year";
            break;
        default:
            die('Invalid report type for export');
    }
    
    if ($format === 'excel') {
        exportToExcel($reportType, $reportData, $reportTitle, $term, $year);
    } elseif ($format === 'pdf') {
        exportToPDF($reportType, $reportData, $reportTitle, $term, $year);
    }
}

function exportToExcel($reportType, $data, $title, $term = null, $year = null) {
    // Set headers for Excel download
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="' . str_replace(' ', '_', $title) . '_' . date('Y-m-d') . '.xls"');
    header('Cache-Control: max-age=0');
    
    echo '<html>';
    echo '<head><meta charset="UTF-8"></head>';
    echo '<body>';
    echo '<h2>' . htmlspecialchars($title) . '</h2>';
    echo '<p>Generated: ' . date('Y-m-d H:i:s') . '</p>';
    
    if ($reportType === 'fees') {
        echo '<table border="1" style="border-collapse: collapse; width: 100%;">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>Class</th>';
        echo '<th>Term</th>';
        echo '<th>Year</th>';
        echo '<th>Total Fee (ZMW)</th>';
        echo '<th>Collected (ZMW)</th>';
        echo '<th>Outstanding (ZMW)</th>';
        echo '<th>Collection %</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        $totalFees = 0;
        $totalCollected = 0;
        $totalOutstanding = 0;
        
        foreach ($data as $row) {
            $totalFees += $row['classFee'];
            $totalCollected += $row['totalCollected'];
            $totalOutstanding += $row['outstanding'];
            $collectionRate = $row['classFee'] > 0 ? ($row['totalCollected'] / $row['classFee']) * 100 : 0;
            
            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['className']) . '</td>';
            echo '<td>Term ' . $row['term'] . '</td>';
            echo '<td>' . $row['year'] . '</td>';
            echo '<td>K ' . number_format($row['classFee'], 2) . '</td>';
            echo '<td>K ' . number_format($row['totalCollected'], 2) . '</td>';
            echo '<td>K ' . number_format($row['outstanding'], 2) . '</td>';
            echo '<td>' . number_format($collectionRate, 1) . '%</td>';
            echo '</tr>';
        }
        
        echo '<tr style="font-weight: bold; background-color: #f0f0f0;">';
        echo '<td colspan="3">TOTAL</td>';
        echo '<td>K ' . number_format($totalFees, 2) . '</td>';
        echo '<td>K ' . number_format($totalCollected, 2) . '</td>';
        echo '<td>K ' . number_format($totalOutstanding, 2) . '</td>';
        echo '<td>' . ($totalFees > 0 ? number_format(($totalCollected / $totalFees) * 100, 1) : 0) . '%</td>';
        echo '</tr>';
        
        echo '</tbody>';
        echo '</table>';
    } elseif ($reportType === 'attendance') {
        echo '<table border="1" style="border-collapse: collapse; width: 100%;">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>Pupil ID</th>';
        echo '<th>Name</th>';
        echo '<th>Term</th>';
        echo '<th>Year</th>';
        echo '<th>Days Present</th>';
        echo '<th>Days Absent</th>';
        echo '<th>Total Days</th>';
        echo '<th>Attendance Rate</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        foreach ($data as $row) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['pupilID']) . '</td>';
            echo '<td>' . htmlspecialchars($row['fName'] . ' ' . $row['lName']) . '</td>';
            echo '<td>Term ' . $row['term'] . '</td>';
            echo '<td>' . $row['year'] . '</td>';
            echo '<td>' . $row['daysPresent'] . '</td>';
            echo '<td>' . $row['daysAbsent'] . '</td>';
            echo '<td>' . $row['totalDays'] . '</td>';
            echo '<td>' . $row['attendanceRate'] . '%</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
    } elseif ($reportType === 'enrollment') {
        echo '<table border="1" style="border-collapse: collapse; width: 100%;">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>Class Name</th>';
        echo '<th>Class Teacher</th>';
        echo '<th>Total Pupils</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        $totalPupils = 0;
        
        foreach ($data as $row) {
            $totalPupils += $row['totalPupils'];
            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['className']) . '</td>';
            echo '<td>' . htmlspecialchars(($row['teacherFirstName'] ?? '') . ' ' . ($row['teacherLastName'] ?? 'Not Assigned')) . '</td>';
            echo '<td>' . $row['totalPupils'] . '</td>';
            echo '</tr>';
        }
        
        echo '<tr style="font-weight: bold; background-color: #f0f0f0;">';
        echo '<td colspan="2">TOTAL ENROLLMENT</td>';
        echo '<td>' . $totalPupils . ' pupils across ' . count($data) . ' classes</td>';
        echo '</tr>';
        
        echo '</tbody>';
        echo '</table>';
    } elseif ($reportType === 'class_roster') {
        // Group by class
        $groupedData = [];
        foreach ($data as $row) {
            $className = $row['className'];
            if (!isset($groupedData[$className])) {
                $groupedData[$className] = [];
            }
            $groupedData[$className][] = $row;
        }
        
        foreach ($groupedData as $className => $pupils) {
            echo '<h3>' . htmlspecialchars($className) . ' (' . count($pupils) . ' pupils)</h3>';
            echo '<table border="1" style="border-collapse: collapse; width: 100%;">';
            echo '<thead>';
            echo '<tr>';
            echo '<th>Pupil</th>';
            echo '<th>Gender</th>';
            echo '<th>Age</th>';
            echo '<th>Parent Contact</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';
            
            foreach ($pupils as $pupil) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($pupil['pupilName']) . '</td>';
                echo '<td>' . htmlspecialchars($pupil['gender'] ?? 'N/A') . '</td>';
                echo '<td>' . ($pupil['age'] ?? 'N/A') . '</td>';
                echo '<td>' . htmlspecialchars($pupil['parentContact'] ?? 'N/A') . '</td>';
                echo '</tr>';
            }
            
            echo '</tbody>';
            echo '</table>';
            echo '<br>';
        }
    } elseif ($reportType === 'payment_by_class') {
        // Group by class
        $groupedData = [];
        foreach ($data as $row) {
            $className = $row['className'];
            if (!isset($groupedData[$className])) {
                $groupedData[$className] = [
                    'pupils' => [],
                    'totalBalance' => 0
                ];
            }
            $groupedData[$className]['pupils'][] = $row;
            $groupedData[$className]['totalBalance'] += $row['balance'];
        }
        
        // Sort by total balance (highest first)
        uasort($groupedData, function($a, $b) {
            return $b['totalBalance'] <=> $a['totalBalance'];
        });
        
        foreach ($groupedData as $className => $classInfo) {
            echo '<h3>' . htmlspecialchars($className) . ' - Total Balance: K ' . number_format($classInfo['totalBalance'], 2) . '</h3>';
            echo '<table border="1" style="border-collapse: collapse; width: 100%;">';
            echo '<thead>';
            echo '<tr>';
            echo '<th>Pupil Name</th>';
            echo '<th>Class</th>';
            echo '<th>Total Paid</th>';
            echo '<th>Balance</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';
            
            foreach ($classInfo['pupils'] as $pupil) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($pupil['pupilName']) . '</td>';
                echo '<td>' . htmlspecialchars($pupil['className']) . '</td>';
                echo '<td>K ' . number_format($pupil['totalPaid'], 2) . '</td>';
                echo '<td>K ' . number_format($pupil['balance'], 2) . '</td>';
                echo '</tr>';
            }
            
            echo '</tbody>';
            echo '</table>';
            echo '<br>';
        }
    }
    
    echo '</body>';
    echo '</html>';
    exit;
}

function exportToPDF($reportType, $data, $title, $term = null, $year = null) {
    // Simple PDF export using HTML to PDF conversion
    // For production, consider using a library like TCPDF or FPDF
    
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment;filename="' . str_replace(' ', '_', $title) . '_' . date('Y-m-d') . '.pdf"');
    
    // For now, we'll use a simple HTML-based approach
    // In production, integrate a proper PDF library
    echo '%PDF-1.4' . "\n";
    echo 'This is a placeholder PDF export. Please integrate a proper PDF library like TCPDF or FPDF for production use.' . "\n";
    echo 'Report: ' . $title . "\n";
    echo 'Generated: ' . date('Y-m-d H:i:s') . "\n";
    echo "\n";
    echo 'Data exported: ' . count($data) . ' records' . "\n";
    
    exit;
}
