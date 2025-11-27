FROM php:7.4-apache

# 设置Apache服务器名
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# 安装基础工具和扩展
RUN apt-get update && apt-get install -y git unzip && \
    docker-php-ext-install pdo pdo_mysql mysqli

# 复制代码到容器（后端文件夹内容对应容器内的/var/www/html/）
COPY . /var/www/html/

# ========== 精准权限设置（基于您的项目结构） ==========
USER root

# 1. 设置整个目录的所有权
RUN chown -R www-data:www-data /var/www/html/

# 2. 设置安全的目录/文件权限
RUN find /var/www/html/ -type d -exec chmod 755 {} \; && \
    find /var/www/html/ -type f -exec chmod 644 {} \;

# 3. 【关键修正】精准设置根目录下的runtime文件夹权限
RUN chmod 755 /var/www/html/runtime && \
    chmod -R 777 /var/www/html/runtime/*

# 4. 【关键修正】精准设置根目录下的.env文件权限
RUN chmod 666 /var/www/html/.env

# 健康检查
RUN echo "<?php header('Content-Type: text/plain'); echo 'OK'; ?>" > /var/www/html/health.php

# 切换回Apache用户
USER www-data

EXPOSE 80