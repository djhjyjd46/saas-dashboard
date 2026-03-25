cd /var/www/voronov/data/www/analytics.stashevski.by
/opt/php84/bin/php artisan tinker --execute="DB::table('deals')->whereNull('integration_id')->delete(); DB::table('leads')->whereNull('integration_id')->delete(); echo 'Deleted old deals and leads\n';"
