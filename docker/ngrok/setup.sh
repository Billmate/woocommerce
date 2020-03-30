#!/bin/bash

# Wait for the db to be available
wait-for-it.sh ${WORDPRESS_DB_HOST}:3306

# Make sure that the upstream entrypoint does not call exec
# TODO: Fix this in a pull request to docker-library/wordpress
sed -i '/exec "$@"/d' /usr/local/bin/docker-entrypoint.sh

# Run the entrypoint script form the wordpress image
docker-entrypoint.sh "$@"

# Symlink the billmate plugin to wp-content/plugins
ln -s /usr/src/billmate /var/www/html/wp-content/plugins

# Complete the wp installation and activate woocommerce and billmate-payment-gateway
wp core install --allow-root --url=${ngrok_url:='localhost'} --title=${WP_TITLE:='Test-title'} --admin_user=${WP_ADMIN_USER:='test'} --admin_email=${WP_ADMIN_EMAIL:='test-admin@test.se'} --admin_password=${WP_ADMIN_PASSWORD:='test'} --skip-email
wp plugin delete akismet --allow-root
wp plugin delete hello --allow-root
wp plugin activate --all --allow-root

# Get the public ngrok address and set wp_url to this value
ngrok_url=$(php /usr/local/bin/PublicUrl.php)
echo "Public URL is: $ngrok_url"

# Update home and site url to the public ngrok url
wp option update home "$ngrok_url" --allow-root
wp option update siteurl "$ngrok_url" --allow-root

exec "$@"
