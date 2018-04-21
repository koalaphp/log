<?php
/**
 * Created by PhpStorm.
 * User: laiconglin
 * Date: 26/11/2017
 * Time: 00:14
 */
namespace Koala\Log;

use Monolog\Formatter\LineFormatter;

class Logger {
	private static $loggerMap = [];

	private static $logConfig = [];

	private static $lineFormatter = null;

	// 默认的日志的配置
	protected static $defaultConfig = [
		'level' => \Monolog\Logger::INFO,
		'logPath' =>  "/tmp/logs/",
		'logFileExtension' => '.log',
		'delayThreshold' => 100, // log buffer threshold
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
			self::$defaultConfig['logPath'] = sys_get_temp_dir() . DS . '' . DS;
			self::$logConfig = self::$defaultConfig;
		} else {
			// 加上默认的配置，防止配置出错
			self::$logConfig = $logConfig + self::$defaultConfig;
		}
	}

	/**
	 * @return LineFormatter
	 */
	protected static function getLineFormatter() {
		if (self::$lineFormatter !== null) {
			return self::$lineFormatter;
		}
		// the default date format is "Y-m-d H:i:s", output the date with microseconds.
		$dateFormat = "Y-m-d H:i:s.u";
		// the default output format is "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n"
		// $output = "%datetime% > %level_name% > %message% %context% %extra%" . PHP_EOL;
		$curUri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '-';
		$output = "%datetime% > URI:[{$curUri}] %level_name% > %message% %context%" . PHP_EOL;
		// finally, create a formatter
		self::$lineFormatter = new LineFormatter($output, $dateFormat);
		return self::$lineFormatter;
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

		$formatter = self::getLineFormatter();

		// Create some handlers
		$logFilePath = sprintf("%s%s-%s%s", self::$logConfig['logPath'] , $loggerName, CUR_DAY, self::$logConfig['logFileExtension']);

		$logLevel = self::$logConfig['level'];
		$delayThreshold = intval(self::$logConfig['delayThreshold']);
		$fileStream = new StreamHandler($logFilePath, $logLevel, true, $delayThreshold);
		$fileStream->setFormatter($formatter);

		$tmpLogger = new \Monolog\Logger($loggerName);
		$tmpLogger->pushHandler($fileStream);

		self::$loggerMap[$loggerName] = $tmpLogger;

		return self::$loggerMap[$loggerName];
	}
}