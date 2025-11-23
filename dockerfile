# 使用官方PHP 7.4镜像并带Apache服务器
FROM php:7.4-apache

# 安装PHP连接MySQL所必需的扩展
RUN docker-php-ext-install pdo pdo_mysql mysqli

# 将当前目录下的所有文件复制到容器内的网站根目录
COPY . /var/www/html/

# 设置正确的文件权限（关键修正部分）
RUN chown -R www-data:www-data /var/www/html/ && \
    find /var/www/html/ -type d -exec chmod 755 {} \; && \
    find /var/www/html/ -type f -exec chmod 644 {} \;

# 为可能需要写入的目录（如runtime、uploads）单独设置写权限
RUN if [ -d "/var/www/html/runtime" ]; then chmod -R 777 /var/www/html/runtime; fi && \
    if [ -d "/var/www/html/uploads" ]; then chmod -R 777 /var/www/html/uploads; fi

# 暴露Apache服务器的默认端口
EXPOSE 80