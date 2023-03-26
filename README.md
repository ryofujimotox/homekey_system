
## Config設定

DB設定 ( `cakephp/config/app_honban.sample`参照 )

```
cakephp/config/bootstrap.php
cakephp/config/app_docker.php
cakephp/config/app_honban.php
```


初期データ設定 ( `cakephp/config/.env.sample`参照 )

```
cakephp/config/.env
```



## DB用意

DB構築

``` bash
cakephp/bin/cake migrations migrate
```

データ挿入

``` bash
cakephp/bin/cake migrations seed
```


## 実行

Docker実行

```
cd homekey_system
docker-compose up -d
```

Composer

```
docker-compose exec osphp bash
cd /var/www/cakephp
composer install
```

権限

```
chmod -R 777 /var/www/cakephp/tmp
chmod -R 777 /var/www/cakephp/logs
```

確認

http://localhost:8080/


