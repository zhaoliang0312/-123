FROM php:7.4-apache

# 设置Apache服务器配置
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# 安装必要的扩展和工具
RUN apt-get update && \
    apt-get install -y --no-install-recommends unzip && \
    docker-php-ext-install pdo pdo_mysql mysqli

# 复制代码到容器
COPY . /var/www/html/

# ========== 关键修复：创建启动脚本 ==========
# 创建启动脚本，确保每次容器启动时都设置正确的权限
RUN echo "#!/bin/bash\n\
# 设置文件所有权\n\
chown -R www-data:www-data /var/www/html/\n\
# 设置基础权限\n\
find /var/www/html/ -type d -exec chmod 755 {} \\;\n\
find /var/www/html/ -type f -exec chmod 644 {} \\;\n\
# 设置runtime目录权限\n\
chmod -R 777 /var/www/html/runtime/ 2>/dev/null || true\n\
# 设置.env文件权限\n\
chmod 666 /var/www/html/.env 2>/dev/null || true\n\
# 启动Apache（保持前台运行）\n\
exec apache2-foreground\n" > /docker-start.sh && \
chmod +x /docker-start.sh

# 使用自定义启动脚本
CMD ["/docker-start.sh"]

EXPOSE 80