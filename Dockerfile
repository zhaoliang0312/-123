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

# ==================== 强化的权限设置 ====================
# 设置文件所有权和基本权限
RUN chown -R www-data:www-data /var/www/html/ && \
    find /var/www/html/ -type d -exec chmod 755 {} \; && \
    find /var/www/html/ -type f -exec chmod 644 {} \;

# 强制设置runtime目录的可写权限（递归设置）
RUN find /var/www/html/ -name "runtime" -type d -exec chmod -R 777 {} \; 2>/dev/null || true

# 强制设置.env文件的可写权限
RUN find /var/www/html/ -name ".env" -type f -exec chmod 666 {} \; 2>/dev/null || true

# 确保Apache有足够的权限
RUN usermod -a -G www-data root

# 暴露标准HTTP端口
EXPOSE 80