<?php

/*
 This file is part of Grease
 http://github.com/AndrewRose/Grease
 http://andrewrose.co.uk
 License: GPL; see below
 Copyright Andrew Rose (hello@andrewrose.co.uk) 2014

    Grease is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Grease is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Grease.  If not, see <http://www.gnu.org/licenses/>
*/

class libxdebugd
{
	private $sock;
	public $connected = FALSE;

	public function __construct()
	{

	}

	public function connect($idekey='xdebugd', $ip='127.0.0.1', $port=9000)
	{
		$this->sock = stream_socket_client('tcp://'.$ip.':'.$port, $errno, $errstr, 30);
		stream_set_chunk_size($this->sock, 1024);

		if (!$this->sock)
		{
// throw error!
			echo "Failed to connect to xdebugd.php server!\n";
			return FALSE;
		}
		$this->connected = TRUE;
		return TRUE;
	}

	public function getResponse()
	{
		$count = '';
		$ch = fread($this->sock, 1);
		while($ch!="\0")
		{
			$count .= $ch;
			$ch = fread($this->sock, 1);
			if($ch === FALSE)
			{
				return;
			}
		}

		if($count)
		{
echo 'count: '.$count."\n";
			if($count > 1024)
			{
				$chunk = 1024;
				$data = fread($this->sock, $chunk);
				$read = 1024;
				while(strlen($data) < $count)
				{

					if(($read + $chunk) > $count)
					{
						$chunk = $count%$chunk;
if($chunk == 0)
{
echo "zero chunk!\n";
exit();
}
					}
echo 'chunk: '.$chunk."\n";
echo 'read: '.$read."\n";
					$data .= fread($this->sock, $chunk);
					$read += $chunk;
				}
			}
			else
			{
				$data = fread($this->sock, $count);
			}

			return $data;
		}
		
		return FALSE;
	}

	public function getSessions()
	{
		$packet = '<request command="getSessions"></request>';
		$packetLen = (string)strlen($packet)-1;
		fwrite($this->sock, $packetLen."\0".$packet);
		return $this->getResponse();
	}

	public function init($idekey='grease')
	{
		$packet = '<request command="init" idekey="'.$idekey.'"></request>';
		$packetLen = (string)strlen($packet)-1;
		fwrite($this->sock, $packetLen."\0".$packet);
		return $this->getResponse();
	}

	public function stepInto($idekey='grease')
	{
		$packet = '<request command="stepInto" idekey="'.$idekey.'"></request>';
		$packetLen = (string)strlen($packet)-1;
		fwrite($this->sock, $packetLen."\0".$packet);
		return $this->getResponse();
	}

	public function getStack($idekey='grease')
	{
		$packet = '<request command="stackGet" idekey="'.$idekey.'"></request>';
		$packetLen = (string)strlen($packet)-1;
		fwrite($this->sock, $packetLen."\0".$packet);
		return $this->getResponse();
	}

	public function getContext($idekey='grease')
	{
		$packet = '<request command="contextGet" idekey="'.$idekey.'"></request>';
		$packetLen = (string)strlen($packet)-1;
		fwrite($this->sock, $packetLen."\0".$packet);
		return $this->getResponse();
	}

	public function breakpointSetLine($idekey='grease', $filename, $lineno)
	{
		$packet = '<request command="breakpointSetLine" idekey="'.$idekey.'" filename="file://'.$filename.'" lineno="'.$lineno.'"></request>';
		$packetLen = (string)strlen($packet)-1;
		fwrite($this->sock, $packetLen."\0".$packet);
		return $this->getResponse();
	}

	public function breakpointRemoveLine($idekey='grease', $breakpointId)
	{
		$packet = '<request command="breakpointRemoveLine" idekey="'.$idekey.'" breakpointId="'.$breakpointId.'"></request>';
		$packetLen = (string)strlen($packet)-1;
		fwrite($this->sock, $packetLen."\0".$packet);
	}

	public function run($idekey='grease')
	{
		$packet = '<request command="run" idekey="'.$idekey.'"></request>';
		$packetLen = (string)strlen($packet)-1;
		fwrite($this->sock, $packetLen."\0".$packet);
		return $this->getResponse();
	}

	public function stop($idekey='grease')
	{
		$packet = '<request command="stop" idekey="'.$idekey.'"></request>';
		$packetLen = (string)strlen($packet)-1;
		fwrite($this->sock, $packetLen."\0".$packet);
		return $this->getResponse();
	}
}