<?php

/*
 * This file is part of the Monolog package.
 *
 * (c) Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Koala\Log;

use Monolog\Logger as MonologLogger;
use Monolog\Handler\AbstractProcessingHandler;

/**
 * Stores to any stream resource
 *
 * Can be used to store into php://stderr, remote and local files, etc.
 *
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */
class StreamHandler extends AbstractProcessingHandler
{
    protected $stream;
    protected $url;

	/**
	 * log buffer threshold
	 * @var int
	 */
    protected $delayThreshold = 10;
	/**
	 * total log line count
	 * @var int
	 */
	protected $logLineCount = 0;
	/**
	 * current log buffer count
	 * @var int
	 */
	protected $logBufferCount = 0;
	/**
	 * log buffers
	 * @var array
	 */
	protected $logBuffers = [];

    /**
     * @param resource|string $stream
     * @param bool|int             $level     The minimum logging level at which this handler will be triggered
     * @param Boolean         $bubble         Whether the messages that are handled can bubble up the stack or not
     * @param int $delayThreshold  log will be written into file delay until up to the threshold
     * @throws \Exception                If a missing directory is not buildable
     * @throws \InvalidArgumentException If stream is not a resource or string
     */
    public function __construct($stream, $level = MonologLogger::DEBUG, $bubble = true, $delayThreshold = 100)
    {
        parent::__construct($level, $bubble);
        if (is_resource($stream)) {
            $this->stream = $stream;
        } elseif (is_string($stream)) {
            $this->url = $stream;
        } else {
            throw new LogException('A stream must either be a resource or a string.', ErrorCode::INVALID_PARAM);
        }

        $filePath = dirname($this->url);
		if (!is_dir($filePath)) {
			$filePath = rtrim($filePath, DIRECTORY_SEPARATOR);
			$result = mkdir($filePath, 0755, true);
			if (!$result) {
				throw new LogException(sprintf('unable to create directory: [%s]', $filePath), ErrorCode::WRITE_FILE_PERMISSION_ERROR);
			}
		}
		if (!is_writable($filePath)) {
			throw new LogException(sprintf('directory: [%s] is not writable, please check permission.', $this->url), ErrorCode::WRITE_FILE_PERMISSION_ERROR);
		}

		$this->delayThreshold = $delayThreshold;
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        if ($this->url && is_resource($this->stream)) {
            fclose($this->stream);
        }
        $this->stream = null;
    }

    public function __destruct()
	{
		// write log into file when request end
		if (!empty($this->logBuffers)) {
			$this->realWriteToFile(implode("", $this->logBuffers));
			// reset log buffer
			$this->logBuffers = [];
			$this->logBufferCount = 0;
		}
		parent::__destruct();
	}

	/**
	 * {@inheritdoc}
	 */
	public function handle(array $record)
	{
		if (!$this->isHandling($record)) {
			return false;
		}

		// 只显示当前的时间格式，把时区信息去掉了
		if (isset($record["datetime"]) && is_object($record["datetime"]) && $record["datetime"] instanceof \DateTime) {
			$record["datetime"] = $record["datetime"]->format("Y-m-d H:i:s.u");
		}
		$record = $this->processRecord($record);

		$record['formatted'] = $this->getFormatter()->format($record);

		$this->write($record);

		return false === $this->bubble;
	}

	/**
	 * Processes a record.
	 *
	 * @param  array $record
	 * @return array
	 */
	protected function processRecord(array $record)
	{
		if ($this->processors) {
			foreach ($this->processors as $processor) {
				$record = call_user_func($processor, $record);
			}
		}

		return $record;
	}

	/**
     * {@inheritdoc}
     */
    protected function write(array $record)
    {
		$this->logBufferCount++;
		$this->logLineCount++;
		$this->logBuffers[] = (string) $record['formatted'];
		if ($this->logBufferCount == $this->delayThreshold) {
			$this->realWriteToFile(implode("", $this->logBuffers));
			// reset log buffer
			$this->logBuffers = [];
			$this->logBufferCount = 0;
		}
    }

    /**
     * Write to stream
     * @param resource $stream
     * @param array $record
     */
    protected function streamWrite($stream, array $record)
    {
        fwrite($stream, (string) $record['formatted']);
    }

	/**
	 * write log content into file
	 *
	 * @param string $logContent
	 * @throws LogException
	 * @return null
	 */
	public function realWriteToFile($logContent)
	{
		// For performance reasons, open the file handler when write log content into file
		$fileHandle = fopen($this->url, 'a');
		flock($fileHandle, LOCK_EX);
		if (fwrite($fileHandle, $logContent) === false) {
			throw new LogException(sprintf('directory: [%s] is not writable, please check permission.', $this->url), ErrorCode::WRITE_FILE_PERMISSION_ERROR);
		}
		fflush($fileHandle);
		flock($fileHandle, LOCK_UN);
		fclose($fileHandle);
	}
}
