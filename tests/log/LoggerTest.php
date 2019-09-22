<?php
/**
 * Created by PhpStorm.
 * User: laiconglin
 * Date: 22/04/2018
 * Time: 00:01
 */

use Koala\Log\MyLogger;

class LoggerTest extends PHPUnit_Framework_TestCase
{
	public function testLogger() {
		$logConfig = [
			'level' => \Monolog\Logger::INFO,
			'logPath' =>  "./logs",
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
		MyLogger::initLogConfig($logConfig);
		$apiLogger = MyLogger::getLogger("api");
		$apiLogger->info("test log", ["target_id" => "123456"]);
		$this->assertTrue(true);
	}
}
