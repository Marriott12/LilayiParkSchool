# Payment Recording Fix - Deployment Instructions

## Problem Summary
Payments were not being recorded in the database due to:
1. **Missing columns**: Payment table lacked `feeID`, `term`, and `academicYear` columns that the code was trying to insert
2. **Primary Key issue**: BaseModel couldn't retrieve the auto-generated `payID` after insertion, returning 0 and causing the create operation to appear failed

## Solutions Implemented

### 1. BaseModel Enhancement
Added Payment table fallback logic to retrieve the generated `payID` after insertion by querying the most recent payment matching pupilID, paymentDate, and pmtAmt.

**File**: `includes/BaseModel.php`

### 2. Database Migration
Created migration to add missing columns to Payment table:
- `feeID` (VARCHAR(10), nullable) - Links payment to fee record
- `term` (INT, nullable) - Tracks which term the payment is for
- `academicYear` (VARCHAR(10), nullable) - Tracks academic year

**Migration File**: `database/migrations/add_fee_tracking_to_payments.sql`

### 3. Migration Scripts
Created three methods to apply the migration:
- `apply_payment_migration.php` - Web-based migration (easiest)
- `deploy_payment_migration.ps1` - PowerShell script for Windows
- `deploy_payment_migration.sh` - Bash script for Linux

## Deployment Steps

### For Production Server (lps.envisagezm.com)

#### Option A: Web-Based Migration (Recommended - Easiest)

1. **Upload files** to production server:
   ```bash
   scp production_deploy_2026-02-10_XXXXXX.zip envithcy@server219:/home/envithcy/lps.envisagezm.com/
   scp apply_payment_migration.php envithcy@server219:/home/envithcy/lps.envisagezm.com/
   scp database/migrations/add_fee_tracking_to_payments.sql envithcy@server219:/home/envithcy/lps.envisagezm.com/database/migrations/
   ```

2. **Extract deployment package**:
   ```bash
   ssh envithcy@server219
   cd /home/envithcy/lps.envisagezm.com
   unzip -o production_deploy_2026-02-10_XXXXXX.zip
   ```

3. **Run migration via browser**:
   - Visit: https://lps.envisagezm.com/apply_payment_migration.php
   - The script will show current table structure and apply the migration
   - Verify the output shows "Migration Complete"

4. **Delete migration script** (for security):
   ```bash
   rm apply_payment_migration.php
   ```

#### Option B: Command-Line Migration

1. **Upload files and extract** (same as Option A steps 1-2)

2. **Apply migration via MySQL**:
   ```bash
   ssh envithcy@server219
   cd /home/envithcy/lps.envisagezm.com
   mysql -u envithcy_lps -p envithcy_lps < database/migrations/add_fee_tracking_to_payments.sql
   ```

3. **Verify migration**:
   ```bash
   mysql -u envithcy_lps -p envithcy_lps -e "DESCRIBE Payment;"
   ```
   
   You should see the new columns: `feeID`, `term`, `academicYear`

### For Local Development (WAMP)

1. **Update local files** (already done if using git)

2. **Apply migration**:
   - Start WAMP services
   - Visit: http://localhost/LilayiParkSchool/apply_payment_migration.php
   - OR run via command line:
     ```powershell
     cd C:\wamp64\www\LilayiParkSchool
     mysql -u root -p test_lps < database/migrations/add_fee_tracking_to_payments.sql
     ```

## Verification

After deployment, test the payment recording:

1. Go to: https://lps.envisagezm.com/payments_form.php
2. Select a pupil
3. Enter payment amount
4. Submit the form
5. Go to: https://lps.envisagezm.com/payments_list.php
6. Verify the payment appears in the list

## Files Changed

- `includes/BaseModel.php` - Added Payment table fallback for ID retrieval
- `payments_form.php` - Restored feeID, term, academicYear to data array
- `database/migrations/add_fee_tracking_to_payments.sql` - New migration file
- `apply_payment_migration.php` - Web-based migration tool
- `deploy_payment_migration.ps1` - PowerShell migration script
- `deploy_payment_migration.sh` - Bash migration script

## Rollback (if needed)

If you need to undo the migration:

```sql
ALTER TABLE Payment 
DROP FOREIGN KEY fk_payment_fee;

ALTER TABLE Payment 
DROP INDEX idx_payment_fee,
DROP INDEX idx_payment_term,
DROP INDEX idx_payment_year;

ALTER TABLE Payment 
DROP COLUMN feeID,
DROP COLUMN term,
DROP COLUMN academicYear;
```

## Notes

- The migration is **backward compatible** - existing payment records will have NULL values for the new columns
- Future payments will automatically populate these columns with appropriate values
- The PaymentModel queries use LEFT JOIN on feeID, so NULL values won't cause errors
- BaseModel now properly handles trigger-generated VARCHAR primary keys for Payment table

## Support

If you encounter any issues:
1. Check the production error log: `/home/envithcy/lps.envisagezm.com/logs/php-errors.log`
2. Verify database credentials in `config/database.php`
3. Ensure WAMP/MySQL services are running for local testing
