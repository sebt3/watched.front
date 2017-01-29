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

	public function getSockets($id) {
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

	public function getProcess($id) {
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

	public function getService($id) {
		$ret['process'] = $this->getProcess($id);
		$ret['sockets'] = $this->getSockets($id);
		$ret['name'] = $ret['process'][0]['serv_name'];
		$ret['id'] = $id;
		return $ret;
	}

	public function getHostServices($id) {
		$ret = [];
		$s   = $this->db->prepare('select id, name from s$services where host_id=:id');
		$s->bindParam(':id', $id, PDO::PARAM_INT);
		$s->execute();
		while($r = $s->fetch())
			$ret[] = $this->getService($r['id']);
		return $ret;
	}

/*
overall availability :
select ok*100/(failed+ok+missing) as avail from (select sum(failed) as failed, sum(missing) as missing, sum(ok) as ok from serviceHistory where serv_id=11) x
*/

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
		//$this->logger->addInfo("HostServices $hid - $sid");
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
}

?>
