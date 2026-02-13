#!/bin/bash
# Deploy Payment table migration to production
# This script applies the fee tracking columns migration to the Payment table

echo "========================================="
echo "Payment Table Migration Deployment"
echo "========================================="
echo ""

# Database credentials (update these for your environment)
DB_HOST="localhost"
DB_NAME="envithcy_lps"
DB_USER="envithcy_lps"

echo "⚠️  This will modify the Payment table structure"
echo "   Adding columns: feeID, term, academicYear"
echo ""
read -p "Continue? (yes/no): " confirm

if [ "$confirm" != "yes" ]; then
    echo "Migration cancelled."
    exit 0
fi

echo ""
echo "Applying migration..."
mysql -h "$DB_HOST" -u "$DB_USER" -p "$DB_NAME" < database/migrations/add_fee_tracking_to_payments.sql

if [ $? -eq 0 ]; then
    echo ""
    echo "✓ Migration applied successfully!"
    echo ""
    echo "Verifying changes..."
    mysql -h "$DB_HOST" -u "$DB_USER" -p "$DB_NAME" -e "DESCRIBE Payment;"
    echo ""
    echo "✓ Payment table updated successfully"
else
    echo ""
    echo "✗ Migration failed. Please check the error messages above."
    exit 1
fi
