<?php
require_once 'includes/bootstrap.php';
require_once 'includes/Auth.php';

Auth::requireLogin();

// Only admin can manage payments
if (!Auth::hasRole('admin')) {
    Session::setFlash('error', 'Only administrators can manage payments.');
    header('Location: /LilayiParkSchool/403.php');
    exit;
}

require_once 'modules/payments/PaymentModel.php';
require_once 'modules/pupils/PupilModel.php';
require_once 'modules/fees/FeesModel.php';

$paymentModel = new PaymentModel();
$pupilModel = new PupilModel();
$feesModel = new FeesModel();

// Get pupils with their current class from Pupil_Class junction table
$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("
    SELECT DISTINCT p.pupilID, p.fName, p.lName, pc.classID, c.className
    FROM Pupil p
    INNER JOIN Pupil_Class pc ON p.pupilID = pc.pupilID
    INNER JOIN Class c ON pc.classID = c.classID
    ORDER BY p.fName, p.lName
");
$stmt->execute();
$pupils = $stmt->fetchAll();

// Handle AJAX request for pupil details
if (isset($_GET['action']) && $_GET['action'] === 'getPupilDetails' && isset($_GET['pupilID'])) {
    header('Content-Type: application/json');
    
    $pupilID = $_GET['pupilID'];
    
    // Get pupil's class
    $stmt = $db->prepare("
        SELECT pc.classID, c.className
        FROM Pupil_Class pc
        INNER JOIN Class c ON pc.classID = c.classID
        WHERE pc.pupilID = ?
        LIMIT 1
    ");
    $stmt->execute([$pupilID]);
    $pupilClass = $stmt->fetch();
    
    if ($pupilClass) {
        // Get current term fee for this class
        $stmt = $db->prepare("
            SELECT feeAmt, term, year
            FROM Fees
            WHERE classID = ? AND year = ?
            ORDER BY term DESC
            LIMIT 1
        ");
        $currentYear = date('Y');
        $stmt->execute([$pupilClass['classID'], $currentYear]);
        $currentFee = $stmt->fetch();
        
        // Get all payments made by this pupil
        $stmt = $db->prepare("
            SELECT SUM(pmtAmt) as totalPaid
            FROM Payment
            WHERE pupilID = ? AND classID = ?
        ");
        $stmt->execute([$pupilID, $pupilClass['classID']]);
        $paymentsData = $stmt->fetch();
        $totalPaid = $paymentsData['totalPaid'] ?? 0;
        
        // Calculate balance
        $totalFee = $currentFee['feeAmt'] ?? 0;
        $balance = $totalFee - $totalPaid;
        
        // Get previous payments
        $stmt = $db->prepare("
            SELECT payID, pmtAmt, balance, paymentDate, paymentMode, remark
            FROM Payment
            WHERE pupilID = ? AND classID = ?
            ORDER BY paymentDate DESC
            LIMIT 5
        ");
        $stmt->execute([$pupilID, $pupilClass['classID']]);
        $previousPayments = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'classID' => $pupilClass['classID'],
            'className' => $pupilClass['className'],
            'totalFee' => $totalFee,
            'totalPaid' => $totalPaid,
            'balance' => $balance,
            'currentTerm' => $currentFee['term'] ?? 'N/A',
            'currentYear' => $currentFee['year'] ?? date('Y'),
            'previousPayments' => $previousPayments
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Pupil not enrolled in any class']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token first
    if (!CSRF::requireToken()) {
        $error = $GLOBALS['csrf_error'] ?? 'Security validation failed. Please try again.';
    } else {
        $pupilID = trim($_POST['pupilID'] ?? '');
        $classID = trim($_POST['classID'] ?? '');
        $pmtAmt = floatval($_POST['pmtAmt'] ?? 0);
        
        // Validation
        if (empty($pupilID)) {
            $error = 'Please select a pupil';
        } elseif (empty($classID)) {
            $error = 'Class information is required';
        } elseif ($pmtAmt <= 0) {
            $error = 'Amount paid must be greater than zero';
        }
        
        if (!isset($error)) {
            try {
                // Calculate the balance automatically
                // Get current term fee for this class
                $stmt = $db->prepare("
                    SELECT feeAmt
                    FROM Fees
                    WHERE classID = ? AND year = ?
                    ORDER BY term DESC
                    LIMIT 1
                ");
                $currentYear = date('Y');
                $stmt->execute([$classID, $currentYear]);
                $currentFee = $stmt->fetch();
                $totalFee = $currentFee['feeAmt'] ?? 0;
                
                // Get all payments made by this pupil for this class
                $stmt = $db->prepare("
                    SELECT SUM(pmtAmt) as totalPaid
                    FROM Payment
                    WHERE pupilID = ? AND classID = ?
                ");
                $stmt->execute([$pupilID, $classID]);
                $paymentsData = $stmt->fetch();
                $totalPaid = $paymentsData['totalPaid'] ?? 0;
                
                // Calculate current balance before this payment
                $currentBalance = $totalFee - $totalPaid;
                
                // Calculate new balance after this payment
                $newBalance = $currentBalance - $pmtAmt;
                
                $data = [
                    'pupilID' => $pupilID,
                    'classID' => $classID,
                    'pmtAmt' => $pmtAmt,
                    'balance' => $newBalance,
                    'paymentDate' => $_POST['paymentDate'] ?? date('Y-m-d'),
                    'paymentMode' => trim($_POST['paymentMode'] ?? 'Cash'),
                    'remark' => trim($_POST['remark'] ?? '')
                ];
                
                $paymentModel->create($data);
                Session::setFlash('success', 'Payment recorded successfully');
                
                CSRF::regenerateToken();
                header('Location: payments_list.php');
                exit;
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Record New Payment';
$currentPage = 'payments';
require_once 'includes/header.php';
?>

<div class="mb-4">
    <a href="payments_list.php" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back to Payments
    </a>
</div>

<div class="card">
    <div class="card-header" style="background: linear-gradient(135deg, #2d5016 0%, #5cb85c 100%); color: white;">
        <h5 class="mb-0">
            <i class="bi bi-credit-card-fill"></i> <?= $pageTitle ?>
        </h5>
    </div>
    <div class="card-body">
        <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="" id="paymentForm">
            <?= CSRF::field() ?>
            
            <!-- Pupil Selection -->
            <div class="card mb-4" style="border-left: 4px solid #2d5016;">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bi bi-person-fill"></i> Pupil Selection</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Select Pupil <span class="text-danger">*</span></label>
                        <select class="form-select" name="pupilID" id="pupilSelect" required>
                            <option value="">-- Select Pupil --</option>
                            <?php foreach ($pupils as $pupil): ?>
                            <option value="<?= $pupil['pupilID'] ?>" data-classid="<?= $pupil['classID'] ?>">
                                <?= htmlspecialchars($pupil['fName'] . ' ' . $pupil['lName'] . ' - ' . $pupil['className']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div id="pupilDetails" style="display: none;">
                        <div class="alert alert-info">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Class:</strong> <span id="displayClass"></span>
                                    <input type="hidden" name="classID" id="classID">
                                </div>
                                <div class="col-md-6">
                                    <strong>Term:</strong> <span id="displayTerm"></span> / <span id="displayYear"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Fee Summary -->
            <div class="card mb-4" style="border-left: 4px solid #5cb85c;" id="feeSummary" style="display: none;">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bi bi-calculator-fill"></i> Fee Summary</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="text-center p-3 bg-light rounded">
                                <small class="text-muted">Total Fee</small>
                                <h4 class="mb-0 text-primary">K <span id="totalFee">0.00</span></h4>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 bg-light rounded">
                                <small class="text-muted">Total Paid</small>
                                <h4 class="mb-0 text-success">K <span id="totalPaid">0.00</span></h4>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 bg-light rounded">
                                <small class="text-muted">Current Balance</small>
                                <h4 class="mb-0 text-warning">K <span id="currentBalance">0.00</span></h4>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 bg-light rounded">
                                <small class="text-muted">New Balance</small>
                                <h4 class="mb-0 text-danger">K <span id="newBalance">0.00</span></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Payment Details -->
            <div class="card mb-4" style="border-left: 4px solid #f0ad4e;">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bi bi-cash-stack"></i> Payment Details</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Amount Paid (K) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" name="pmtAmt" id="pmtAmt"
                                   placeholder="0.00" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Balance (K) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" name="balance" id="balanceInput"
                                   readonly style="background-color: #f8f9fa;">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Payment Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="paymentDate" 
                                   value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Mode of Payment <span class="text-danger">*</span></label>
                            <select class="form-select" name="paymentMode" required>
                                <option value="Cash" selected>Cash</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Mobile Money">Mobile Money</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Receipt/Reference Number</label>
                        <input type="text" class="form-control" name="remark" 
                               placeholder="Payment reference or remarks">
                    </div>
                </div>
            </div>
            
            <!-- Previous Payments -->
            <div class="card mb-4" style="border-left: 4px solid #5bc0de;" id="previousPaymentsCard" style="display: none;">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bi bi-clock-history"></i> Recent Payments (Last 5)</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Receipt No.</th>
                                    <th>Date</th>
                                    <th>Amount Paid</th>
                                    <th>Payment Mode</th>
                                    <th>Balance After</th>
                                    <th>Remark</th>
                                </tr>
                            </thead>
                            <tbody id="previousPaymentsBody">
                                <tr>
                                    <td colspan="6" class="text-center text-muted">Select a pupil to view payment history</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn" style="background-color: #2d5016; color: white;">
                    <i class="bi bi-save"></i> Record Payment
                </button>
                <a href="payments_list.php" class="btn btn-secondary">
                    <i class="bi bi-x-circle"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const pupilSelect = document.getElementById('pupilSelect');
    const pupilDetails = document.getElementById('pupilDetails');
    const feeSummary = document.getElementById('feeSummary');
    const previousPaymentsCard = document.getElementById('previousPaymentsCard');
    const pmtAmtInput = document.getElementById('pmtAmt');
    const balanceInput = document.getElementById('balanceInput');
    
    let currentData = null;
    
    // Check if pupil is pre-selected from URL parameter
    const urlParams = new URLSearchParams(window.location.search);
    const preSelectedPupil = urlParams.get('pupil');
    if (preSelectedPupil) {
        pupilSelect.value = preSelectedPupil;
        pupilSelect.dispatchEvent(new Event('change'));
    }
    
    pupilSelect.addEventListener('change', function() {
        const pupilID = this.value;
        
        if (!pupilID) {
            pupilDetails.style.display = 'none';
            feeSummary.style.display = 'none';
            previousPaymentsCard.style.display = 'none';
            return;
        }
        
        // Fetch pupil details via AJAX
        fetch(`?action=getPupilDetails&pupilID=${pupilID}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    currentData = data;
                    
                    // Update pupil details
                    document.getElementById('displayClass').textContent = data.className;
                    document.getElementById('classID').value = data.classID;
                    document.getElementById('displayTerm').textContent = data.currentTerm;
                    document.getElementById('displayYear').textContent = data.currentYear;
                    pupilDetails.style.display = 'block';
                    
                    // Update fee summary
                    document.getElementById('totalFee').textContent = parseFloat(data.totalFee).toFixed(2);
                    document.getElementById('totalPaid').textContent = parseFloat(data.totalPaid).toFixed(2);
                    document.getElementById('currentBalance').textContent = parseFloat(data.balance).toFixed(2);
                    document.getElementById('newBalance').textContent = parseFloat(data.balance).toFixed(2);
                    feeSummary.style.display = 'block';
                    
                    // Update balance input
                    balanceInput.value = parseFloat(data.balance).toFixed(2);
                    
                    // Update previous payments
                    const tbody = document.getElementById('previousPaymentsBody');
                    if (data.previousPayments && data.previousPayments.length > 0) {
                        tbody.innerHTML = '';
                        data.previousPayments.forEach(payment => {
                            const row = `
                                <tr>
                                    <td>${payment.payID}</td>
                                    <td>${payment.paymentDate}</td>
                                    <td>K ${parseFloat(payment.pmtAmt).toFixed(2)}</td>
                                    <td>${payment.paymentMode || 'Cash'}</td>
                                    <td>K ${parseFloat(payment.balance).toFixed(2)}</td>
                                    <td>${payment.remark || '-'}</td>
                                </tr>
                            `;
                            tbody.innerHTML += row;
                        });
                        previousPaymentsCard.style.display = 'block';
                    } else {
                        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No previous payments</td></tr>';
                        previousPaymentsCard.style.display = 'block';
                    }
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to fetch pupil details');
            });
    });
    
    // Calculate new balance when amount changes
    pmtAmtInput.addEventListener('input', function() {
        if (currentData) {
            const amountPaid = parseFloat(this.value) || 0;
            const newBalance = currentData.balance - amountPaid;
            document.getElementById('newBalance').textContent = newBalance.toFixed(2);
            balanceInput.value = newBalance.toFixed(2);
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
