# PowerShell script to prepare files for manual production upload
# Creates a deployment folder with only the critical files

$deployFolder = "C:\wamp64\www\LilayiParkSchool\DEPLOY_PACKAGE"

# Create deployment folder
if (Test-Path $deployFolder) {
    Remove-Item -Path $deployFolder -Recurse -Force
}
New-Item -ItemType Directory -Path $deployFolder | Out-Null
New-Item -ItemType Directory -Path "$deployFolder\includes" | Out-Null

# Copy critical files
Write-Host "Copying critical files to deployment package..."

Copy-Item "C:\wamp64\www\LilayiParkSchool\includes\Session.php" "$deployFolder\includes\Session.php"
Write-Host "✓ includes/Session.php"

Copy-Item "C:\wamp64\www\LilayiParkSchool\includes\Auth.php" "$deployFolder\includes\Auth.php"
Write-Host "✓ includes/Auth.php"

Copy-Item "C:\wamp64\www\LilayiParkSchool\login.php" "$deployFolder\login.php"
Write-Host "✓ login.php"

Copy-Item "C:\wamp64\www\LilayiParkSchool\index.php" "$deployFolder\index.php"
Write-Host "✓ index.php"

Copy-Item "C:\wamp64\www\LilayiParkSchool\payments_list.php" "$deployFolder\payments_list.php"
Write-Host "✓ payments_list.php"

Write-Host "`nDeployment package created at: $deployFolder"
Write-Host "`nUpload these files to production via cPanel File Manager:"
Write-Host "1. Login to cPanel at https://lps.envisagezm.com:2083"
Write-Host "2. Open File Manager → public_html"
Write-Host "3. Upload files maintaining folder structure (includes/ folder)"
Write-Host ""
Write-Host "Press any key to open deployment folder..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
explorer $deployFolder
