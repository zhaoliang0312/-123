FROM php:7.4-apache

# 设置Apache服务器名
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# 安装基础工具
RUN apt-get update && apt-get install -y git unzip

# 安装PHP扩展
RUN docker-php-ext-install pdo pdo_mysql mysqli

# 复制代码
COPY . /var/www/html/

# ========== 终极权限解决方案 ==========
# 1. 设置整个目录为完全可写（开发环境适用）
RUN chmod -R 777 /var/www/html/

# 2. 确保Apache用户有所有权
RUN chown -R www-data:www-data /var/www/html/

# 健康检查
RUN echo "<?php header('Content-Type: text/plain'); echo 'OK'; ?>" > /var/www/html/health.php

EXPOSE 80