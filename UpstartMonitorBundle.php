<?php

namespace SfNix\UpstartMonitorBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class UpstartMonitorBundle extends Bundle{

	public function encrypt($data){
		$password = $this->container->getParameter('secret');
		if(function_exists('openssl_encrypt')){
			return openssl_encrypt($data, 'aes-256-cbc', $password, true);
		}elseif(function_exists('mcrypt_encrypt')){
			return mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $password, $data, MCRYPT_MODE_CBC);
		}else{
			throw new \Exception("You need openssl or mcrypt php extension to be enabled.");
		}
	}

	public function decrypt($data){
		$password = $this->container->getParameter('secret');
		if(function_exists('openssl_decrypt')){
			return openssl_decrypt($data, 'aes-256-cbc', $password, true);
		}elseif(function_exists('mcrypt_decrypt')){
			return mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $password, $data, MCRYPT_MODE_CBC);
		}else{
			throw new \Exception("You need openssl or mcrypt php extension to be enabled.");
		}
	}
}
