# 使用官方PHP 7.4镜像并带Apache服务器
FROM php:7.4-apache

# 配置Apache服务器名称，避免启动警告
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# 配置国内软件源加速安装
RUN sed -i 's/deb.debian.org/mirrors.aliyun.com/g' /etc/apt/sources.list && \
    sed -i 's/security.debian.org/mirrors.aliyun.com/g' /etc/apt/sources.list && \
    apt-get update && \
    apt-get install -y git unzip

# 使用国内镜像安装Composer
RUN curl -sS https://install.phpcomposer.com/installer | php -- --install-dir=/usr/local/bin --filename=composer && \
    composer config -g repo.packagist composer https://mirrors.aliyun.com/composer/

# 安装PHP扩展
RUN docker-php-ext-install pdo pdo_mysql mysqli

# 复制代码到容器
COPY . /var/www/html/

# 安装Composer依赖
RUN cd /var/www/html && composer install --no-dev

# ==================== 强化的权限设置 ====================
# 设置文件所有权
RUN chown -R www-data:www-data /var/www/html/

# 设置基本权限
RUN find /var/www/html/ -type d -exec chmod 755 {} \; && \
    find /var/www/html/ -type f -exec chmod 644 {} \;

# 强制设置关键目录的可写权限
RUN find /var/www/html/ -name "runtime" -type d -exec chmod -R 777 {} \; 2>/dev/null || true && \
    find /var/www/html/ -name ".env" -type f -exec chmod 666 {} \; 2>/dev/null || true

# 创建健康检查文件（解决健康检查失败）
RUN echo "<?php header('Content-Type: text/plain'); echo 'OK'; ?>" > /var/www/html/health.php && \
    chmod 644 /var/www/html/health.php

# 暴露端口
EXPOSE 80