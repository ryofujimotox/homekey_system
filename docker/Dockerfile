# CentOS 7.8
FROM centos:7
RUN yum update -y && yum clean all

# Repository
# EPEL
RUN yum install -y epel-release
# remi
RUN yum -y install http://rpms.famillecollet.com/enterprise/remi-release-7.rpm

# Install apache
RUN yum install -y httpd
# RUN mkdir -m 777 /var/www/logs/
# RUN mkdir -m 777 /var/www/cakephp/logs/

# Install PHP
RUN yum-config-manager --enable remi-php74
RUN yum -y install php php-devel php-mbstring php-pdo php-xml php-gd php-fpm php-mysqlnd php-opcache php-pecl-zip libzip5
RUN yum -y install php-bcmath php-intl php-imap 

RUN yum -y install composer yum-utils php-pear php-pecl-memcached php-pecl-memcache 

# Install Xdebug
RUN yum -y install git
RUN yum -y install php-pecl-imagick.x86_64
RUN yum -y install php-pecl-xdebug
RUN touch /var/log/xdebug.log && chmod a+w /var/log/xdebug.log

RUN yum -y install which
RUN yum -y install gcc wget ImageMagick mod_ssl openssh-clients memcached zip unzip
RUN yum -y install libwebp-tools


# MeCab
RUN yum install -y file
RUN yum install -y https://packages.groonga.org/centos/groonga-release-latest.noarch.rpm
RUN yum install -y mecab mecab-ipadic

# MeCab-neologd
#RUN yum install -y mecab-devel xz openssl bzip2 bzip2-devel gcc gcc-c++ make wget curl openssl-devel readline-devel zlib-devel git find patch file which
#RUN git clone --depth 1 https://github.com/neologd/mecab-ipadic-neologd.git
#RUN ./mecab-ipadic-neologd/bin/install-mecab-ipadic-neologd -n -y -p /opt/mecab/lib/mecab/dic/neologd


# Port
EXPOSE 80

# Httpd start
ENTRYPOINT ["/usr/sbin/httpd", "-DFOREGROUND"]
