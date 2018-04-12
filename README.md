# php-http-proxy
HTTP proxy written in PHP based on workerman.

这是一个用php写的http的代理,支持设置用户名密码

# authorization setting
edit start.php,find $user and $pass,change your own value.

设置方式：编辑start.php，找到$user  $pass  $ports，分别改成自己想设置的值


## Start. 启动

php start.php start -d

## Stop. 停止

php start.php stop

## Status.  查询状态

php start.php status

