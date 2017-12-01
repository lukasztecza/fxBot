#!/usr/bin/env bash

# Set versions
APACHE_VERSION=2.4.7*
HOST=localhost
PORT=8080
MYSQL_VERSION=5.5
MYSQL_ROOT_PASSWORD=pass
MYSQL_USER=user
MYSQL_USER_PASSWORD=pass
MYSQL_HOST=localhost
MYSQL_DATABASE=tiny_app
PHP_VERSION=7.1

# Export variable to fix "dpkg-preconfigure: unable to re-open..." error
export DEBIAN_FRONTEND=noninteractive

# Add ondrej php repository
sudo add-apt-repository ppa:ondrej/php
apt-get update

# Install basic tools
apt-get install -y vim curl

# Install apache
apt-get install -y apache2="$APACHE_VERSION"

# Create symlink from default apache web dir to /vagrant
if ! [ -L /var/www/html ]; then
    rm -rf /var/www
    mkdir /var/www
    ln -fs /vagrant /var/www/html
fi

# Enable mod_rewrite for apache
a2enmod rewrite

# Enable mod_headers for apache
a2enmod headers

# Set mysql answers and install mysql-server and mysql-client
debconf-set-selections <<< "mysql-server mysql-server/root_password password $MYSQL_ROOT_PASSWORD"
debconf-set-selections <<< "mysql-server mysql-server/root_password_again password $MYSQL_ROOT_PASSWORD"
apt-get install -y mysql-server-"$MYSQL_VERSION" mysql-client-"$MYSQL_VERSION"

# Set key_buffer_size to fix "Using unique option prefix key_buffer instead of key_buffer_size..." warning
if ! fgrep key_buffer_size /etc/mysql/my.cnf; then
    echo 'key_buffer_size = 16M' | sudo tee -a /etc/mysql/my.cnf
fi

# Install php and modules
apt-get install -y php"$PHP_VERSION" php"$PHP_VERSION"-curl php"$PHP_VERSION"-mysql php"$PHP_VERSION"-gd

# Display all errors for php
sed -i "s/error_reporting = .*/error_reporting = E_ALL/" /etc/php/"$PHP_VERSION"/apache2/php.ini
sed -i "s/display_errors = .*/display_errors = On/" /etc/php/"$PHP_VERSION"/apache2/php.ini

# Allow large file uploads
sed -i "s/memory_limit = .*/memory_limit = 32M/" /etc/php/"$PHP_VERSION"/apache2/php.ini
sed -i "s/upload_max_filesize = .*/upload_max_filesize = 16M/" /etc/php/"$PHP_VERSION"/apache2/php.ini
sed -i "s/post_max_size = .*/post_max_size = 24M/" /etc/php/"$PHP_VERSION"/apache2/php.ini

# Set php upload tmp directory
sed -i "s/;upload_tmp_dir =/upload_tmp_dir = \/vagrant\/tmp\/upload/" /etc/php/"$PHP_VERSION"/apache2/php.ini

# Allow usage of .htaccess files inside /var/www/html
if ! fgrep "/var/www/html" /etc/apache2/apache2.conf; then
    cat >> /etc/apache2/apache2.conf <<EOL
ServerName $HOST
Listen $PORT
<VirtualHost *:$PORT>
    DocumentRoot "/var/www/html/public/"
    <Directory "/var/www/html/public/">
        DirectoryIndex app.php
        AllowOverride All
        Order Allow,Deny
        Allow from All
        Require all granted
    </Directory>
    <Directory "/var/www/html/public/assets/">
        <IfModule mod_rewrite.c>
            RewriteEngine Off
        </IfModule>
    </Directory>
</VirtualHost>
EOL
fi

# Set up database (note no space after -p)
mysql -u root -p"$MYSQL_ROOT_PASSWORD" <<EOL
CREATE DATABASE IF NOT EXISTS $MYSQL_DATABASE CHARACTER SET utf8 COLLATE utf8_general_ci;
GRANT ALL PRIVILEGES ON $MYSQL_DATABASE.* TO $MYSQL_USER@$MYSQL_HOST IDENTIFIED BY '$MYSQL_USER_PASSWORD';
FLUSH PRIVILEGES;
EOL

if [ $? != "0" ]; then
    echo "[Error]: Database creation failed. Aborting."
    exit 1
fi

# Create tables needed by tinyCms
mysql -u "$MYSQL_USER" -p"$MYSQL_USER_PASSWORD" -h $MYSQL_HOST $MYSQL_DATABASE <<"EOL"
CREATE TABLE IF NOT EXISTS `items` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(16) COLLATE utf8_general_ci NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
CREATE TABLE IF NOT EXISTS `files` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) COLLATE utf8_general_ci NOT NULL,
    `type` TINYINT(4) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
EOL

# Restart apache
service apache2 restart

# @TODO create parameters.json here

echo "[Info] Your project will be accessible via url: http://$HOST:$PORT"
