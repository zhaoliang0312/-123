# 使用官方PHP 7.4镜像并带Apache服务器
FROM php:7.4-apache

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

# ==================== 终极权限解决方案 ====================
# 1. 确保使用正确的用户
USER root

# 2. 设置整个目录的所有权给Apache用户
RUN chown -R www-data:www-data /var/www/html/

# 3. 设置目录和文件的基本权限
RUN find /var/www/html/ -type d -exec chmod 755 {} \; && \
    find /var/www/html/ -type f -exec chmod 644 {} \;

# 4. 为所有可能的runtime目录设置完全权限（覆盖所有情况）
RUN find /var/www/html/ -name "runtime" -type d -exec chmod -R 777 {} \; 2>/dev/null || true && \
    find /var/www/html/ -path "*/runtime" -type d -exec chmod -R 777 {} \; 2>/dev/null || true

# 5. 为所有可能的.env文件设置写权限
RUN find /var/www/html/ -name ".env" -type f -exec chmod 666 {} \; 2>/dev/null || true

# 6. 为可能需要的上传目录设置权限
RUN find /var/www/html/ -name "uploads" -type d -exec chmod -R 777 {} \; 2>/dev/null || true && \
    find /var/www/html/ -name "cache" -type d -exec chmod -R 777 {} \; 2>/dev/null || true

# 7. 切换到Apache用户运行
USER www-data

# 暴露端口
EXPOSE 80