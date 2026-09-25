FROM nginx:1.28-alpine
COPY docker/web/nginx.conf /etc/nginx/nginx.conf
COPY docker/web/default.conf.template /etc/nginx/templates/default.conf.template
COPY app/public/style.css /usr/share/nginx/html/style.css
ENV PHP_UPSTREAM=php:9000 \
    NGINX_ENVSUBST_OUTPUT_DIR=/tmp \
    NGINX_ENVSUBST_FILTER=PHP_UPSTREAM
USER nginx
EXPOSE 8080
CMD ["nginx", "-g", "daemon off;"]
