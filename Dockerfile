FROM php:7.4-apache

# 设置Apache
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# 安装扩展
RUN apt-get update && \
    apt-get install -y --no-install-recommends unzip && \
    docker-php-ext-install pdo pdo_mysql mysqli

# 复制代码
COPY . /var/www/html/

# 设置启动脚本（关键修复！）
RUN echo "#!/bin/bash\n\
chown -R www-data:www-data /var/www/html/\n\
chmod -R 755 /var/www/html/\n\
chmod -R 777 /var/www/html/runtime/ 2>/dev/null || true\n\
chmod 666 /var/www/html/.env 2>/dev/null || true\n\
apache2-foreground\n" > /docker-start.sh && \
chmod +x /docker-start.sh

# 设置启动命令（确保每次启动都应用权限）
CMD ["/docker-start.sh"]

EXPOSE 80