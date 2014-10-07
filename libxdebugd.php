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

	public function getResponse($debug=FALSE)
	{
		while(1)
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
				if($count > 1024)
				{
					$chunk = 1024;
					$data = fread($this->sock, $chunk);
					$totalRead = 1024;
					while($totalRead < $count)
					{
						if(($totalRead + $chunk) > $count)
						{
							$chunk = $count - $totalRead; //$count%$chunk;
						}

						$tmp = fread($this->sock, $chunk);
						$totalRead += strlen($tmp); // get the actual read bytes
						$data .= $tmp;
					}
				}
				else
				{
					$data = fread($this->sock, $count);

				}
				return $data;
			}
			sleep(0.1);
		}
		return FALSE;
	}

	public function getSessions()
	{
		$packet = '<request command="getSessions"></request>';
		$packetLen = (string)strlen($packet)-1;
		if(FALSE !== fwrite($this->sock, $packetLen."\0".$packet))
		{
			return $this->getResponse();
		}
		return FALSE;
	}

	public function init($idekey='grease')
	{
		$packet = '<request command="init" idekey="'.$idekey.'"></request>';
		$packetLen = (string)strlen($packet)-1;
		if(FALSE !== fwrite($this->sock, $packetLen."\0".$packet))
		{
			return $this->getResponse();
		}
		return FALSE;
	}

	public function stepInto($idekey='grease')
	{
		$packet = '<request command="stepInto" idekey="'.$idekey.'"></request>';
		$packetLen = (string)strlen($packet)-1;
		if(FALSE !== fwrite($this->sock, $packetLen."\0".$packet))
		{
			return $this->getResponse();
		}
		return FALSE;
	}

	public function getStack($idekey='grease')
	{
		$packet = '<request command="stackGet" idekey="'.$idekey.'"></request>';
		$packetLen = (string)strlen($packet)-1;
		if(FALSE !== fwrite($this->sock, $packetLen."\0".$packet))
		{
			return $this->getResponse();
		}
		return FALSE;
	}

	public function getContext($idekey='grease')
	{
		$packet = '<request command="contextGet" idekey="'.$idekey.'"></request>';
		$packetLen = (string)strlen($packet)-1;
		if(FALSE !== fwrite($this->sock, $packetLen."\0".$packet))
		{
			return $this->getResponse();
		}
		return FALSE;
	}

	public function breakpointSetLine($idekey='grease', $filename, $lineno)
	{
		$packet = '<request command="breakpointSetLine" idekey="'.$idekey.'" filename="file://'.$filename.'" lineno="'.$lineno.'"></request>';
		$packetLen = (string)strlen($packet)-1;
		if(FALSE !== fwrite($this->sock, $packetLen."\0".$packet))
		{
			return $this->getResponse();
		}
		return FALSE;
	}

	public function breakpointRemoveLine($idekey='grease', $breakpointId)
	{
		$packet = '<request command="breakpointRemoveLine" idekey="'.$idekey.'" breakpointId="'.$breakpointId.'"></request>';
		$packetLen = (string)strlen($packet)-1;
		if(FALSE !== fwrite($this->sock, $packetLen."\0".$packet))
		{
			return TRUE;
		}
		return FALSE;
	}

	public function run($idekey='grease')
	{
		$packet = '<request command="run" idekey="'.$idekey.'"></request>';
		$packetLen = (string)strlen($packet)-1;
		if(FALSE !== fwrite($this->sock, $packetLen."\0".$packet))
		{
echo 'waiting for resp';
			return $this->getResponse();
		}
		return FALSE;
	}

	public function stop($idekey='grease')
	{
		$packet = '<request command="stop" idekey="'.$idekey.'"></request>';
		$packetLen = (string)strlen($packet)-1;
		if(FALSE !== fwrite($this->sock, $packetLen."\0".$packet))
		{
			return $this->getResponse();
		}
		return FALSE;
	}
}
