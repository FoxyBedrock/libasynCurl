<?php

declare(strict_types=1);

namespace poggit\libasynCurl;

use pocketmine\plugin\PluginBase;

/**
 * Main class of the libasynCurl plugin distribution (plugin.yml packaging).
 *
 * This plugin provides asynchronous Curl requests using await-generator for optimal performance.
 */
final class LibAsynCurlPlugin extends PluginBase{
	protected function onEnable() : void{
		libasynCurl::register($this);
		$this->getLogger()->info("libasynCurl " . $this->getDescription()->getVersion() . " enabled (plugin mode with await-generator).");
	}
}
