<?php
namespace Cache\Shell;

use Cake\Console\Shell;
use Cache\Routing\Filter\CacheFilter;

/**
 * Shell for tasks related to plugins.
 *
 */
class CacheShell extends Shell {

	/**
	 * @param strin|null $url
	 * @return void
	 */
	public function info($url = null) {
		$folder = CACHE . 'views' . DS;
		if (!is_dir($folder)) {
			mkdir($folder, 0770, true);
		}

		if (!$url) {
			$fi = new \FilesystemIterator($folder, \FilesystemIterator::SKIP_DOTS);
			$count = iterator_count($fi);
			$this->out($count . ' cache files found.');
			return;
		}

		$cache = new CacheFilter();
		$file = $cache->getFile($url);
		if (!$file) {
			$this->out('No cache file found');
			return;
		}
		$content = file_get_contents($file);
		$cacheInfo = $cache->extractCacheInfo($content);
		$time = $cacheInfo['time'];
		if ($time) {
			$time = date(FORMAT_DB_DATETIME, $time);
		} else {
			$time = '(unlimited)';
		}

		$this->out('Cache File: ' . basename($file));
		$this->out('URL ext: ' . $cacheInfo['ext']);
		$this->out('Cached until: ' . $time);
	}

	/**
	 * @param string|null $url
	 * @return void|int
	 */
	public function clear($url = null) {
		if ($url) {
			$cache = new CacheFilter();
			$file = $cache->getFile($url);
			if (!$file) {
				$this->error('No cache file found');
				return;
			}
			unlink($file);
			$this->out('File ' . $file . ' deleted');
			return;
		}

		$folder = CACHE . 'views' . DS;

		$continue = $this->in('Clear `' . $folder . '`?', ['y', 'n'], 'y');
		if ($continue !== 'y') {
			return $this->error('Aborted!');
		}

		$fi = new \FilesystemIterator($folder, \FilesystemIterator::SKIP_DOTS);
		foreach ($fi as $file) {
			$path = $file->getPathname();
			if ($this->params['verbose']) {
				$this->out('Deleting ' . $path);
			}
			unlink($path);
		}
		$this->out('Done!');
	}

	/**
	 * Gets the option parser instance and configures it.
	 *
	 * @return \Cake\Console\ConsoleOptionParser
	 */
	public function getOptionParser() {
		$parser = parent::getOptionParser();

		$infoParser = $parser->toArray();
		$infoParser['arguments']['url'] = [
			'help' => 'Absolute URL',
			'required' => false
		];

		$parser->description('Cache Shell to cleanup caching of view files.')
				->addSubcommand('info', [
					'help' => 'Infos about the files',
					'parser' => $infoParser,
				])
				->addSubcommand('clear', [
					'help' => 'Clear all or part of the files',
					'parser' => $parser
				]);

		return $parser;
	}

}
