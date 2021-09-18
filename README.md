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
+ 本地浏览器访问 `http://127.0.0.1:10086` 。 
+ 可以看到Hello World字样，说明启动成功。
## 使用说明
> 注意：本框架所有类的自动加载都是基于命名空间，且命名空间需要与文件路径保持一致！
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
1. http：http服务器基本配置，ip、端口等，及websocket服务器基本配置。
2. websocket：swoole websocket服务器配置，部分配置只有在异步风格才有效。
3. server：swoole服务器详细配置，部分配置只有在异步风格才有效。
4. coroutine：协程配置。  
> 注意：swoole的配置如果不在配置文件里，可自行增加，具体请查看[swoole文档](https://wiki.swoole.com/#/server/setting)

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

+ `ws_response()`  
返回WebsocketResponse实例。  

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
+ `singleton(string $key, string $class = '', ...$params)`  
返回单例类实例，如果未定义，会使用$params传值进行实例化。  
$key：类的别名。  
$class：类。  
$params：构造函数参数。 

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
> 注意：使用该方法后，会直接结束请求。

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
> 注意：使用该方法后，会直接结束请求。

+ `write($content)`  
将内容发送到浏览器。  

+ `cookie($name, $value = null, $expires = null, $path = null, $domain = null, $secure = null, $httponly = null, $samesite = null, $priority = null)`  
设置 HTTP 响应的 cookie 信息，会对 $value 进行`urlencode`处理。  

+ `rawCookie($name, $value = null, $expires = null, $path = null, $domain = null, $secure = null, $httponly = null, $samesite = null, $priority = null)`  
与`cookie()`方法参数一样，但不会对 $value 进行`urlencode`处理。

+ `header($key, $value, $format = null)`  
设置 HTTP 响应的 Header 信息。  
$format：是否需要对 Key 进行 HTTP 约定格式化【默认 true 会自动格式化】  

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
  更新或插入数据，先根据条件查询结果，如存在结果，对比传入的数据，如果完全一致，则不会执行更新。  
  $where：查询条件。  
  $data：要更新或插入的数据。
  
### Redis  
移植[Simps](https://simps.io)框架的BaseRedis模块，可以使用连接池，增加短连接模式。在连接池模式下，在类销毁时，会自动将库切换回`config/redis.php`配置文件的默认库。原代码为每执行一次命令，都做一次连接池池get和push，现在改为构造函数连接池get，析构函数执行连接池push。
#### 使用方法
实例化类之后，可以使用Redis扩展中的所有方法。  
 ```
$redis = new system\kernel\Redis();
$redis->get('key1');
```
 
### 中间件
编写的中间件需要放在`app/middleware`文件夹下，实现`system\kernel\HttpServer\Middleware`抽象类。路由中定义的中间件，会自动执行`handle()`方法。当执行通过时，请返回`true`。不通过时，返回的内容会直接发送到浏览器，且结束当前请求。需要在`config/middleware.php`中注册才能使用。

### 定义路由
路由文件存放在`app/routes`文件夹下。`default.php`文件下的路由，没有前置路径。除`default.php`文件外，其他文件内的路由，都会根据文件名作为前置路径，例：  
`admin.php`文件下存在一个路由，地址为`/test1`，服务器监听`localhost:80`，则实际访问地址为`http://localhost/admin/test1` 。  
> 支持依赖注入，带参数的路由，按HTTP方法区分路由。  

#### 使用中间件  
+ `Router::middleware(array $middleware)`：  
$middleware： 该路由需要使用的中间件名称。中间件名称为`app/middleware.php`中的key。  
> 注意：单独使用middleware()方法没有任何效果，必须和group()或各个单一路由方法一起使用。
```
Router::middleware(['test'])->get($path, $controller, $func);
```

#### 设置路由前缀  
+ `Router::prefix(string $prefix)`：  
$prefix： 该路由或路由组的前缀。最终路径为：`域名/路由文件名/路由前缀/路由路径`。  
> 注意：单独使用prefix()方法没有任何效果，必须和group()或各个单一路由方法一起使用。
```
Router::prefix('admin')->get($path, $controller, $func);
```

#### 定义单一路由  
根据HTTP方法，分为了`get`、`post`、`put`、`delete`、`patch`、`any`、`match`这几个方法，`any`方法为匹配任何HTTP方法，如果请求时的HTTP方法不符合，则抛出异常。  
除了`match`方法需要多传一个method参数，其他参数都一样：

+ `Router::get(string $path, $controller, string $func = null)`  
$path：路由路径  
$controller：控制器名称，控制器的查找路径为```controllers/路由文件名/该参数值```。也可以直接传匿名函数，直接执行该匿名函数，无需控制器。  
$func：控制器函数名。当$controller传了匿名函数时，不能传该参数。

+ `Router::match(array $method, string $path, $controller, string $func = null)`   
$method：当前路由允许的所有HTTP方法，如：```['get','post']```。所有元素都必须为小写。
+ 使用匿名函数
```php
Router::get('/test', function () {
    response('test callback');
});
```
#### 定义一组路由
 当多个路由都需要使用同一组中间件或前缀时使用。  
+ `group($options, $callback = null)`：  
$options： 统一配置，支持配置中间件和前缀。例：`['middleware'=>['test'],'prefix'=>'admin']`。也可以忽略配置，直接传回调函数。  
$callback：回调函数，包含多个单一路由。如果路由组和单一路由同时配置了中间件和前缀，则优先使用路由组的配置。

```
Router::group(['middleware'=>['test'],'prefix'=>'admin'], function () {
    Router::get('/test/{obj}', function ($obj) {
        response($obj);
    });
    Router::post('/test1', 'index', 'test1');
});
```

#### 定义带参数的路由
在URI中直接传值，可以使用带参数的路由。  
```php
Router::get('/index/{str}', 'index', 'index');
```
也可以定义多个参数  
```php
Router::get('/index/{str}/{str2}/test', 'index', 'test');
```

#### 使用依赖注入
直接在控制器方法或回调函数中传入指定类的参数，即可自动获取该类的实例，无需手动实例化。  
```php
Router::get('/test/{id}', function (app\models\example $model, $id) {
    $re = $model->getInfo($id);
    response(var_export($re, true));
});
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
使用进程池+协程服务器实现，类似异步风格的SWOOLE_BASE模式，多个子进程开启相同的纯协程HTTP服务器，对同一端口监听，争抢请求。  
目前支持的`swoole.server`配置项有：
+ worker_num
+ max_request
+ enable_static_handler
+ document_root
+ static_handler_locations
+ daemonize

#### 异步风格
使用异步风格服务器，`swoole.server`所有配置项都能使用，默认使用SWOOLE_BASE模式。  

#### 代码热更新
每5秒会扫描app路径下，除了helpers和libraries文件夹下文件外的所有文件，如有文件更新过，则会平滑重启当前Worker进程。  

### Websocket服务器
跟随HTTP服务器一同启动，与HTTP服务器使用同一端口，使用`ws://host:port/websocket`连接。  
可以使用的事件有onOpen、onMessage、onClose三个，默认在`app/websocket/WebsocketService.php`中实现对应逻辑。  
使用全局方法`ws_response()`获取websocket响应类。  
  
#### 配置
+ `swoole.http.open_websocket`：控制开始或关闭。  
+ `swoole.http.websocket_service`：websocket服务器处理事件的类，默认即可。如需修改，新的类需要实现`\system\kernel\WebsocketServer\WebsocketHandlerInterface`接口。  
+ `swoole.http.co_ws_broadcast`：协程风格服务器是否开启广播功能。  
> 注意：协程风格下的广播依赖redis的发布订阅功能，需要先配置好redis。
+ `swoole.http.co_ws_pool_size`：协程风格服务器最大同时连接客户端数。  
> 注意：协程风格下的客户端连接记录使用`Swoole\Table`实现，当连接数大于该参数时，会查询不到多余的连接，且无法广播到这些连接。
+ `swoole.http.close_command`：客户端发送关闭命令的关键字。当frame->data等于该参数时，服务端会关闭连接。  
+ `swoole.websocket.websocket_subprotocol`：设置WebSocket子协议。设置后握手响应的 HTTP 头会增加 Sec-WebSocket-Protocol: {$websocket_subprotocol}。**协程风格暂不支持该配置。**  
+ `swoole.websocket.open_websocket_close_frame`：在onMessage事件中接收关闭帧。无需手动断开连接，处理完onMessage事件后会自动断开。
+ `swoole.websocket.open_websocket_ping_frame`：在onMessage事件中接收Ping帧。
+ `swoole.websocket.open_websocket_pong_frame`：在onMessage事件中接收Pong帧。
+ `swoole.websocket.websocket_compression`：启用数据压缩，配合push方法或broadcast方法中的flag参数使用。**协程风格暂不支持该配置。**  
#### websocket响应类
+ `public $fd`：websocket连接的fd。
+ `public $frame`：客户端发送到服务器的帧，类型为`Swoole\WebSocket\Frame`。
+ `push($fd, $data, int $opcode = WEBSOCKET_OPCODE_TEXT, int $flag = SWOOLE_WEBSOCKET_FLAG_FIN): bool`  
  推送数据到指定客户端。  
  $fd：websocket连接的fd。  
  $data：发送的数据。当类型为`Swoole\WebSocket\Frame`时，忽略后面的两个参数。  
  $opcode：指定发送数据内容的格式，默认为文本类型，发送二进制数据，使用`WEBSOCKET_OPCODE_BINARY`。  
  $flag：是否已完成，是否压缩帧。0：未完成；`SWOOLE_WEBSOCKET_FLAG_FIN`：已完成；`SWOOLE_WEBSOCKET_FLAG_COMPRESS`：压缩帧。需要压缩时，使用`SWOOLE_WEBSOCKET_FLAG_FIN | SWOOLE_WEBSOCKET_FLAG_COMPRESS`。
  
+ `exists($fd): bool`
  判断连接是否存在。
  $fd：websocket连接的fd。  

+ `disconnect($fd, int $code = SWOOLE_WEBSOCKET_CLOSE_NORMAL, string $reason = ''): bool`
  断开指定客户端的连接。  
  $fd：websocket连接的fd。  
  $code：关闭连接的状态码。  
  $reason：关闭连接的原因。  

+ `isEstablished($fd): bool`
  检查连接是否为有效的WebSocket客户端连接。  
  $fd：websocket连接的fd。  
  
+ `broadcast($data, int $opcode = WEBSOCKET_OPCODE_TEXT, int $flag = SWOOLE_WEBSOCKET_FLAG_FIN)`  
  广播信息到所有除自己外的在线客户端。  
  参数除$fd外，与`push()`方法对应。  
  
+ `connection_count(): int;`
  返回当前连接的客户端数量。  