<?php
/**
 * Test Payment Insertion
 * This script tests if payments can be successfully inserted into the database
 */

require_once 'config/database.php';
require_once 'includes/BaseModel.php';
require_once 'modules/payments/PaymentModel.php';

echo "<h2>Payment Insertion Test</h2>\n";

try {
    $db = Database::getInstance()->getConnection();
    
    // 1. Check Payment table structure
    echo "<h3>Step 1: Verify Payment Table Structure</h3>\n";
    echo "<pre>";
    $stmt = $db->query("DESCRIBE Payment");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo sprintf("%-15s %-20s %-10s\n", $col['Field'], $col['Type'], $col['Null']);
    }
    echo "</pre>\n";
    
    $existingCols = array_column($columns, 'Field');
    $hasFeeID = in_array('feeID', $existingCols);
    $hasTerm = in_array('term', $existingCols);
    $hasAcademicYear = in_array('academicYear', $existingCols);
    
    echo "<p><strong>Required columns check:</strong></p>";
    echo "<ul>";
    echo "<li>feeID: " . ($hasFeeID ? "<span style='color:green'>✓ EXISTS</span>" : "<span style='color:red'>✗ MISSING</span>") . "</li>";
    echo "<li>term: " . ($hasTerm ? "<span style='color:green'>✓ EXISTS</span>" : "<span style='color:red'>✗ MISSING</span>") . "</li>";
    echo "<li>academicYear: " . ($hasAcademicYear ? "<span style='color:green'>✓ EXISTS</span>" : "<span style='color:red'>✗ MISSING</span>") . "</li>";
    echo "</ul>\n";
    
    // 2. Get a test pupil
    echo "<h3>Step 2: Get Test Pupil</h3>\n";
    $stmt = $db->query("SELECT p.pupilID, p.fName, p.lName, pc.classID, c.className 
                        FROM Pupil p 
                        INNER JOIN Pupil_Class pc ON p.pupilID = pc.pupilID 
                        INNER JOIN Class c ON pc.classID = c.classID 
                        LIMIT 1");
    $pupil = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$pupil) {
        throw new Exception("No pupils found in database. Cannot test payment insertion.");
    }
    
    echo "<p>Test pupil: <strong>" . htmlspecialchars($pupil['fName'] . ' ' . $pupil['lName']) . "</strong></p>";
    echo "<p>Class: <strong>" . htmlspecialchars($pupil['className']) . "</strong></p>\n";
    
    // 3. Get or create a fee record
    echo "<h3>Step 3: Check Fee Record</h3>\n";
    $currentYear = date('Y');
    $stmt = $db->prepare("SELECT feeID, feeAmt, term FROM Fees WHERE classID = ? AND year = ? ORDER BY term DESC LIMIT 1");
    $stmt->execute([$pupil['classID'], $currentYear]);
    $fee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$fee) {
        echo "<p style='color:orange'>⚠️ No fee record found for class " . htmlspecialchars($pupil['className']) . " in year $currentYear</p>";
        echo "<p>Creating a test fee record...</p>\n";
        
        // Create a test fee
        $stmt = $db->prepare("INSERT INTO Fees (classID, feeAmt, term, year) VALUES (?, 1000.00, 1, ?)");
        $stmt->execute([$pupil['classID'], $currentYear]);
        
        // Retrieve the created fee
        $stmt = $db->prepare("SELECT feeID, feeAmt, term FROM Fees WHERE classID = ? AND year = ? ORDER BY term DESC LIMIT 1");
        $stmt->execute([$pupil['classID'], $currentYear]);
        $fee = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<p style='color:green'>✓ Test fee record created</p>\n";
    }
    
    echo "<p>Fee ID: <strong>" . htmlspecialchars($fee['feeID']) . "</strong></p>";
    echo "<p>Fee Amount: <strong>K " . number_format($fee['feeAmt'], 2) . "</strong></p>";
    echo "<p>Term: <strong>" . htmlspecialchars($fee['term']) . "</strong></p>\n";
    
    // 4. Test payment insertion
    echo "<h3>Step 4: Test Payment Insertion</h3>\n";
    
    $paymentModel = new PaymentModel();
    
    $testAmount = 100.00;
    $paymentData = [
        'pupilID' => $pupil['pupilID'],
        'feeID' => $fee['feeID'],
        'classID' => $pupil['classID'],
        'pmtAmt' => $testAmount,
        'balance' => $fee['feeAmt'] - $testAmount,
        'paymentDate' => date('Y-m-d'),
        'paymentMode' => 'Cash',
        'remark' => 'Test payment - ' . date('Y-m-d H:i:s'),
        'term' => $fee['term'],
        'academicYear' => $currentYear
    ];
    
    echo "<p>Attempting to insert payment with data:</p>";
    echo "<pre>" . print_r($paymentData, true) . "</pre>\n";
    
    $result = $paymentModel->create($paymentData);
    
    if ($result) {
        echo "<p style='color:green; font-size:1.2em;'><strong>✓ SUCCESS!</strong> Payment inserted successfully!</p>";
        echo "<p>Returned ID: <strong>" . htmlspecialchars($result) . "</strong></p>\n";
        
        // Verify the payment was actually inserted
        echo "<h3>Step 5: Verify Insertion</h3>\n";
        if ($result !== '0' && $result !== 0) {
            $verifyStmt = $db->prepare("SELECT * FROM Payment WHERE payID = ?");
            $verifyStmt->execute([$result]);
            $insertedPayment = $verifyStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($insertedPayment) {
                echo "<p style='color:green'>✓ Payment verified in database:</p>";
                echo "<pre>" . print_r($insertedPayment, true) . "</pre>\n";
                
                // Clean up test payment
                echo "<p>Cleaning up test payment...</p>";
                $db->prepare("DELETE FROM Payment WHERE payID = ?")->execute([$result]);
                echo "<p style='color:green'>✓ Test payment removed</p>\n";
            } else {
                echo "<p style='color:red'>✗ Payment ID was returned but record not found in database</p>\n";
            }
        } else {
            echo "<p style='color:orange'>⚠️ Returned ID is 0 or '0' - checking if payment was inserted anyway...</p>";
            $verifyStmt = $db->prepare("SELECT * FROM Payment WHERE pupilID = ? AND paymentDate = ? AND pmtAmt = ? ORDER BY createdAt DESC LIMIT 1");
            $verifyStmt->execute([$pupil['pupilID'], date('Y-m-d'), $testAmount]);
            $insertedPayment = $verifyStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($insertedPayment) {
                echo "<p style='color:green'>✓ Payment WAS inserted despite returning 0! This is the bug we fixed.</p>";
                echo "<pre>" . print_r($insertedPayment, true) . "</pre>\n";
                echo "<p>Cleaning up test payment...</p>";
                $db->prepare("DELETE FROM Payment WHERE payID = ?")->execute([$insertedPayment['payID']]);
                echo "<p style='color:green'>✓ Test payment removed</p>\n";
            } else {
                echo "<p style='color:red'>✗ Payment was NOT inserted and ID returned 0</p>\n";
            }
        }
        
    } else {
        echo "<p style='color:red; font-size:1.2em;'><strong>✗ FAILED!</strong> Payment insertion failed!</p>";
        echo "<p>Returned value: " . var_export($result, true) . "</p>\n";
    }
    
    echo "<h3>Summary</h3>";
    echo "<p><strong>Payment insertion test complete.</strong></p>";
    
} catch (Exception $e) {
    echo "<p style='color:red'><strong>ERROR:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
}
