<?php

namespace SfNix\UpstartMonitorBundle\Command;

use Ratchet\ConnectionInterface;
use Ratchet\Http\HttpServer;
use Ratchet\MessageComponentInterface;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use SfNix\UpstartMonitorBundle\UpstartMonitorBundle;
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
	/**
	 * @var UpstartMonitorBundle
	 */
	protected $bundle;

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
		$this->bundle = $this->getContainer()
			->get('kernel')
			->getBundle('UpstartMonitorBundle');
		$http = new HttpServer(new WsServer($this));
		$this->server = IoServer::factory(
			$http,
			$this->monitorConfig['server']['port'],
			$this->monitorConfig['server']['host']
		);
		$this->server->run();
		$this->writeLog('Shutting down.');
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
			}
		});
	}

	public function onStateReceived($state){
		$msg = json_encode(['type' => 'state', 'data' => $state]);
		foreach($this->clients as $client){
			/**
			 * @var ConnectionInterface $client
			 */
			$client->send($msg);
		}
	}

	public function writeLog($message, $data = []){
		$this->output->writeln(date(DATE_W3C)." $message ".json_encode($data));
	}

	public function onOpen(ConnectionInterface $conn){
		$accessToken = $conn->WebSocket->request->getQuery()['accessToken'];
		$accessToken = $this->bundle->checkAccessToken($accessToken);
		if(!$accessToken){
			$this->writeLog("Access deny.");
			$msg = json_encode(['type' => 'accessDeny']);
			$conn->send($msg);
			$conn->close();
			return;
		}
		$this->writeLog("Client is connected.", $accessToken);
		$this->clients->attach($conn);
		if(count($this->clients) == 1){
			$this->getState();
		}
	}

	protected function onChangeState($goal, $job = null, $tag = null){
		$procect = $this->jobConfig['project'];
		if($job){
			popen("$goal ".escapeshellarg("$procect/$job"), 'r');
		}elseif($tag){
			if(!in_array($tag, $this->jobConfig['tagNames'], true)){
				return $this->onHack();
			}
			popen("initctl emit ".escapeshellarg("$procect.$tag.$goal"), 'r');
		}else{
			popen("initctl emit ".escapeshellarg("$procect.$goal"), 'r');
		}
		return true;
	}

	protected function onHack(){
		$this->writeLog("Hacker detected!");
	}

	protected function onStart($job = null, $tag = null){
		$this->onChangeState('start', $job, $tag);

	}
	protected function onStop($job = null, $tag = null){
		$this->onChangeState('stop', $job, $tag);
	}

	protected function onLog($job = null, $tag = null){

	}

	public function onMessage(ConnectionInterface $from, $msg){
		$msg = json_decode($msg, true);
		$this->writeLog("Message is received.", $msg);
		switch($msg['type']){
			case 'action':
				$action = $msg['data']['action'];
				$job = $msg['data']['job'];
				$tag = $msg['data']['tag'];
				switch($action){
					case 'start':
						$this->onStart($job, $tag);
						break;
					case 'stop':
						$this->onStop($job, $tag);
						break;
					case 'restart':
						$this->onStop($job, $tag);
						$this->onStart($job, $tag);
						break;
					case 'log':
						$this->onLog($job, $tag);
						break;
				}
				break;
		}

	}

	public function onClose(ConnectionInterface $conn){
		$this->clients->detach($conn);
	}

	public function onError(ConnectionInterface $conn, \Exception $e){
		$conn->close();
	}
}
