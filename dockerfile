# 使用官方PHP 7.4镜像并带Apache服务器（根据教程要求选择版本，如教程无特殊要求，7.2+均可）
FROM php:7.4-apache

# 安装PHP连接MySQL所必需的扩展
RUN docker-php-ext-install pdo pdo_mysql mysqli

# 复制代码
COPY . /var/www/html/

# 设置正确的所有权和权限（关键步骤）
RUN chown -R www-data:www-data /var/www/html/ && \
    find /var/www/html/ -type d -exec chmod 755 {} \; && \
    find /var/www/html/ -type f -exec chmod 644 {} \; && \
    chmod 755 /var/www/html/install.php  # 确保安装文件有执行权限

# 暴露Apache服务器的默认端口
EXPOSE 80