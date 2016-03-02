<?php

namespace SfNix\UpstartMonitorBundle\Command;

use Ratchet\ConnectionInterface;
use Ratchet\Http\HttpServer;
use Ratchet\MessageComponentInterface;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

//use Symfony\Component\Console\Input\InputArgument;
//use Symfony\Component\Console\Input\InputOption;

class UpstartMonitorCommand
	extends ContainerAwareCommand
	implements MessageComponentInterface{

	/**
	 * @var \SplObjectStorage
	 */
	protected $clients;

	/**
	 * @var OutputInterface
	 */
	protected $output;

	protected $monitorConfig;

	protected $jobConfig;

	/**
	 * @var IoServer
	 */
	protected $server;

	protected function configure(){
		$this
			->setName('upstart:monitor')
			->setDescription('Start upstart:monitor socket server.');
	}

	protected function execute(InputInterface $input, OutputInterface $output){
		$this->monitorConfig = $this->getContainer()->getParameter('upstart_monitor');
		$this->jobConfig = $this->getContainer()->getParameter('upstart');
		$this->clients = new \SplObjectStorage;
		$this->output = $output;
		$http = new HttpServer(new WsServer($this));
		$this->server = IoServer::factory(
			$http,
			$this->monitorConfig['server']['port'],
			$this->monitorConfig['server']['host']
		);
		$this->server->run();
		$output->writeln('Shutting down.');
	}

	public function getState(){
		$project = escapeshellarg($this->jobConfig['project'] . '/');
		$fp = popen("initctl list | grep $project", 'r');
		$state = [];
		$this->server->loop->addReadStream($fp, function($fp) use (&$state){
			if(feof($fp)){
				$this->server->loop->removeReadStream($fp);
				if(count($this->clients) > 0){
					$this->onStateReceived($state);
					$this->server->loop->addTimer(1., [$this, 'getState']);
				}
			}else{
				$line = trim(fgets($fp));
				if(!$line){
					return;
				}
				$match = [];
				preg_match(
					'@^
						((\\S*?)/(\\S*?)(\.instance)?)
						(\\s\((\\S*?)\))?
						(\\s(\\S*?)/(\\S*?))?
						(,\\sprocess\\s(\\d+))?
					$@x',
					$line,
					$match
				);
				list(
						,//[0] => imagin/test.instance (2) start/running, process 10110
						,//[1] => imagin/test.instance
						,//[2] => imagin
						$job,//[3] => test
						$instance,//[4] => .instance
						,//[5] =>  (2)
						,//$env//[6] => 2
						,//[7] =>  start/running
						$goal,//[8] => start
						$status,//[9] => running
						) = $match;
				$instance = (int)(bool)$instance;
				if(!isset($state[$job])){
					$state[$job] = [0, 0];
				}
				if($goal == 'start'){
					$state[$job][$instance]++;
				}
//				if(!in_array("$goal/$status", ["stop/waiting","start/running"])){
//					$this->output->writeln("$goal/$status");
//				}
			}
		});
	}

	public function onStateReceived($state){
//		$this->output->writeln(json_encode($state));
		$state = json_encode(['type' => 'state', 'data' => $state]);
		foreach($this->clients as $client){
			/**
			 * @var ConnectionInterface $client
			 */
			$client->send($state);
		}
	}

	public function onOpen(ConnectionInterface $conn){
		$this->output->writeln("client is connected");
		$this->clients->attach($conn);
		if(count($this->clients) == 1){
			$this->getState();
		}
	}

	public function onMessage(ConnectionInterface $from, $msg){
		$this->output->writeln("message is received " . $msg);

	}

	public function onClose(ConnectionInterface $conn){
		$this->clients->detach($conn);
	}

	public function onError(ConnectionInterface $conn, \Exception $e){
		$conn->close();
	}
}
