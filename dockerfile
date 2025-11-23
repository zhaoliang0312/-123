# 使用官方PHP 7.4镜像并带Apache服务器
FROM php:7.4-apache

# 配置国内软件源并安装依赖
RUN sed -i 's/deb.debian.org/mirrors.aliyun.com/g' /etc/apt/sources.list && \
    sed -i 's/security.debian.org/mirrors.aliyun.com/g' /etc/apt/sources.list && \
    apt-get update && \
    apt-get install -y git unzip

# 使用国内镜像安装Composer
RUN curl -sS https://install.phpcomposer.com/installer | php -- --install-dir=/usr/local/bin --filename=composer && \
    composer config -g repo.packagist composer https://mirrors.aliyun.com/composer/

# 安装PHP扩展
RUN docker-php-ext-install pdo pdo_mysql mysqli

# 创建必要的运行时目录（优先于文件复制）
RUN mkdir -p /var/www/html/runtime /var/www/html/public/runtime

# 复制项目文件（排除无需部署的目录）
COPY . /var/www/html/

# 安装Composer依赖
RUN cd /var/www/html && composer install --no-dev

# 精准设置文件权限（关键修复）
RUN chown -R www-data:www-data /var/www/html/ && \
    find /var/www/html/ -type d -exec chmod 755 {} \; && \
    find /var/www/html/ -type f -exec chmod 644 {} \; && \
    chmod -R 777 /var/www/html/runtime/ /var/www/html/public/runtime/ && \
    (find /var/www/html/ -name ".env" -exec chmod 644 {} \; || true)

# 暴露Apache端口
EXPOSE 80