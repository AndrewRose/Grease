<?php
/*
 step two sessions together to compare and find problems where something works for one person but not he other..
*/
namespace xdebugd;

class Xmlhttp
{
	private $methods = ['getSessions', 'run', 'init', 'stepInto', 'dump','shutdown','record'];
	private $xdebugd;

	public $sessions = [];

	public function __construct($xdebugd)
	{
		$this->xdebugd = $xdebugd;
	}

	public function handler($url, $params)
	{
		$method = str_replace('/', '', trim($url));
		if(!in_array($method, $this->methods))
		{
			return FALSE;
		}

		if(method_exists($this, $method))
		{
			return $this->$method($params);
		}
		else
		{
			return FALSE;
		}
	}

	public function getSessions($params)
	{
		$ret = [];
		foreach($this->xdebugd->servers as $idekey => $connectionId)
		{
			$tmp = ['connectionId' => $connectionId,  'server' => $this->xdebugd->connections[$connectionId], 'client' => false];

			if(isset($this->xdebugd->clients[$idekey]) && $this->xdebugd->clients[$idekey] !== FALSE)
			{
				$tmp['client'] = $this->xdebugd->connections[$this->xdebugd->clients[$idekey]];
			}

			$ret[$idekey] = $tmp;
		}

		return json_encode($ret,  JSON_PRETTY_PRINT);
	}

	public function shutdown()
	{
		exit();
	}

	public function init($params)
	{
		$this->sessions[$params['idekey']] = [];
		$this->xdebugd->clients[$params['idekey']] = FALSE;
		$this->xdebugd->init(FALSE, $params['serverId'], $params['idekey']);
		return '{}';
	}

	public function run($params)
	{
		$this->xdebugd->run(FALSE, $params['serverId'], $params['idekey']);
		return '{}';
	}

	public function dump($params)
	{
		return json_encode($this->sessions,  JSON_PRETTY_PRINT);
	}

	public function stepInto($params)
	{
		$this->xdebugd->stepInto(FALSE, $params['serverId'], $params['idekey']);
		return '{}';
	}

	public function record($params)
	{
		$this->xdebugd->record($params['idekey']);
	}
}
