<?php
/**
 * Created by PhpStorm.
 * User: laiconglin
 * Date: 22/04/2018
 * Time: 00:01
 */

use Koala\Log\Logger;

class LoggerTest extends PHPUnit_Framework_TestCase
{
	public function testLogger() {
		$logConfig = [
			'level' => \Monolog\Logger::INFO,
			'logPath' =>  "./logs/",
			'logFileExtension' => '.log',
			'delayThreshold' => 100, // log buffer threshold
		];
		Logger::initLogConfig($logConfig);
		$apiLogger = Logger::getLogger("api");
		$apiLogger->info("test log");
		$this->assertTrue(true);
	}
}
