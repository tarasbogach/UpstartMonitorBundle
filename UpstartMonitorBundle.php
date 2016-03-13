<?php

namespace SfNix\UpstartMonitorBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class UpstartMonitorBundle extends Bundle{

	public function createAccessToken(){
		#We use strong encryption with random Initialization Vector,
		#so I think we do not need any more salt,
		#or user related data (IP, User agent, etc.).
		$token = $this->container->get('security.context')->getToken();
		$time = microtime(true);
		$data = json_encode([
			'time' => $time,
			'username' => $token->getUsername(),
		]);
		return $this->encrypt($data);
	}

	public function checkAccessToken($token){
		$data = $this->decrypt($token);
		if(!$data){
			return false;
		}
		$data = json_decode($data, true);
		if(!$data || !isset($data['time'])){
			return false;
		}
		if(microtime(true) - $data['time'] > 30){
			return false;
		}
		return $data;
	}

	protected function encrypt($data){
		$password = $this->container->getParameter('secret');
		if(function_exists('openssl_encrypt')){
			$alg = 'aes-256-cbc';
			$ivLen = openssl_cipher_iv_length($alg);
			$iv = openssl_random_pseudo_bytes($ivLen);
			$enc = openssl_encrypt($data, $alg, $password, true, $iv);
			return bin2hex($iv.$enc);
		}elseif(function_exists('mcrypt_encrypt')){
			$alg = MCRYPT_RIJNDAEL_256;
			$mode = MCRYPT_MODE_CBC;
			$ivLen = mcrypt_get_iv_size($alg, $mode);
			$iv = mcrypt_create_iv($ivLen);
			$enc = mcrypt_encrypt($alg, $password, $data, $mode, $iv);
			return bin2hex($iv.$enc);
		}else{
			throw new AccessDeniedException("You need openssl or mcrypt php extension to be enabled.");
		}
	}

	protected function decrypt($data){
		$data = hex2bin($data);
		$password = $this->container->getParameter('secret');
		if(function_exists('openssl_decrypt')){
			$alg = 'aes-256-cbc';
			$ivLen = openssl_cipher_iv_length($alg);
			$iv = substr($data, 0, $ivLen);
			$enc = substr($data, $ivLen);
			return openssl_decrypt($enc, $alg, $password, true, $iv);
		}elseif(function_exists('mcrypt_decrypt')){
			$alg = MCRYPT_RIJNDAEL_256;
			$mode = MCRYPT_MODE_CBC;
			$ivLen = mcrypt_get_iv_size($alg, $mode);
			$iv = substr($data, 0, $ivLen);
			$enc = substr($data, $ivLen);
			return mcrypt_decrypt($alg, $password, $enc, $mode, $iv);
		}else{
			throw new AccessDeniedException("You need openssl or mcrypt php extension to be enabled.");
		}
	}
}
