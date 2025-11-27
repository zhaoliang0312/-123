FROM php:7.4-apache

# 设置Apache服务器名
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# 安装基础工具和扩展
RUN apt-get update && apt-get install -y git unzip && \
    docker-php-ext-install pdo pdo_mysql mysqli

# 复制代码
COPY . /var/www/html/

# ========== 精准权限设置 ==========
# 1. 设置正确的所有权
RUN chown -R www-data:www-data /var/www/html/

# 2. 设置安全的目录/文件权限
RUN find /var/www/html/ -type d -exec chmod 755 {} \; && \
    find /var/www/html/ -type f -exec chmod 644 {} \;

# 3. 精准设置runtime目录权限（仅针对特定目录）
RUN if [ -d "/var/www/html/runtime" ]; then chmod 755 /var/www/html/runtime; fi && \
    if [ -d "/var/www/html/runtime" ]; then chmod -R 777 /var/www/html/runtime/* 2>/dev/null || true; fi && \
    if [ -d "/var/www/html/app/runtime" ]; then chmod -R 777 /var/www/html/app/runtime/* 2>/dev/null || true; fi && \
    if [ -d "/var/www/html/public/runtime" ]; then chmod -R 777 /var/www/html/public/runtime/* 2>/dev/null || true; fi

# 4. 精准设置.env文件权限
RUN if [ -f "/var/www/html/.env" ]; then chmod 644 /var/www/html/.env; fi

# 健康检查
RUN echo "<?php header('Content-Type: text/plain'); echo 'OK'; ?>" > /var/www/html/health.php

EXPOSE 80