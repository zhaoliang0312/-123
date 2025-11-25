FROM php:7.4-apache

# 设置Apache服务器名
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# 安装基础工具
RUN apt-get update && apt-get install -y git unzip

# 安装PHP扩展
RUN docker-php-ext-install pdo pdo_mysql mysqli

# 复制代码
COPY . /var/www/html/

# ==================== 强化的权限解决方案 ====================
# 1. 递归设置整个目录的所有权
RUN chown -R www-data:www-data /var/www/html/

# 2. 设置基础权限
RUN find /var/www/html/ -type d -exec chmod 755 {} \; && \
    find /var/www/html/ -type f -exec chmod 644 {} \;

# 3. 为所有可能的runtime目录设置完全权限
RUN find /var/www/html/ -name "runtime" -type d -exec chmod -R 777 {} \; 2>/dev/null || true && \
    find /var/www/html/ -path "*/runtime" -type d -exec chmod -R 777 {} \; 2>/dev/null || true

# 4. 为.env文件设置写权限
RUN find /var/www/html/ -name ".env" -type f -exec chmod 666 {} \; 2>/dev/null || true

# 5. 确保Apache用户可以写入所有目录
RUN usermod -a -G www-data root

# 健康检查端点
RUN echo "<?php header('Content-Type: text/plain'); echo 'OK'; ?>" > /var/www/html/health.php

EXPOSE 80