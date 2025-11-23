# 使用官方PHP 7.4镜像并带Apache服务器
FROM php:7.4-apache

# 安装系统依赖和Composer
RUN apt-get update && \
    apt-get install -y git unzip && \
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# 安装PHP扩展
RUN docker-php-ext-install pdo pdo_mysql mysqli

# 将代码复制到容器内的网站根目录
COPY . /var/www/html/

# 安装Composer依赖（关键步骤！）
RUN cd /var/www/html && composer install --no-dev

# 设置正确的文件权限
RUN chown -R www-data:www-data /var/www/html/ && \
    find /var/www/html/ -type d -exec chmod 755 {} \; && \
    find /var/www/html/ -type f -exec chmod 644 {} \; && \
    chmod -R 777 /var/www/html/public/ /var/www/html/runtime/

# 暴露Apache服务器的默认端口
EXPOSE 80