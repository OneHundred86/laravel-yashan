## 简介
yashandb driver implementation for Laravel

## 安装

```shell
composer require oh86/laravel-yashan
```

## 配置

1. 在 `config/app.php` 中添加:

```
'providers' => [
    ...
    Oh86\LaravelYashan\YSServiceProvider::class,
    ...
],
```

2. 配置env:

```
DB_CONNECTION=yashan
DB_HOST=172.21.49.185
DB_PORT=1688
DB_DATABASE=YHZX    # 模式
DB_USERNAME=xxx
DB_PASSWORD=xxx
```
