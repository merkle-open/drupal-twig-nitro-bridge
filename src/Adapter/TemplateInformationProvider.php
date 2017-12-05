<?php

namespace Drupal\twig_nitro_bridge\Adapter;

use Deniaz\Terrific\Config\ConfigReader;
use Deniaz\Terrific\Provider\TemplateInformationProviderInterface;
use Drupal\Core\Config\ConfigFactory as DrupalConfigFactory;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Class TemplateInformationProvider.
 *
 * @package Drupal\twig_nitro_bridge\Adapter
 */
class TemplateInformationProvider implements TemplateInformationProviderInterface {
  /**
   * List of paths where templates are stored.
   *
   * @var array
   */
  private $paths = [];

  /**
   * Path to Frontend Directory.
   *
   * @var string
   */
  private $basePath = '';

  /**
   * Terrific's config.json Content.
   *
   * @var array
   */
  private $terrificConfig = [];

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * TemplateLocator constructor.
   *
   * @param DrupalConfigFactory $config_factory
   *    Config factory param.
   * @param FileSystemInterface $filesystem
   *    FileSystem.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger factory service.
   */
  public function __construct(
    DrupalConfigFactory $config_factory,
    FileSystemInterface $filesystem,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->basePath = $filesystem
      ->realpath(
        DRUPAL_ROOT . '/' . $config_factory->get('twig_nitro_bridge.settings')->get('frontend_dir')
      );

    $this->terrificConfig = (new ConfigReader($this->basePath))->read();
    $this->logger = $logger_factory->get('twig_nitro_bridge');
  }

  /**
   * Returns a list of paths where templates can be found.
   *
   * @return array
   *    Return array of paths.
   */
  public function getPaths() {
    if (empty($this->paths)) {
      $this->generatePaths();
    }

    return $this->paths;
  }

  /**
   * Generate Paths array from Terrific Configuration.
   */
  private function generatePaths() {
    $components = $this->terrificConfig['nitro']['components'];
    foreach ($components as $name => $component) {
      $this->paths[$name] = $this->basePath . '/' . $component['path'];
    }
  }

  /**
   * File extension.
   *
   * @return mixed
   *    Template File Extension.
   *
   * @throws \Drupal\twig_nitro_bridge\Adapter\DomainException
   *    Exception.
   */
  public function getFileExtension() {
    $fileExtension = $this->terrificConfig['nitro']['view_file_extension'];
    if (!isset($fileExtension)) {
      $this->logger->notice('Frontend Template File Extension not defined in Terrific\'s Configuration File.');
    }

    return $fileExtension;
  }

}
