<?php

declare(strict_types=1);

namespace poggit\libasynCurl;

use Closure;
use InvalidArgumentException;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\InternetRequestResult;
use poggit\libasynCurl\thread\CurlDeleteTask;
use poggit\libasynCurl\thread\CurlGetTask;
use poggit\libasynCurl\thread\CurlPostTask;
use poggit\libasynCurl\thread\CurlPutTask;
use poggit\libasynCurl\thread\CurlThreadPool;
use SOFe\AwaitGenerator\Await;

use function is_array;
use function json_encode;

/**
 * Asynchronous Curl library using await-generator for PocketMine-MP.
 *
 * This class provides non-blocking HTTP requests that can be awaited using SOFe's AwaitGenerator.
 */
final class libasynCurl{
	/** @var bool */
	private static bool $registered = false;
	/** @var CurlThreadPool */
	private static CurlThreadPool $threadPool;

	/**
	 * Registers the libasynCurl with a plugin.
	 * This initializes the thread pool and sets up the necessary schedulers.
	 *
	 * @param PluginBase $plugin
	 * @throws InvalidArgumentException if already registered
	 */
	public static function register(PluginBase $plugin): void
	{
		if(self::$registered){
			throw new InvalidArgumentException("{$plugin->getName()} attempted to register " . self::class . " twice.");
		}

		$server = $plugin->getServer();
		self::$threadPool = new CurlThreadPool(CurlThreadPool::POOL_SIZE, CurlThreadPool::MEMORY_LIMIT, $server->getLoader(), $server->getLogger(), $server->getTickSleeper());

		$plugin->getScheduler()->scheduleRepeatingTask(new ClosureTask(function(): void{
			self::$threadPool->collectTasks();
		}), CurlThreadPool::COLLECT_INTERVAL);
		$plugin->getScheduler()->scheduleRepeatingTask(new ClosureTask(function(): void{
			self::$threadPool->triggerGarbageCollector();
		}), CurlThreadPool::GARBAGE_COLLECT_INTERVAL);

		self::$registered = true;
	}

	/**
	 * Checks if libasynCurl has been registered.
	 *
	 * @return bool
	 */
	public static function isRegistered(): bool
	{
		return self::$registered;
	}

	/**
	 * Detects if this library is running in plugin mode (packaged as a plugin).
	 *
	 * @return bool
	 */
	public static function detectPackaged(): bool
	{
		// This is called from LibAsynCurlPlugin to signal we're in plugin mode
		// In plugin mode, classes are loaded by ThreadSafeClassLoader
		return true;
	}

	/**
	 * Performs an asynchronous GET request and returns an Await object.
	 *
	 * @param string $page URL to request
	 * @param int $timeout Timeout in seconds
	 * @param string[] $headers HTTP headers
	 * @return \Generator<mixed, Await::RESOLVE|Await::REJECT|Await::ONCE, mixed, InternetRequestResult>
	 */
	public static function get(string $page, int $timeout = 10, array $headers = []): \Generator
	{
		return Await::promise(function($resolve, $reject) use ($page, $timeout, $headers): void {
			self::$threadPool->submitTask(new CurlGetTask($page, $timeout, $headers, function(?InternetRequestResult $response) use ($page, $resolve, $reject): void {
				if($response === null){
					$reject(new \RuntimeException("GET request to {$page} failed"));
					return;
				}
				$resolve($response);
			}));
		});
	}

	/**
	 * Performs an asynchronous POST request and returns an Await object.
	 *
	 * @param string $page URL to request
	 * @param array|string $args POST data
	 * @param int $timeout Timeout in seconds
	 * @param string[] $headers HTTP headers
	 * @return \Generator<mixed, Await::RESOLVE|Await::REJECT|Await::ONCE, mixed, InternetRequestResult>
	 */
	public static function post(string $page, array|string $args, int $timeout = 10, array $headers = []): \Generator
	{
		return Await::promise(function($resolve, $reject) use ($page, $args, $timeout, $headers): void {
			self::$threadPool->submitTask(new CurlPostTask($page, $args, $timeout, $headers, function(?InternetRequestResult $response) use ($page, $resolve, $reject): void {
				if($response === null){
					$reject(new \RuntimeException("POST request to {$page} failed"));
					return;
				}
				$resolve($response);
			}));
		});
	}

	/**
	 * Performs an asynchronous PUT request and returns an Await object.
	 *
	 * @param string $page URL to request
	 * @param array|string $args PUT data
	 * @param int $timeout Timeout in seconds
	 * @param string[] $headers HTTP headers
	 * @return \Generator<mixed, Await::RESOLVE|Await::REJECT|Await::ONCE, mixed, InternetRequestResult>
	 */
	public static function put(string $page, array|string $args, int $timeout = 10, array $headers = []): \Generator
	{
		return Await::promise(function($resolve, $reject) use ($page, $args, $timeout, $headers): void {
			self::$threadPool->submitTask(new CurlPutTask($page, $args, $timeout, $headers, function(?InternetRequestResult $response) use ($page, $resolve, $reject): void {
				if($response === null){
					$reject(new \RuntimeException("PUT request to {$page} failed"));
					return;
				}
				$resolve($response);
			}));
		});
	}

	/**
	 * Performs an asynchronous DELETE request and returns an Await object.
	 *
	 * @param string $page URL to request
	 * @param array|string $args DELETE data
	 * @param int $timeout Timeout in seconds
	 * @param string[] $headers HTTP headers
	 * @return \Generator<mixed, Await::RESOLVE|Await::REJECT|Await::ONCE, mixed, InternetRequestResult>
	 */
	public static function delete(string $page, array|string $args, int $timeout = 10, array $headers = []): \Generator
	{
		return Await::promise(function($resolve, $reject) use ($page, $args, $timeout, $headers): void {
			self::$threadPool->submitTask(new CurlDeleteTask($page, $args, $timeout, $headers, function(?InternetRequestResult $response) use ($page, $resolve, $reject): void {
				if($response === null){
					$reject(new \RuntimeException("DELETE request to {$page} failed"));
					return;
				}
				$resolve($response);
			}));
		});
	}
}
