FROM php:7.4-apache

# 设置Apache服务器名
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# 安装基础工具和扩展
RUN apt-get update && apt-get install -y git unzip && \
    docker-php-ext-install pdo pdo_mysql mysqli

# 复制代码到容器
COPY . /var/www/html/

# ========== 修复版权限设置 ==========
USER root

# 1. 设置所有权
RUN chown -R www-data:www-data /var/www/html/

# 2. 设置基础权限
RUN find /var/www/html/ -type d -exec chmod 755 {} \; && \
    find /var/www/html/ -type f -exec chmod 644 {} \;

# 3. 【关键修复】安全设置runtime目录权限（避免空目录错误）
RUN chmod 755 /var/www/html/runtime && \
    (ls /var/www/html/runtime/ | xargs chmod 777 2>/dev/null || true)

# 4. 设置.env文件权限
RUN chmod 666 /var/www/html/.env

# 健康检查
RUN echo "<?php header('Content-Type: text/plain'); echo 'OK'; ?>" > /var/www/html/health.php

USER www-data
EXPOSE 80