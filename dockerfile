# 使用官方PHP 7.4镜像并带Apache服务器
FROM php:7.4-apache

# 配置国内Debian软件源并安装依赖
RUN sed -i 's/deb.debian.org/mirrors.aliyun.com/g' /etc/apt/sources.list && \
    sed -i 's/security.debian.org/mirrors.aliyun.com/g' /etc/apt/sources.list && \
    apt-get update && \
    apt-get install -y git unzip

# 使用国内镜像安装Composer
RUN curl -sS https://install.phpcomposer.com/installer | php -- --install-dir=/usr/local/bin --filename=composer && \
    composer config -g repo.packagist composer https://mirrors.aliyun.com/composer/

# 安装PHP扩展
RUN docker-php-ext-install pdo pdo_mysql mysqli

# 将代码复制到容器内的网站根目录
COPY . /var/www/html/

# 安装Composer依赖
RUN cd /var/www/html && composer install --no-dev

# 设置正确的文件权限（修正版：只针对实际存在的目录）
RUN chown -R www-data:www-data /var/www/html/ && \
    find /var/www/html/ -type d -exec chmod 755 {} \; && \
    find /var/www/html/ -type f -exec chmod 644 {} \; && \
    # 只设置实际存在的目录权限
    (test -d /var/www/html/runtime && chmod -R 777 /var/www/html/runtime || true) && \
    (test -d /var/www/html/public/runtime && chmod -R 777 /var/www/html/public/runtime || true) && \
    # 移除不存在的app/runtime目录
    # 设置.env文件权限（如果存在）
    (test -f /var/www/html/.env && chmod 666 /var/www/html/.env || true) && \
    (test -f /var/www/html/public/.env && chmod 666 /var/www/html/public/.env || true)

# 暴露Apache服务器的默认端口
EXPOSE 80