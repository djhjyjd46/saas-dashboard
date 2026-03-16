# --- Configuration ---
$SERVER_USER = "root"
$SERVER_IP = "178.172.235.254"
$REMOTE_PATH = "/var/www/voronov/data/www/analytics.stashevski.by"
$SSH_KEY = $env:USERPROFILE + "\.ssh\id_rsa_stashevski"

Write-Output "Starting deployment of SaaS Dashboard to $SERVER_IP..."

# 1. Build Frontend
Write-Output "Building frontend (Vite)..."
npm run build
if ($LASTEXITCODE -ne 0) { exit }

# 2. Create Archive
Write-Output "Creating archive..."
$ArchiveName = "dist.zip"
if (Test-Path $ArchiveName) { Remove-Item $ArchiveName }

# Use tar.exe which handles locked files better than Compress-Archive
# We use -a to auto-detect zip format from extension
tar.exe -acf $ArchiveName --exclude=vendor --exclude=node_modules --exclude=.env --exclude=.git --exclude=.github --exclude=storage --exclude="*.zip" --exclude=deploy.ps1 --exclude=deploy_server.sh --exclude=tests *

if (!(Test-Path $ArchiveName)) {
    Write-Output "ERROR: Failed to create archive."
    exit
}

# 3. Upload to Server
Write-Output "Uploading files to server..."
$sshTarget = "${SERVER_USER}@${SERVER_IP}:${REMOTE_PATH}"

scp -i "$SSH_KEY" -o StrictHostKeyChecking=no "$ArchiveName" "$sshTarget"
scp -i "$SSH_KEY" -o StrictHostKeyChecking=no "deploy_server.sh" "$sshTarget"
scp -i "$SSH_KEY" -o StrictHostKeyChecking=no ".env" "$sshTarget/.env"

if ($LASTEXITCODE -ne 0) {
    Write-Output "ERROR: SCP Upload failed."
    exit
}

# 4. Remote commands
Write-Output "Running remote setup..."
$sshAuth = "${SERVER_USER}@${SERVER_IP}"
ssh -i "$SSH_KEY" -o StrictHostKeyChecking=no $sshAuth "bash ${REMOTE_PATH}/deploy_server.sh ${REMOTE_PATH} && rm ${REMOTE_PATH}/deploy_server.sh"

if ($LASTEXITCODE -ne 0) {
    Write-Output "ERROR: Remote setup failed."
    exit
}

# 5. Cleanup local
if (Test-Path $ArchiveName) { Remove-Item $ArchiveName }

Write-Output "SUCCESS: Deployment completed successfully!"
