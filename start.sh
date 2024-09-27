#!/bin/sh

# Substitute environment variables in the Nginx config
envsubst '$FPM_HOST' < /etc/nginx/http.d/default.conf.template > /etc/nginx/http.d/default.conf

# Start PHP-FPM (foreground)
php-fpm &

# Start Nginx in foreground (daemon off is fine here)
nginx -g 'daemon off;'
