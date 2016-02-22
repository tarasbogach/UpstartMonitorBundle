<?php

namespace SfNix\UpstartMonitorBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller{

	public function indexAction(){
		$cnf = [];
		$upstart = $this->container->getParameter('upstart');
		$upstart_monitor = $this->container->getParameter('upstart_monitor');
		$cnf['client'] = $upstart_monitor['client'];
		foreach($upstart['job'] as $job){
			$item = [
				'name' => $job['name'],
				'tag' => $job['tag'],
				'quantity' => $job['quantity'],
			];
			$cnf['job'][$job['name']] = $item;
			foreach($job['tag'] as $tagName){
				$cnf['tag'][$tagName]['name']= $tagName;
				$cnf['tag'][$tagName]['job'][$job['name']] = $item;
			}
		}
		$cnf = json_encode($cnf);
		return $this->render(
			'UpstartMonitorBundle:Default:index.html.twig',
			[
				'ns' => 'UpstartMonitor-',
				'el' => 'UpstartMonitor-el-',
				'cnf' => $cnf,
			]
		);
	}
}
