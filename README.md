# KoalaPHP Logger Component
KoalaPHP 基于 monolog 写入本地文件的json格式日志的组件

## 1. 快速开始

### 1.1 日志组件初始化

建议在Bootstrap的时候初始化一次

```
define('LOG_PATH', './logs/')
$logConfig = [
    'level' => \Monolog\Logger::INFO,
    'logPath' =>  "./logs/",
    'logFileExtension' => '.log',
    'delayThreshold' => 100, // log buffer threshold
    // 用于输出日志的附加信息   ---start
    'extra' => [
        'REQUEST_URI'    => 'A', // 请求的地址
        'REMOTE_ADDR'    => 'B', // request ip, 如果要获取用户真实ip，需要重新获取
        'REQUEST_METHOD' => 'C', // 请求的方法，get or post ?
        'HTTP_REFERER'   => 'D', // 请求的referer
        'SERVER_NAME'    => 'E', // 请求的host
        'UNIQUE_ID'      => md5(uniqid(mt_rand(), true)), // 请求的唯一的ID，可用于链路追踪
    ],
    // 用户输出日志的附加信息 ---start
];
Koala\Log\Logger::initLogConfig($logConfig);

```

### 1.2 日志对象的获取和使用

```
$apiLogger = Koala\Log\Logger::getLogger("api");
$apiLogger->info("test log", ["target_id" => "123456"]);
```

### 1.3 日志信息预览

在日志文件`./logs/api-2019-05-25.log` 中，写入如下的一条日志：

```
{"message":"test log","context":{"target_id":"123456"},"level":200,"level_name":"INFO","channel":"api","datetime":"2019-05-25 12:02:21.758641","extra":{"url":"A","ip":"B","http_method":"C","server":"E","referrer":"D","unique_id":"cf026132e86bc2799375bbabeeab3edc"}}
```
