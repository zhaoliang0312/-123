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

# ==================== 强化的权限设置（核心修复部分）====================
# 创建可能缺失的运行时目录
RUN mkdir -p /var/www/html/runtime /var/www/html/public/runtime /var/www/html/app/runtime

# 设置所有文件和目录的基本权限
RUN chown -R www-data:www-data /var/www/html/ && \
    find /var/www/html/ -type d -exec chmod 755 {} \; && \
    find /var/www/html/ -type f -exec chmod 644 {} \;

# 强制设置关键目录的写权限（递归设置，确保所有子目录都可写）
RUN find /var/www/html/ -name "runtime" -type d -exec chmod -R 777 {} \; 2>/dev/null || true

# 强制设置.env文件的写权限（如果存在）
RUN find /var/www/html/ -name ".env" -type f -exec chmod 666 {} \; 2>/dev/null || true

# 额外确保public目录下的runtime也可写
RUN chmod -R 777 /var/www/html/public/runtime/ 2>/dev/null || true

# 暴露端口
EXPOSE 80