# 使用官方PHP 7.4镜像并带Apache服务器（根据教程要求选择版本，如教程无特殊要求，7.2+均可）
FROM php:7.4-apache

# 安装PHP连接MySQL所必需的扩展
RUN docker-php-ext-install pdo pdo_mysql mysqli

# 将当前目录下的所有文件复制到容器内的网站根目录
COPY . /var/www/html/

# 设置正确的文件权限（确保PHP可写入缓存等目录）
RUN chown -R www-data:www-data /var/www/html/ && chmod -R 755 /var/www/html/

# 暴露Apache服务器的默认端口
EXPOSE 80