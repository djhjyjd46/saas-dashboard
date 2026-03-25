$SERVER_USER = "root"
$SERVER_IP = "178.172.235.254"
$REMOTE_PATH = "/var/www/voronov/data/www/analytics.stashevski.by"
$SSH_KEY = $env:USERPROFILE + "\.ssh\id_rsa_stashevski"

$script = @"
cd $REMOTE_PATH
/opt/php84/bin/php artisan tinker --execute="DB::table('deals')->whereNull('integration_id')->delete(); DB::table('leads')->whereNull('integration_id')->delete(); echo 'Deleted old deals and leads\n';"
"@

$script | Out-File -Encoding ASCII remote_script.sh
scp -i "$SSH_KEY" -o StrictHostKeyChecking=no remote_script.sh "${SERVER_USER}@${SERVER_IP}:/tmp/remote_script.sh"
ssh -i "$SSH_KEY" -o StrictHostKeyChecking=no "${SERVER_USER}@${SERVER_IP}" "bash /tmp/remote_script.sh"
