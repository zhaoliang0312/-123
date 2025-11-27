FROM php:7.4-apache

# 设置Apache服务器名
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# 安装基础工具
RUN apt-get update && apt-get install -y git unzip

# 安装PHP扩展
RUN docker-php-ext-install pdo pdo_mysql mysqli

# 复制代码
COPY . /var/www/html/

# ========== 核心修复：强制的权限设置 ==========
# 1. 确保使用root权限
USER root

# 2. 递归设置整个目录所有权
RUN chown -R www-data:www-data /var/www/html/

# 3. 设置基础权限
RUN find /var/www/html/ -type d -exec chmod 755 {} \; && \
    find /var/www/html/ -type f -exec chmod 644 {} \;

# 4. 强制创建并设置runtime目录权限（确保目录存在）
RUN mkdir -p /var/www/html/runtime /var/www/html/public/runtime && \
    chmod -R 777 /var/www/html/runtime/ /var/www/html/public/runtime/

# 5. 强制设置.env文件权限（如不存在则创建）
RUN touch /var/www/html/.env && \
    chmod 666 /var/www/html/.env

# 6. 切换到Apache用户运行
USER www-data

# 健康检查
RUN echo "<?php header('Content-Type: text/plain'); echo 'OK'; ?>" > /var/www/html/health.php

EXPOSE 80