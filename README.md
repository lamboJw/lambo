# lambo
这是一个基于swoole开发的简易MVC框架，参考了CI框架、laravel框架和simps框架进行开发。支持异步风格和协程风格两种HTTP服务器、MySQL和Redis连接池。  
## 运行环境
1. PHP > 7.4
2. Composer
## PHP扩展依赖
1. PDO
2. Redis
3. mbstring
4. Swoole >= 4.5
5. posix
## 安装
```
git clone https://github.com/lamboJw/lambo.git
cd lambo
composer install
```
## 快速开始
+ `php run_server.php` ，控制台输出协程http服务器启动。
+ 本地浏览器访问 `http://127.0.0.1:10086/test` 。 
+ 可以看到Hello World字样，说明启动成功。
## 使用说明
**\* 注意：本框架所有类的自动加载都是基于命名空间，且命名空间需要与文件路径保持一致！**
### 定义常量
在根目录的`run_server.php`中增加即可。非必要，不要改动原来的常量。

### 配置 config文件夹
#### app.php 关于框架总体的设置
1. 是否开启debug、debug等级。
2. 日志路径、等级等。
3. 开启http服务器的模式（协程风格 或 异步风格）。
4. 标准输出是否输出到页面。
5. 连接池开关，redis配置选择。
6. 是否自动加载vendor。  

#### swoole.php 关于swoole的设置
1. http：服务器基本配置，ip、端口等。
2. websocket：websocket服务器配置（待开发）。
3. server：swoole服务器详细配置，部分配置只有在异步风格才有效。
4. coroutine：协程配置。  
_\* 注意：swoole的配置如果不在配置文件里，可自行增加，具体请查看[swoole文档](https://wiki.swoole.com/#/server/setting)_ 。

#### autoload.php 自动加载library和helper
在对应位置加上类名，在服务器启动前会自动加载并常驻内存，因此只可以加载构造函数不需要传值的类。  
使用全局函数`library($class_name)`和`helper($class_name)`调用。

#### database.php MySQL数据库配置
1. MySQL连接基于PDO扩展。
2. size为连接池容量，仅当启用连接池时才有效。
3. 可以添加多个配置，当启用连接池时，会一次生成所有配置的连接池。

#### ftp.php FTP服务器配置
（待开发）

#### middleware.php 中间件注册
1. 只有在该配置文件中注册了的中间件才能使用。
2. 格式为 `'middleware_name' => \namespace\class_name::class,`。

#### redis.php Redis配置
1. 可以填写多个配置，但只能启用一个，在`app.php`中的`redis_config_key`修改启用配置。
2. size为连接池容量，仅当启用连接池时才有效。

#### 自定义配置
必须使用变量名为$config的数组。

### 全局方法
+ `&config_all()`  
获取所有配置文件信息，以文件名为key。修改元素会直接改变已加载配置的值，谨慎修改。

+ `config($name, $default = null)`  
获取某一配置文件的内容。  
$name：配置项路径，例如：`app.php`文件下的`server_type`配置，使用`app.server_type`。   
$default：如果$item配置不存在时，返回的默认值。

+ `library($name)` 和 `helper($name)`
获取对应类的实例，分别对应libraries文件夹和helper文件夹下的类。  
$name：类名。

+ `app()`  
返回Application实例，协程隔离。

+ `request(...$keys)`  
若不传参数，则返回Request实例。  
若传参数，则根据参数获取get和post中对应的值。

+ `response($data = '')`  
Http响应。  
若不传参数，则返回Response实例。  
若传参数，则把data作为内容发送到浏览器。

+ `ws_response($data = '', $opcode = WEBSOCKET_OPCODE_TEXT, $flags = true)`  
websocket响应。  
若不传参数，则返回WebsocketResponse实例。  
若传参数，则把内容推送到客户端。

+ `server($key = '')`  
获取请求中的server信息，类似原生PHP的$_SERVER。

+ `api_json($code = 1, $msg = 'success', $data = [])`  
返回一个用于响应api的json。

+ `api_response($code = 1, $msg = 'success', $data = [])`  
区别于`api_json()`方法，该方法会直接将json发送到浏览器。

+ `log_message($level, $message)`  
移植并改良自CI框架Log类，记录日志。  
$level：日志等级。可以为'ERROR'、'DEBUG'、'NOTICE'、'INFO'、'ALL'，也可以自定义。如果为预设的几个，则受到`config/app.php`中的`log_level`限制，否则不会限制。  
$message：需要记录的信息。如果传值类型为非string，会做var_export处理。

+ `debug($level, $message)`  
打印debug消息到控制台，并写入日志。受`config/app.php`中的`debug`和`debug_level`限制。  
$level：debug等级。  
$message：需要打印的信息。

+ `view($view, $data=[])`  
渲染视图，使用blade模版引擎。  
$view：视图名称。  
$data： 传到视图的数据。

### Application类
主要用于传出协程隔离的全局变量、单例类实例、Request实例、Response实例、WebsocketResponse实例。  
#### 主要方法
+ `singleton(string $class, ...$params)`  
返回单例类实例，如果未定义，会使用$params传值进行实例化。

+ `set($key, $value = '')`  
设置全局变量，可以只设置一个值，也可以一次设置多个。  
$key：只设置一个值时，传入变量名，当想设置多个值，传入key=>value数组。  
$value：只设置一个值时，传入变量的值，当想设置多个值，不需传值。  

+ `get($key)`
获取全局变量。  
$key：当类型为string时，返回单个变量，当类型为array时，返回数组中的所有变量。

### Request类
对`Swoole\Http\Request`进行封装。
#### 主要方法
+ `request(...$keys)`  
获取一个或多个get和post中指定值  

+ `get(...$keys)`  
获取一个或多个get中指定值。  

+ `post(...$keys)`  
获取一个或多个post中指定值。  

+ `files()`  
获取所有客户端提交的文件。  

+ `tmpfiles()`  
获取所有临时文件。

+ `all()`  
获取所有客户端传值，包含get、post、files、tmpfiles。

+ `cookie($key = '')`  
获取cookie。  
$key为空时，返回所有cookie的值。

+ `server($key = '')`  
获取server信息，类似原生PHP的$_SERVER。  
$key为空时，返回所有server的值。

+ `header($key = '')`  
获取header信息  
$key为空时，返回所有header的值。

### Response类
对`Swoole\Http\Response`中的HTTP响应方法进行封装。
#### 主要方法
+ `json($data)`  
将$data进行json_encode后发送到浏览器。

+ `end($content = '')`  
将内容发送到浏览器并结束本次响应。慎用，一般响应只需要使用`write()`方法。  
如果开启了标准输出到页面，使用该方法后，标准输出的内容会抛弃。  
_\* 注意：使用该方法后，会直接结束程序。_

+ `status($statusCode)`  
设置响应状态码。

+ `sendfile($filename, $offset = null, $length = null)`  
发送文件到客户端。
$filename：文件名。  
$offset：偏移量，不传默认从头开始。  
$length：长度，不传默认到文件末尾。  

+ `redirect($location, $http_code = null)`  
重定向页面。
$location：重定向地址。  
$http_code：重定向状态码。  
_\* 注意：使用该方法后，会直接结束程序。_

+ `write($content)`  
将内容发送到浏览器。  

+ `cookie($name, $value = null, $expires = null, $path = null, $domain = null, $secure = null, $httponly = null, $samesite = null, $priority = null)`  
设置 HTTP 响应的 cookie 信息，会对 $value 进行`urlencode`处理。  

+ `cookie($name, $value = null, $expires = null, $path = null, $domain = null, $secure = null, $httponly = null, $samesite = null, $priority = null)`  
设置 HTTP 响应的 cookie 信息，会对 $value 进行`urlencode`处理。  

+ `rawCookie($name, $value = null, $expires = null, $path = null, $domain = null, $secure = null, $httponly = null, $samesite = null, $priority = null)`  
与`cookie()`方法参数一样，但不会对 $value 进行`urlencode`处理。

+ `header($key, $value, $format = null)`  
设置 HTTP 响应的 Header 信息。  
$format：是否需要对 Key 进行 HTTP 约定格式化【默认 true 会自动格式化】  

### WebsocketResponse类
对`Swoole\Http\Response`进行封装的websocket响应类，自动保存客户端池。  
_\* 注意：仅适用于协程风格。_  
#### 主要方法
+ `get_pool()`  
获取客户端池

+ `upgrade()`  
发送 WebSocket 握手成功信息。

+ `recv()`  
接收 WebSocket 消息。已进行基本异常退出判断。

+ `push($data, $opcode = WEBSOCKET_OPCODE_TEXT, $flags = true)`
发送 WebSocket 数据帧。  
$data：发送数据帧（类型为Frame时，忽略后面两个参数）。  
$opcode：数据帧类型，WEBSOCKET_OPCODE_TEXT（文本内容） 或 WEBSOCKET_OPCODE_BINARY（二进制内容）。  
$finish：是否发送完成。

+ `close()`  
关闭 WebSocket 连接。

### 数据库Model
移植[Simps](https://simps.io)框架的BaseModel模块，基于PDO连接MySQL，可以使用连接池，增加短连接模式，增加了一些功能。使用Medoo框架，基本的使用方法，请查看[Medoo文档](https://medoo.lvtao.net/1.2/doc.php) 。  
+ 创建model时，需要继承`\system\kernel\Model`类：`class example_model extends Model`  
+ 根据情况，覆盖`$db`、`$tableName`、`$keyName`。  
  `$db`：数据库配置，`config/database.php`配置中其中一个key。  
  `$tableName`：表名。  
  `$keyName`：主键名。  
  
+ 插入、更新时自动修改时间功能。  
  `$timestamp`：是否开启自动更新时间功能。  
  `$add_time_col`：创建时间的列名。  
  `$edit_time_col`：更新时间的列名。  
  `$time_type`： 更新时间的类型。`MODEL_DATETIME`日期时间，`MODEL_DATE`日期，`MODEL_UNIX_TIMESTAMP`时间戳。  
#### 额外方法
+ `getInfo($where, $columns = "*", $join = null)`  
  获取单条数据。  
  $where：查询条件，如果类型不为array，则当作是主键查询。    
  $columns：查询字段。  
  $join：连表操作。  
    
+ `getList($where, $columns = "*", $join = null, &$count = false)`  
  获取多条数据。  
  $where：查询条件。  
  $columns：查询字段。  
  $join：连表查询。  
  $count：本条查询无limit时的总条数，不传默认不获取，可用于分页计数。  
  
+ `getListWithPage(array $where, int $page, array $option = [])`  
  获取带有分页信息的列表。  
  $where：查询条件。  
  $page：页数，从1开始。  
  $option：分页选项。pagesize：每页数量，默认20；page_name：页码的参数名；columns：查询字段；join：连表查询。  
  返回值：  
  list：查询结果。  
  count：总行数。  
  page：当前页数。  
  pagesize：每页数量。  
  page_name：页数的参数名。  
 
+ `add($data)`  
  插入数据，可插入单条或多条，返回最后插入ID。  
  $data：插入数据。  
  
+ `edit($data, $where)`  
  更新数据，返回影响行数。  
  $data：更新字段。  
  $where：更新条件。  
 
+ `del($where)`  
  删除数据，返回影响行数。  
  $where：删除条件。  
  
+ `columns()`  
  获取当前表的所有字段。
  
+ `load()`  
  根据表字段自动获取数据。  
  可以覆盖Model类的`$able_columns`变量，决定只获取某些字段。  
  可以覆盖Model类的`$deny_columns`变量，决定不获取某些字段。  

+ `save($data)`  
  根据主键是否为空判断插入或更新。  
  $data：要更新或插入的数据。  
  
+ `updateOrInsert($where, $data)`  
  $where：查询条件。  
  $data：要更新或插入的数据。
  
### Redis  
移植[Simps](https://simps.io)框架的BaseRedis模块，可以使用连接池，增加短连接模式。在连接池模式下，在类销毁时，会自动将库切换回`config/redis.php`配置文件的默认库。原代码为每执行一次命令，都做一次连接池池get和push，现在改为构造函数连接池get，析构函数执行连接池push。
#### 使用方法
实例化类之后，可以使用Redis扩展中的所有方法。  
 ```
$redis = new BaseRedis();
$redis->get('key1');
```
 
### 中间件
编写的中间件需要放在`app/middleware`文件夹下，实现`Middleware`抽象类。路由中定义的中间件，会自动执行handle方法。当执行通过时，请返回`true`。不通过时，返回的内容会直接发送到浏览器，且结束运行。

### 定义路由
路由文件存放在`app/routes`文件夹下。`default.php`文件下的路由，没有前置路径。除`default.php`文件外，其他文件内的路由，都会根据文件名作为前置路径，例：  
`admin.php`文件下存在一个路由，地址为`test1`，服务器监听`localhost:80`，则实际访问地址为`http://localhost/admin/test1` 。  
+ 定义单一路由  
`route()`：  
$path：路由地址  
$controller：控制器名  
$func：方法名  

```
$router = new Router();
$router->route('/test','test','index');
```

+ 使用中间件  
`middleware()`：  
$middleware： 该路由需要使用的中间件名称。中间件名称为`app/middleware.php`中的key。  
_\* 注意：单独使用middleware()方法没有任何效果，不许和route()方法一起使用。_
```
$router = new Router();
$router->middleware(['test'])->route('/test','test','index');
```

+ 定义一组路由
当多个路由都需要使用同一组中间件，可以使用这个。  
`group()`：  
$middleware： 和`middleware()`方法接收参数一样。  
$routes：多个路由类实例组成的数组。  

```
$router = new Router();
$router->group(['test'],[
    (new Route('/test2','test','index2')),
]);
```

### 渲染视图
框架使用了blade模版引擎，来自 [xiaoLer/blade](https://github.com/XiaoLer/blade) 。blade模版引擎使用方法请查看 [laravel文档](https://learnku.com/docs/laravel/8.x/blade/9377) 。  
几乎所有 Blade 的特性都被保留了，但是一些专属于 Laravel 的特征被移除了：  
+ @inject @can @cannot @lang 关键字被移除了
+ 不支持事件和中间件

#### 使用
视图文件放在`app/views`文件夹下。在控制器中，使用view()方法直接渲染。  
+ `view()`  
$view：视图文件路径  
$data：传递到视图的变量  
`view('test', ['a'=>'Hello World']);`

### HTTP服务器
使用`app.server_type`来控制启动的HTTP服务器类型。
#### 协程风格
使用进程池+协程服务器实现，类似异步风格的BASE模式，多个子进程开启相同的纯协程HTTP服务器，对同一端口监听，争抢请求。  
目前支持的`swoole.server`配置项有：
+ worker_num
+ max_request
+ enable_static_handler
+ document_root
+ static_handler_locations
+ daemonize

#### 异步风格
使用异步风格服务器，`swoole.server`所有配置项都能使用，默认使用SWOOLE_BASE模式。