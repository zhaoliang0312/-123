# 使用官方PHP 7.4镜像并带Apache服务器
FROM php:7.4-apache

# 设置Apache服务器名
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# 安装系统依赖和所有必需的PHP扩展
RUN apt-get update && \
    apt-get install -y --no-install-recommends git unzip && \
    docker-php-ext-install pdo pdo_mysql mysqli mbstring curl

# 复制代码到容器
COPY . /var/www/html/

# ========== 完整的权限和配置设置 ==========
# 设置文件所有权
RUN chown -R www-data:www-data /var/www/html/

# 设置基础权限
RUN find /var/www/html/ -type d -exec chmod 755 {} \; && \
    find /var/www/html/ -type f -exec chmod 644 {} \;

# 安全设置runtime目录权限（如果目录存在）
RUN if [ -d "/var/www/html/runtime" ]; then chmod -R 777 /var/www/html/runtime/; fi

# 安全设置.env文件权限（如果文件存在）
RUN if [ -f "/var/www/html/.env" ]; then chmod 666 /var/www/html/.env; fi

# 如果.env不存在，创建示例文件
RUN if [ ! -f "/var/www/html/.env" ]; then \
    touch /var/www/html/.env && \
    chmod 666 /var/www/html/.env; \
    fi

# 创建健康检查端点
RUN echo "<?php header('Content-Type: text/plain'); echo 'OK'; ?>" > /var/www/html/health.php

EXPOSE 80