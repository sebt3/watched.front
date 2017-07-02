<?php
use Interop\Container\ContainerInterface;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

class HostService extends CorePage {
	public function __construct(ContainerInterface $ci) { 
		parent::__construct($ci);
	}

/////////////////////////////////////////////////////////////////////////////////////////////
// Model
/*	public function getHostSockets($id) {
		$ret = [];
		$s   = $this->db->prepare('select sp.serv_id, s.name as serv_name, sp.name as socket_name, sp.status, sp.timestamp, (UNIX_TIMESTAMP()*1000-sp.timestamp)/1000 as late_sec from services s, serviceSockets sp where s.host_id=:id and s.id = sp.serv_id');
		$s->bindParam(':id', $id, PDO::PARAM_INT);
		$s->execute();
		while($r = $s->fetch()) {
			$r['timestamp'] = $this->formatTimestamp($r['timestamp']);
			$ret[] = $r;
		}
		return $ret;
	}

	public function getHostProcess($id) {
		$ret = [];
		$s   = $this->db->prepare('select sp.serv_id, s.name as serv_name, sp.name as process_name, sp.full_path, sp.cwd, sp.username, sp.pid, sp.status, sp.timestamp, (UNIX_TIMESTAMP()*1000-sp.timestamp)/1000 as late_sec from services s, serviceProcess sp where s.host_id=1 and s.id = sp.serv_id');
		$s->bindParam(':id', $id, PDO::PARAM_INT);
		$s->execute();
		while($r = $s->fetch()) {
			$r['timestamp'] = $this->formatTimestamp($r['timestamp']);
			$ret[] = $r;
		}
		return $ret;
	}*/

	private function getProcessTable($id) {
		$_ = $this->trans;
		$ret = [];
		$ret['title'] = $_('Process');
		$ret['cols'] = [];
		$ret['cols'][] = array( 'text' => $_('name'), 'class'=> 'sortable');
		$ret['cols'][] = array( 'text' => $_('path'), 'class'=> 'sortable');
		$ret['cols'][] = array( 'text' => $_('cwd'), 'class'=> 'sortable');
		$ret['cols'][] = array( 'text' => $_('username'), 'class'=> 'sortable');
		$ret['cols'][] = array( 'text' => $_('last check'), 'class'=> 'sortable');
		$ret['cols'][] = array( 'text' => $_('status'), 'class'=> 'sortable');
		$ret['body'] = [];
		$s   = $this->db->prepare('select sp.serv_id, s.name as serv_name, sp.name as process_name, sp.full_path, sp.cwd, sp.username, sp.pid, sp.status, sp.timestamp, (UNIX_TIMESTAMP()*1000-sp.timestamp)/1000 as late_sec
  from s$services s, s$process sp
 where s.id=:id
   and s.id = sp.serv_id');
		$s->bindParam(':id', $id, PDO::PARAM_INT);
		$s->execute();
		while($r = $s->fetch()) {
			$ret['body'][] = array(
				'name'	=> array('text'	=> $r['process_name']),
				'path'	=> array('text'	=> $r['full_path']),
				'cwd'	=> array('text'	=> $r['cwd']),
				'uname'	=> array('text'	=> $r['username']),
				'time'	=> array('text'	=> $this->formatTimestamp($r['timestamp'])),
				'status'=> array('text'	=> $r['status'], 'color' => $this->getStatusColor($r['status'], $r['late_sec']))
			);
		}
		return $ret;
	}

	private function getSocketsTable($id) {
		$_ = $this->trans;
		$ret = [];
		$ret['title'] = $_('Sockets');
		$ret['cols'] = [];
		$ret['cols'][] = array( 'text' => $_('listen'), 'class'=> 'sortable');
		$ret['cols'][] = array( 'text' => $_('last check'), 'class'=> 'sortable');
		$ret['cols'][] = array( 'text' => $_('status'), 'class'=> 'sortable');
		$ret['body'] = [];
		$s   = $this->db->prepare('select sp.serv_id, s.name as serv_name, sp.name as socket_name, sp.status, sp.timestamp, (UNIX_TIMESTAMP()*1000-sp.timestamp)/1000 as late_sec 
  from s$services s, s$sockets sp 
 where s.id=:id
   and s.id = sp.serv_id');
		$s->bindParam(':id', $id, PDO::PARAM_INT);
		$s->execute();
		while($r = $s->fetch()) {
			$ret['body'][] = array(
				'name'	=> array('text'	=> $r['socket_name']),
				'time'	=> array('text'	=> $this->formatTimestamp($r['timestamp'])),
				'status'=> array('text'	=> $r['status'], 'color' => $this->getStatusColor($r['status'], $r['late_sec']))
			);
		}
		return $ret;
	}


	private function getService($id) {
		$s   = $this->db->prepare('select id, host_id, name, type_id from s$services where id=:id');
		$s->bindParam(':id', $id, PDO::PARAM_INT);
		$s->execute();
		while($r = $s->fetch())
			return $r;
		return false;
	}

	private function getSockets($id) {
		$ret = [];
		$s   = $this->db->prepare('select sp.serv_id, s.name as serv_name, sp.name as socket_name, sp.status, sp.timestamp, (UNIX_TIMESTAMP()*1000-sp.timestamp)/1000 as late_sec 
  from s$services s, s$sockets sp 
 where s.id=:id
   and s.id = sp.serv_id');
		$s->bindParam(':id', $id, PDO::PARAM_INT);
		$s->execute();
		while($r = $s->fetch()) {
			$r['timestamp'] = $this->formatTimestamp($r['timestamp']);
			$r['color']	= $this->getStatusColor($r['status'], $r['late_sec']);
			$ret[] = $r;
		}
		return $ret;
	}

	private function getProcess($id) {
		$ret = [];
		$s   = $this->db->prepare('select sp.serv_id, s.name as serv_name, sp.name as process_name, sp.full_path, sp.cwd, sp.username, sp.pid, sp.status, sp.timestamp, (UNIX_TIMESTAMP()*1000-sp.timestamp)/1000 as late_sec
  from s$services s, s$process sp
 where s.id=:id
   and s.id = sp.serv_id');
		$s->bindParam(':id', $id, PDO::PARAM_INT);
		$s->execute();
		while($r = $s->fetch()) {
			$r['timestamp'] = $this->formatTimestamp($r['timestamp']);
			$r['color']	= $this->getStatusColor($r['status'], $r['late_sec']);
			$ret[] = $r;
		}
		return $ret;
	}

	private function getHostServices($id) {
		$ret = [];
		$s   = $this->db->prepare('select id, name from s$services where host_id=:id');
		$s->bindParam(':id', $id, PDO::PARAM_INT);
		$s->execute();
		while($r = $s->fetch()) {
			$t	= $this->getService($r['id']);
			$t['process'] = $this->getProcess($r['id']);
			$t['sockets'] = $this->getSockets($r['id']);
			$ret[] = $t;
		}
		return $ret;
	}

	private function getServOverAllAvail($id) {
		$_ = $this->trans;
		$ret = [];
		$ret['title'] = $_('Overall availability');
		$ret['body'] = [];
		$s   = $this->db->prepare('select d.id, d.name as label, if(d.id=0, x.ok, if(d.id=1,x.failed,x.missing))/(x.ok+x.failed+x.missing)*100 as value
  from	(select 2 as id, "missing" as name from dual union all select 1 as id, "failed" as name from dual union all select 0 as id, "ok" as name from dual) d,
	(select sum(failed) as failed, sum(missing) as missing, sum(ok) as ok from s$history where serv_id=:id) x');
		$s->bindParam(':id', $id, PDO::PARAM_INT);
		$s->execute();
		while($r = $s->fetch()) {
			$r['color'] = $this->getEventColor($r['label']);
			$ret['body'][] = $r;
			
		}
		return $ret;
	}

	private function getServRess($id) {
		$_ = $this->trans;
		$ret = [];
		$ret['title'] = $_('Ressources');
		$ret['body'] = [];
		$s   = $this->db->prepare('select s.serv_id, s.res_id, c.name, c.name as text, c.data_type from s$ressources s, c$ressources c where c.id=s.res_id and s.serv_id=:id');
		$s->bindParam(':id', $id, PDO::PARAM_INT);
		$s->execute();
		while($r = $s->fetch()) {
			$r['url'] = $this->router->pathFor('services.ressource', [ 'serv_id' => $r['serv_id'], 'res_id' => $r['res_id']]);
			$ret['body'][] = $r;
			
		}
		return $ret;
	}
	
	private function getServLog($id) {
		$_ = $this->trans;
		$ret = [];
		$ret['title'] = $_('Logs');
		$ret['cols'] = [];
		$ret['cols'][] = array( 'text' => $_('type'), 'class'=> 'sortable');
		$ret['cols'][] = array( 'text' => $_('time'), 'class'=> 'sortable');
		$ret['cols'][] = array( 'text' => $_('source'), 'class'=> 'sortable');
		$ret['cols'][] = array( 'text' => $_('line number'), 'class'=> 'sortable');
		$ret['cols'][] = array( 'text' => $_('text'), 'class'=> 'sortable');
		$ret['body'] = [];
		$s   = $this->db->prepare('select l.id, l.timestamp, l.source_name, l.date_field, l.line_no, l.text, t.name as event_name from s$log_events l, c$event_types t where t.id=l.event_type and serv_id=:id order by l.timestamp desc limit 30');
		$s->bindParam(':id', $id, PDO::PARAM_INT);
		$s->execute();
		while($r = $s->fetch()) {
			$ret['body'][] = array(
				'type'	=> array('text' => $r['event_name'], 
						'color'	=> $this->getEventTextColor($r['event_name'])),
				'stime'	=> array('text'	=> $this->formatTimestamp($r['timestamp'])),
				'src'	=> array('text' => $r['source_name']),
				'lno'	=> array('text' => floatval($r['line_no'])),
				'text'	=> array('text' => $r['text'])
			);
			
		}
		return $ret;
	}
	
/////////////////////////////////////////////////////////////////////////////////////////////
// WidgetControlers

	public function widgetTableProcess(Request $request, Response $response) {
		$id = $request->getAttribute('id');
		if ($this->getService($id) == false)
			throw new Slim\Exception\NotFoundException($request, $response);
		$this->auth->assertService($id, $request, $response);
		$response->getBody()->write(json_encode($this->getProcessTable($id)));
		return $response->withHeader('Content-type', 'application/json');
	}
	public function widgetTableSockets(Request $request, Response $response) {
		$id = $request->getAttribute('id');
		if ($this->getService($id) == false)
			throw new Slim\Exception\NotFoundException($request, $response);
		$this->auth->assertService($id, $request, $response);
		$response->getBody()->write(json_encode($this->getSocketsTable($id)));
		return $response->withHeader('Content-type', 'application/json');
	}
	public function widgetDonutAvail(Request $request, Response $response) {
		$id = $request->getAttribute('id');
		if ($this->getService($id) == false)
			throw new Slim\Exception\NotFoundException($request, $response);
		$this->auth->assertService($id, $request, $response);
		$response->getBody()->write(json_encode($this->getServOverAllAvail($id)));
		return $response->withHeader('Content-type', 'application/json');
	}
	public function widgetListResources(Request $request, Response $response) {
		$id = $request->getAttribute('id');
		if ($this->getService($id) == false)
			throw new Slim\Exception\NotFoundException($request, $response);
		$this->auth->assertService($id, $request, $response);
		$response->getBody()->write(json_encode($this->getServRess($id)));
		return $response->withHeader('Content-type', 'application/json');
	}
	public function widgetTableLog(Request $request, Response $response) {
		$id = $request->getAttribute('id');
		if ($this->getService($id) == false)
			throw new Slim\Exception\NotFoundException($request, $response);
		$this->auth->assertService($id, $request, $response);
		$response->getBody()->write(json_encode($this->getServLog($id)));
		return $response->withHeader('Content-type', 'application/json');
	}

/////////////////////////////////////////////////////////////////////////////////////////////
// Controlers
	public function hostServices (Request $request, Response $response) {
		$id = $request->getAttribute('id');
		//$this->logger->addInfo("HostServices $id");
		$host = $this->getHost($id);
		if ($host == false)
			throw new Slim\Exception\NotFoundException($request, $response);
		$this->auth->assertHost($id, $request, $response);

		$this->menu->activateHost($host['host']);
		$this->menu->breadcrumb = array(
			array('name' => 'host', 'icon' => 'fa fa-server', 'url' => $this->router->pathFor('hosts')), 
			array('name' => $host['host'], 'url' => $this->router->pathFor('host', array('id' => $id))), 
			array('name' => 'services', 'icon' => 'fa fa-building-o', 'url' => $this->router->pathFor('services', array('id' => $id))));
		return $this->view->render($response, 'hosts/hostServices.twig', [ 
			'h'		=> $host,
			'services'	=> $this->getHostServices($id)
		]);
	}

	public function hostService (Request $request, Response $response) {
		$hid = $request->getAttribute('hid');
		$sid = $request->getAttribute('sid');
		$host = $this->getHost($hid);
		$serv = $this->getService($sid);
		if ($host == false || $serv == false)
			throw new Slim\Exception\NotFoundException($request, $response);
		$this->auth->assertHost($hid, $request, $response);
		$this->auth->assertService($sid, $request, $response);

		$this->menu->activateHost($host['host']);
		$this->menu->breadcrumb = array(
			array('name' => 'host', 'icon' => 'fa fa-server', 'url' => $this->router->pathFor('hosts')), 
			array('name' => $host['host'], 'url' => $this->router->pathFor('host', array('id' => $hid))), 
			array('name' => 'services', 'icon' => 'fa fa-building-o', 'url' => $this->router->pathFor('services', array('id' => $hid))),
			array('name' => $serv['name'], 'url' => $this->router->pathFor('service', array('hid' => $hid, 'sid' => $sid))));
		return $this->view->render($response, 'hosts/hostService.twig', [ 
			'h'		=> $host,
			'service'	=> $serv
		]);
	}
	public function serviceRessource (Request $request, Response $response) {
		$rid = $request->getAttribute('res_id');
		$sid = $request->getAttribute('serv_id');
		$serv = $this->getService($sid);
		$host = $this->getHost($serv['host_id']);
		$res = $this->getRessource($rid);
		if ($host == false || $serv == false)
			throw new Slim\Exception\NotFoundException($request, $response);
		$this->auth->assertHost($serv['host_id'], $request, $response);
		$this->auth->assertService($sid, $request, $response);

		$this->menu->activateHost($host['host']);
		$this->menu->breadcrumb = array(
			array('name' => 'host', 'icon' => 'fa fa-server', 'url' => $this->router->pathFor('hosts')), 
			array('name' => $host['host'], 'url' => $this->router->pathFor('host', array('id' => $host['id']))), 
			array('name' => 'services', 'icon' => 'fa fa-building-o', 'url' => $this->router->pathFor('services', array('id' => $host['id']))),
			array('name' => $serv['name'], 'url' => $this->router->pathFor('service', array('hid' => $host['id'], 'sid' => $sid))),
			array('name' => $res['name'], 'url' => $this->router->pathFor('services.ressource', array('serv_id' => $sid, 'res_id' => $rid)))
		);
		return $this->view->render($response, 'hosts/servRessource.twig', [ 
			'h'		=> $host,
			'res'		=> $res,
			'service'	=> $serv
		]);
	}
}
