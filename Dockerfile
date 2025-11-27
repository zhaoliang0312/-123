# 最小化版本 - 只包含最基本的功能
FROM php:7.4-apache

# 复制代码
COPY . /var/www/html/

# 仅设置最基本的权限
RUN chown -R www-data:www-data /var/www/html/ && \
    chmod -R 755 /var/www/html/

# 暴露端口
EXPOSE 80