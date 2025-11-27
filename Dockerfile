# 使用官方PHP 7.4镜像并带Apache服务器
FROM php:7.4-apache

# 设置Apache服务器名，避免启动警告
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# 更新软件源并安装必要工具（使用国内镜像加速）
RUN sed -i 's/deb.debian.org/mirrors.aliyun.com/g' /etc/apt/sources.list && \
    sed -i 's/security.debian.org/mirrors.aliyun.com/g' /etc/apt/sources.list && \
    apt-get update && \
    apt-get install -y --no-install-recommends git unzip

# 安装PHP扩展
RUN docker-php-ext-install pdo pdo_mysql mysqli

# 复制代码到容器
COPY . /var/www/html/

# ========== 简化但有效的权限设置 ==========
# 设置文件所有权
RUN chown -R www-data:www-data /var/www/html/

# 设置基础权限（目录755，文件644）
RUN find /var/www/html/ -type d -exec chmod 755 {} \; && \
    find /var/www/html/ -type f -exec chmod 644 {} \;

# 单独设置关键目录权限（避免空目录错误）
RUN chmod 755 /var/www/html/runtime && \
    chmod 666 /var/www/html/.env

# 创建健康检查端点
RUN echo "<?php header('Content-Type: text/plain'); echo 'OK'; ?>" > /var/www/html/health.php

# 暴露端口
EXPOSE 80