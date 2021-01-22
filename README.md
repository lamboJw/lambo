# frame_test
这是一个基于swoole开发的简易MVC框架  
## 运行环境
1. PHP > 7
2. Composer
## PHP扩展依赖
1. PDO
2. Redis
3. mbstring
4. Swoole >= 4.5
## 安装
+ `git clone https://github.com/lamboJw/frame_test.git`
+ `cd frame_test`
+ `composer install`
## 快速开始
+ `php run_server.php` ，控制台输出协程http服务器启动
+ 本地浏览器访问 `http://127.0.0.1:10086/test` 
+ 可以看到Hello World字样，说明启动成功
## 使用说明
### 配置 config文件夹
#### app.php 关于框架总体的设置
1. 是否开启debug、debug等级
2. 日志路径、等级等
3. 开启http服务器的模式（协程风格 或 异步风格）
4. 标准输出是否输出到页面
5. 连接池开关，redis配置选择
6. 是否自动加载vendor  

#### swoole.php 关于swoole的设置
1. http：服务器基本配置，ip、端口等
2. websocket：websocket服务器配置（待开发）
3. server：swoole服务器详细配置，部分配置只有在异步风格才有效
4. coroutine：协程配置  
_注意，swoole的配置如果不在配置文件里，可自行增加，具体请查看[swoole文档](https://wiki.swoole.com/#/server/setting)_

#### autoload.php 自动加载library和helper
在对应位置加上类名，在服务器启动前会自动加载并常驻内存，因此只可以加载构造函数不需要传值的类。  
使用全局函数`library($class_name)`和`helper($class_name)`调用。

#### database.php MySQL数据库配置
1. MySQL连接基于PDO扩展
2. size为连接池容量，仅当启用连接池时才有效
3. 可以添加多个配置，当启用连接池时，会一次生成所有配置的连接池

#### ftp.php FTP服务器配置
（待开发）

#### middleware.php 中间件注册
1. 只有在该配置文件中注册了的中间件才能使用
2. 格式为 `'middleware_name' => \namespace\class_name::class,`

#### redis.php Redis配置
1. 可以填写多个配置，但只能启用一个，在`app.php`中的`redis_config_key`修改启用配置
2. size为连接池容量，仅当启用连接池时才有效