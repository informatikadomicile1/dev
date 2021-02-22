<?php

declare(strict_types = 1);

namespace Drupal\vuejs\Commands;

use Consolidation\SiteProcess\Util\Escape;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\vuejs\Exception\LibraryException;
use Drush\Commands\DrushCommands;
use Drush\Drush;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Vuejs Drush commandfile.
 */
class VuejsCommands extends DrushCommands {

  public const LIBRARY_DIR = 'libraries/vuejs';

  /**
   * The config.factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * VuejsCommands constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The config.factory service.
   */
  public function __construct(ConfigFactoryInterface $config) {
    $this->configFactory = $config;
  }

  /**
   * Downloads Vue.js libraries.
   *
   * @param string $name
   *   Library name (vue, vue-router, vue-resource).
   *
   * @usage drush vue:download vue-router
   *   Download vue-router library.
   *
   * @command vuejs:download
   * @aliases vue,vuejs-download
   *
   * @throws \Drupal\vuejs\Exception\LibraryException
   */
  public function download($name) {
    if (!is_dir('libraries')) {
      throw new LibraryException(dt('Directory libraries does not exist.'));
    }

    $libraries = $this->configFactory->get('vuejs.settings')->get('libraries');
    $machineName = str_replace('-', '_', $name);
    if (!isset($libraries[$machineName])) {
      throw new LibraryException(dt('Library @library is not supported.', [
        '@library' => $name,
      ]));
    }
    $library = $libraries[$machineName];

    $download_dir = VuejsCommands::LIBRARY_DIR . "/{$name}/{$library['version']}";
    if (!is_dir($download_dir)) {
      $fs = new Filesystem();
      try {
        $fs->mkdir($download_dir);
      }
      catch (IOException $e) {
        throw new LibraryException($e->getMessage(), $e->getCode());
      }
    }

    foreach (['', '.min'] as $suffix) {
      $download_url = sprintf(
        'https://raw.githubusercontent.com/vuejs/%s/%s%s/dist/%s%s.js',
        $name,
        // Vue resource does not prefix version tags.
        $name == 'vue-resource' ? '' : 'v',
        $library['version'],
        $name,
        $suffix
      );

      $cmd = sprintf(
        'wget --timeout=15 -O %s %s',
        Escape::shellArg("{$download_dir}/{$name}{$suffix}.js"),
        Escape::shellArg($download_url)
      );
      $wget = Drush::process($cmd);
      $result = $wget->run();
      if ($result !== 0) {
        throw new LibraryException(dt('Could not download file @file.', [
          '@file' => $download_url,
        ]));
      }
    }

    $this->logger()->notice('Library downloaded successfully');
  }

}
