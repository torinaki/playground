<?php declare(strict_types = 1);

namespace App\System\Logging;

use Nette\Application\LinkGenerator;
use Throwable;
use Tracy\BlueScreen;
use Tracy\Logger;

class TracyLogger extends Logger
{

	/** @var \Monolog\Logger */
	private $errorLogger;

	/** @var LinkGenerator */
	private $linkGenerator;

	/** @var string */
	private $localDirectory;

	/** @var string */
	private $remoteDirectory;

	public function __construct(
		?BlueScreen $blueScreen,
		\Monolog\Logger $errorLogger,
		LinkGenerator $linkGenerator,
		string $localDirectory,
		string $remoteDirectory
	)
	{
		parent::__construct($localDirectory, null, $blueScreen);
		$this->errorLogger = $errorLogger;
		$this->linkGenerator = $linkGenerator;
		$this->localDirectory = $localDirectory;
		$this->remoteDirectory = $remoteDirectory;
	}

	/**
	 * @param string|string[]|\Throwable $message
	 * @param string|null $priority
	 * @return string|null
	 */
	public function log($message, $priority = self::ERROR): ?string
	{
		$exceptionFile = null;
		$logContext = [];
		if ($message instanceof Throwable) {
			$exceptionFileName = $this->logException($message);
			if ($this->localDirectory !== $this->remoteDirectory) {
				$basename = basename($exceptionFileName);
				file_put_contents(
					$this->remoteDirectory . '/' . $basename,
					$this->anonymize(file_get_contents($exceptionFileName))
				);
				$logContext['exceptionUrl'] = $this->linkGenerator->link('Service:log', [
					'exception' => $basename,
				]);
				unlink($exceptionFileName);
			}

			$message = sprintf('%s: %s', get_class($message), $message->getMessage());
		}

		$this->errorLogger->addError($message, $logContext);

		return $exceptionFile;
	}

	private function anonymize(string $contents): string
	{
		foreach ($_ENV as $key => $value) {
			if (
				stripos($key, 'secret') === false
				&& stripos($key, 'access') === false
				&& stripos($key, 'debug') === false
			) {
				continue;
			}
			$contents = str_replace($value, sprintf('<%s>', $key), $contents);
		}

		return $contents;
	}

}
