# Deploy Payment Table Migration
# This script applies the fee tracking columns migration to the Payment table

Write-Host "=========================================" -ForegroundColor Cyan
Write-Host "Payment Table Migration Deployment" -ForegroundColor Cyan
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host ""

Write-Host "⚠️  This will modify the Payment table structure" -ForegroundColor Yellow
Write-Host "   Adding columns: feeID, term, academicYear" -ForegroundColor Yellow
Write-Host ""

# Database configuration
$dbHost = "localhost"
$dbName = "test_lps"  # Update this for your database
$dbUser = "root"       # Update this for your database

# Path to migration file
$migrationFile = "database\migrations\add_fee_tracking_to_payments.sql"

if (-not (Test-Path $migrationFile)) {
    Write-Host "✗ Migration file not found: $migrationFile" -ForegroundColor Red
    exit 1
}

$confirm = Read-Host "Continue with migration? (yes/no)"

if ($confirm -ne "yes") {
    Write-Host "Migration cancelled." -ForegroundColor Yellow
    exit 0
}

Write-Host ""
Write-Host "Applying migration..." -ForegroundColor Green

# Read migration SQL
$migrationSQL = Get-Content $migrationFile -Raw

try {
    # Execute migration
    mysql -h $dbHost -u $dbUser -p $dbName -e $migrationSQL
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host ""
        Write-Host "✓ Migration applied successfully!" -ForegroundColor Green
        Write-Host ""
        Write-Host "Verifying changes..." -ForegroundColor Cyan
        mysql -h $dbHost -u $dbUser -p $dbName -e "DESCRIBE Payment;"
        Write-Host ""
        Write-Host "✓ Payment table updated successfully" -ForegroundColor Green
    } else {
        throw "MySQL command failed"
    }
} catch {
    Write-Host ""
    Write-Host "✗ Migration failed: $_" -ForegroundColor Red
    Write-Host "Please check your database credentials and try again." -ForegroundColor Red
    exit 1
}
