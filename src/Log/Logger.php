<?php
/**
 * Created by PhpStorm.
 * User: laiconglin
 * Date: 26/11/2017
 * Time: 00:14
 */
namespace Koala\Log;

class Logger {
	protected static $loggerMap = [];

	protected static $logConfig = [];

	protected static $lineFormatter = null;

	protected static $jsonFormatter = null;

	// 默认的日志的配置
	protected static $defaultConfig = [
		'level' => \Monolog\Logger::INFO,
		'logPath' =>  "/tmp/logs/",
		'logFileExtension' => '.log',
		'delayThreshold' => 100, // log buffer threshold
		// 用于输出日志的附加信息   ---start
		'extra' => [
			'REQUEST_URI'    => 'A', // 请求的地址
			'REMOTE_ADDR'    => 'B', // request ip, 如果要获取用户真实ip，需要重新获取
			'REQUEST_METHOD' => 'C', // 请求的方法，get or post ?
			'HTTP_REFERER'   => 'D', // 请求的referer
			'SERVER_NAME'    => 'E', // 请求的host
			'UNIQUE_ID'      => 'G', // 请求的唯一的ID，可用于链路追踪
		],
		// 用户输出日志的附加信息 ---start
	];

	/**
	 * 初始化日志的配置信息
	 * @param array $logConfig
	 */
	public static function initLogConfig($logConfig = []) {
		if (!empty(self::$logConfig)) {
			return;
		}
		if (empty($logConfig)) {
			// 采用默认的配置
			self::$defaultConfig['logPath'] = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR;
			self::$logConfig = self::$defaultConfig;
		} else {
			// 加上默认的配置，防止配置出错
			self::$logConfig = $logConfig + self::$defaultConfig;
		}
	}

	protected static function getJsonFormatter() {
		if (self::$jsonFormatter !== null) {
			return self::$jsonFormatter;
		}
		self::$jsonFormatter = new \Monolog\Formatter\JsonFormatter();
		return self::$jsonFormatter;
	}

	/**
	 * @param string $loggerName
	 * @return \Monolog\Logger
	 * @throws LogException
	 */
	public static function getLogger($loggerName = 'error') {
		if (!is_string($loggerName) || empty(trim($loggerName))) {
			throw new LogException("logger name must be a not empty string", ErrorCode::INVALID_PARAM);
		}
		if (!preg_match('/^[a-zA-Z][a-zA-Z_]+$/', $loggerName, $matches)) {
			throw new LogException("logger name must be ^[a-zA-Z_]+$", ErrorCode::INVALID_PARAM);
		}

		if (isset(self::$loggerMap[$loggerName])) {
			return self::$loggerMap[$loggerName];
		}

		// 采用Json的格式化输出
		$formatter = self::getJsonFormatter();

		// 日志的附加信息，用于追踪和表明请求来源等等
		$server = self::$logConfig['extra'];
		$processor = new \Monolog\Processor\WebProcessor($server);

		// Create some handlers
		$logFilePath = sprintf("%s%s-%s%s", self::$logConfig['logPath'] , $loggerName, date("Y-m-d"), self::$logConfig['logFileExtension']);

		$logLevel = self::$logConfig['level'];
		$delayThreshold = intval(self::$logConfig['delayThreshold']);
		$fileStream = new StreamHandler($logFilePath, $logLevel, true, $delayThreshold);
		$fileStream->setFormatter($formatter);
		$fileStream->pushProcessor($processor);

		$tmpLogger = new \Monolog\Logger($loggerName);
		$tmpLogger->pushHandler($fileStream);

		self::$loggerMap[$loggerName] = $tmpLogger;

		return self::$loggerMap[$loggerName];
	}
}
