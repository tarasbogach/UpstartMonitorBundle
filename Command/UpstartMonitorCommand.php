<?php

namespace SfNix\UpstartMonitorBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpstartMonitorCommand extends ContainerAwareCommand{

	protected function configure(){
		$this
			->setName('upstart:monitor')
			->setDescription('Start upstart:monitor socket server.');
	}

	protected function execute(InputInterface $input, OutputInterface $output){


		$output->writeln('Command result.');
	}

}
