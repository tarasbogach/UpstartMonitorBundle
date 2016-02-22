<?php

namespace SfNix\UpstartMonitorBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface{

	/**
	 * {@inheritdoc}
	 */
	public function getConfigTreeBuilder(){
		$treeBuilder = new TreeBuilder();
		$rootNode = $treeBuilder->root('upstart_monitor');
		$rootNode->children()
			->arrayNode('server')->isRequired()
				->children()
					->scalarNode('host')->defaultValue('127.0.0.1')->isRequired()->end()
					->integerNode('port')->defaultValue(13000)->min(0)->isRequired()->end()
				->end()
			->end()
			->arrayNode('client')->isRequired()
				->children()
					->scalarNode('url')->defaultValue('ws://127.0.0.1:13000')->isRequired()->end()
				->end()
			->end()
		;

		return $treeBuilder;
	}
}
