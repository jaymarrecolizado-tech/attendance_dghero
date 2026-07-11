# PowerShell script to copy files for Hostinger migration
# Run this from the project root: .\hostinger_migrate\copy_files.ps1

$source = "C:\wamp64\www\isspsolo"
$dest = "C:\wamp64\www\isspsolo\hostinger_migrate"

Write-Host "Copying files for Hostinger migration..."

# Copy public/ contents to root (for Hostinger public_html)
Write-Host "Copying public/ contents to root..."
Copy-Item -Path "$source\public\*" -Destination "$dest\" -Recurse -Force

# Copy config folder
Write-Host "Copying config/..."
Copy-Item -Path "$source\config\*" -Destination "$dest\config\" -Recurse -Force

# Copy src folder
Write-Host "Copying src/..."
Copy-Item -Path "$source\src\*" -Destination "$dest\src\" -Recurse -Force

# Copy views folder
Write-Host "Copying views/..."
Copy-Item -Path "$source\views\*" -Destination "$dest\views\" -Recurse -Force

# Copy migrations folder
Write-Host "Copying migrations/..."
Copy-Item -Path "$source\migrations\*" -Destination "$dest\migrations\" -Recurse -Force

# Copy scripts folder
Write-Host "Copying scripts/..."
Copy-Item -Path "$source\scripts\*" -Destination "$dest\scripts\" -Recurse -Force

# Copy vendor folder (composer dependencies)
Write-Host "Copying vendor/..."
Copy-Item -Path "$source\vendor\*" -Destination "$dest\vendor\" -Recurse -Force

# Copy composer files
Write-Host "Copying composer files..."
Copy-Item -Path "$source\composer.json" -Destination "$dest\" -Force
Copy-Item -Path "$source\composer.lock" -Destination "$dest\" -Force -ErrorAction SilentlyContinue

# Create .htaccess files for storage protection
Write-Host "Creating storage .htaccess files..."
Set-Content -Path "$dest\storage\qrcodes\.htaccess" -Value "Require all denied"
Set-Content -Path "$dest\storage\signatures\.htaccess" -Value "Require all denied"
Set-Content -Path "$dest\storage\imports\.htaccess" -Value "Require all denied"
Set-Content -Path "$dest\storage\runtime\.htaccess" -Value "Require all denied"

# Create index.php placeholder files to prevent directory listing
Set-Content -Path "$dest\storage\index.php" -Value "<?php // Directory access denied"
Set-Content -Path "$dest\storage\qrcodes\index.php" -Value "<?php // Directory access denied"
Set-Content -Path "$dest\storage\signatures\index.php" -Value "<?php // Directory access denied"
Set-Content -Path "$dest\storage\imports\index.php" -Value "<?php // Directory access denied"
Set-Content -Path "$dest\storage\runtime\index.php" -Value "<?php // Directory access denied"

Write-Host "File copying completed!"
Write-Host "Next steps:"
Write-Host "1. Review and update env.example -> .env with your credentials"
Write-Host "2. Upload all files to Hostinger public_html"
Write-Host "3. Follow DEPLOYMENT.md instructions"

