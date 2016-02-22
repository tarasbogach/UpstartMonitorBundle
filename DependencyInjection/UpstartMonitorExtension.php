<?php

namespace SfNix\UpstartMonitorBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class UpstartMonitorExtension extends Extension{

	/**
	 * {@inheritdoc}
	 */
	public function load(array $configs, ContainerBuilder $container){
		$hasConfig = false;
		foreach($configs as $cnf){
			if($cnf){
				$hasConfig = true;
			}
		}
		if(!$hasConfig){
			return null;
		}
		$configuration = new Configuration();
		$config = $this->processConfiguration($configuration, $configs);
		$container->setParameter('upstart_monitor', $config);
	}
}
