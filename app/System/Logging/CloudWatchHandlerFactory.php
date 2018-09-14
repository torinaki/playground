<?php declare(strict_types = 1);

namespace App\System\Logging;

use Aws\CloudWatchLogs\CloudWatchLogsClient;
use Maxbanton\Cwh\Handler\CloudWatch;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\NullHandler;

class CloudWatchHandlerFactory
{

	/** @var \Aws\CloudWatchLogs\CloudWatchLogsClient */
	private $client;

	/** @var string */
	private $ecsInstanceId;

	/** @var bool */
	private $enabled;

	public function __construct(CloudWatchLogsClient $client, string $ecsInstanceId, bool $enabled)
	{
		$this->client = $client;
		$this->ecsInstanceId = $ecsInstanceId;
		$this->enabled = $enabled;
	}

	public function create(): HandlerInterface
	{
		if (!$this->enabled) {
			return new NullHandler();
		}

		return new CloudWatch(
			$this->client,
			'phpstan-playground',
			$this->ecsInstanceId
		);
	}

}
