<?php

/*
 *  Copyright (c) 2010-2013 Tinyboard Development Group
 */

defined('TINYBOARD') or exit;

class Remote {
	public function __construct($config) {
		foreach ($config as $name => $value) {
			$this->{$name} = $value;
		}
		
		$methods = array();
		
		if (!isset($this->auth['method']))
			error('Unspecified authentication method.');
		
		// Connect
		$this->connection = ssh2_connect($this->host, isset($this->port) ? $this->port : 22, $methods);
		
		switch ($this->auth['method']) {
			case 'pubkey':
				
				if (!isset($this->auth['public']))
					error('Public key filename not specified.');
				if (!isset($this->auth['private']))
					error('Private key filename not specified.');
				
				if (!ssh2_auth_pubkey_file($this->connection, $this->auth['username'], $this->auth['public'], $this->auth['private'], isset($this->auth['passphrase']) ? $this->auth['passphrase']: null))
					error('Public key authentication failed.');
				break;
			case 'plain':
				if (!ssh2_auth_password($this->connection, $this->auth['username'], $this->auth['password']))
					error('Plain-text authentication failed.');
				break;
			default:
				error('Unknown authentication method: "' . $this->auth['method'] . '".');
		}
		
	}
	
	public function write($data, $remote_path) {
		global $config;
		
		switch ($this->type) {
			case 'sftp':
				$sftp = ssh2_sftp($this->connection);
				file_write('ssh2.sftp://' . $sftp . $remote_path, $data, true);
				break;
			case 'scp':
				$file = tempnam($config['tmp'], 'tinyboard-scp');
				// Write to temp file
				file_write($file, $data);
				
				ssh2_scp_send($this->connection, $file, $remote_path, 0755);
				break;
			default:
				error('Unknown send method.');
		}
	}
};

