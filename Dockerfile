# 使用官方PHP 7.4镜像并带Apache服务器
FROM php:7.4-apache

# 设置Apache服务器名
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# 安装系统依赖和必要的开发库
RUN apt-get update && \
    apt-get install -y --no-install-recommends git unzip libonig-dev libcurl4-openssl-dev pkg-config && \
    apt-get clean

# 安装PHP扩展（先安装必需的系统库）
RUN docker-php-ext-install pdo pdo_mysql mysqli

# 复制代码到容器
COPY . /var/www/html/

# 设置文件所有权和基础权限
RUN chown -R www-data:www-data /var/www/html/ && \
    find /var/www/html/ -type d -exec chmod 755 {} \; && \
    find /var/www/html/ -type f -exec chmod 644 {} \;

# 设置runtime目录权限（如果存在）
RUN if [ -d "/var/www/html/runtime" ]; then chmod -R 777 /var/www/html/runtime/; fi

# 设置.env文件权限（如果存在）
RUN if [ -f "/var/www/html/.env" ]; then chmod 666 /var/www/html/.env; fi

# 健康检查
RUN echo "<?php header('Content-Type: text/plain'); echo 'OK'; ?>" > /var/www/html/health.php

EXPOSE 80