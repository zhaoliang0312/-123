# 使用官方PHP 7.4镜像并带Apache服务器
FROM php:7.4-apache

# 1. 配置国内Debian软件源并安装依赖
RUN sed -i 's/deb.debian.org/mirrors.aliyun.com/g' /etc/apt/sources.list && \
    sed -i 's/security.debian.org/mirrors.aliyun.com/g' /etc/apt/sources.list && \
    apt-get update && \
    apt-get install -y git unzip

# 2. 使用国内镜像安装Composer
RUN curl -sS https://install.phpcomposer.com/installer | php -- --install-dir=/usr/local/bin --filename=composer && \
    composer config -g repo.packagist composer https://mirrors.aliyun.com/composer/

# 安装PHP扩展
RUN docker-php-ext-install pdo pdo_mysql mysqli

# 将代码复制到容器内的网站根目录
COPY . /var/www/html/

# 设置正确的文件权限（修正版）
RUN chown -R www-data:www-data /var/www/html/ && \
    find /var/www/html/ -type d -exec chmod 755 {} \; && \
    find /var/www/html/ -type f -exec chmod 644 {} \; && \
    # 特别设置关键目录的写权限
    chmod -R 777 /var/www/html/runtime/ /var/www/html/public/runtime/ /var/www/html/app/runtime/ && \
    # 处理.env文件（如果存在则设置权限，不存在则忽略）
    (test -f /var/www/html/.env && chmod 666 /var/www/html/.env || true) && \
    (test -f /var/www/html/public/.env && chmod 666 /var/www/html/public/.env || true)
# 暴露Apache服务器的默认端口
EXPOSE 80