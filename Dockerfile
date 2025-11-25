FROM php:7.4-apache

# 修改Apache端口为8080以适应云托管
RUN sed -i 's/80/8080/g' /etc/apache2/ports.conf && \
    sed -i 's/80/8080/g' /etc/apache2/sites-available/*.conf

# 安装PHP扩展
RUN docker-php-ext-install pdo pdo_mysql mysqli

# 复制代码
COPY . /var/www/html/

# 设置文件权限（核心修复）
RUN chown -R www-data:www-data /var/www/html/ && \
    find /var/www/html/ -type d -exec chmod 755 {} \; && \
    find /var/www/html/ -type f -exec chmod 644 {} \; && \
    chmod -R 777 /var/www/html/runtime/ /var/www/html/app/runtime/ /var/www/html/public/runtime/ 2>/dev/null || true && \
    chmod 666 /var/www/html/.env /var/www/html/app/.env /var/www/html/public/.env 2>/dev/null || true

EXPOSE 8080