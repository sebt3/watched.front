<?php
use Interop\Container\ContainerInterface;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

class HostService extends CorePage {
	public function __construct(ContainerInterface $ci) { 
		parent::__construct($ci);
	}

/*	public function getHostSockets($id) {
		$ret = [];
		$s   = $this->ci->db->prepare("select sp.serv_id, s.name as serv_name, sp.name as socket_name, sp.status, sp.timestamp, (UNIX_TIMESTAMP()*1000-sp.timestamp)/1000 as late_sec from services s, serviceSockets sp where s.host_id=:id and s.id = sp.serv_id");
		$s->bindParam(':id', $id, PDO::PARAM_INT);
		$s->execute();
		while($r = $s->fetch()) {
			$r["timestamp"] = $this->formatTimestamp($r["timestamp"]);
			$ret[] = $r;
		}
		return $ret;
	}

	public function getHostProcess($id) {
		$ret = [];
		$s   = $this->ci->db->prepare("select sp.serv_id, s.name as serv_name, sp.name as process_name, sp.full_path, sp.cwd, sp.username, sp.pid, sp.status, sp.timestamp, (UNIX_TIMESTAMP()*1000-sp.timestamp)/1000 as late_sec from services s, serviceProcess sp where s.host_id=1 and s.id = sp.serv_id");
		$s->bindParam(':id', $id, PDO::PARAM_INT);
		$s->execute();
		while($r = $s->fetch()) {
			$r["timestamp"] = $this->formatTimestamp($r["timestamp"]);
			$ret[] = $r;
		}
		return $ret;
	}*/

	public function getSockets($id) {
		$ret = [];
		$s   = $this->ci->db->prepare("select sp.serv_id, s.name as serv_name, sp.name as socket_name, sp.status, sp.timestamp, (UNIX_TIMESTAMP()*1000-sp.timestamp)/1000 as late_sec 
  from s\$services s, s\$sockets sp 
 where s.id=:id
   and s.id = sp.serv_id");
		$s->bindParam(':id', $id, PDO::PARAM_INT);
		$s->execute();
		while($r = $s->fetch()) {
			$r["timestamp"] = $this->formatTimestamp($r["timestamp"]);
			$r["color"]	= $this->getStatusColor($r["status"], $r["late_sec"]);
			$ret[] = $r;
		}
		return $ret;
	}

	public function getProcess($id) {
		$ret = [];
		$s   = $this->ci->db->prepare("select sp.serv_id, s.name as serv_name, sp.name as process_name, sp.full_path, sp.cwd, sp.username, sp.pid, sp.status, sp.timestamp, (UNIX_TIMESTAMP()*1000-sp.timestamp)/1000 as late_sec
  from s\$services s, s\$process sp
 where s.id=:id
   and s.id = sp.serv_id");
		$s->bindParam(':id', $id, PDO::PARAM_INT);
		$s->execute();
		while($r = $s->fetch()) {
			$r["timestamp"] = $this->formatTimestamp($r["timestamp"]);
			$r["color"]	= $this->getStatusColor($r["status"], $r["late_sec"]);
			$ret[] = $r;
		}
		return $ret;
	}

	public function getService($id) {
		$ret["process"] = $this->getProcess($id);
		$ret["sockets"] = $this->getSockets($id);
		$ret["name"] = $ret["process"][0]["serv_name"];
		$ret["id"] = $id;
		return $ret;
	}

	public function getHostServices($id) {
		$ret = [];
		$s   = $this->ci->db->prepare("select id, name from s\$services where host_id=:id");
		$s->bindParam(':id', $id, PDO::PARAM_INT);
		$s->execute();
		while($r = $s->fetch())
			$ret[] = $this->getService($r["id"]);
		return $ret;
	}

/*
overall availability :
select ok*100/(failed+ok+missing) as avail from (select sum(failed) as failed, sum(missing) as missing, sum(ok) as ok from serviceHistory where serv_id=11) x
*/

/////////////////////////////////////////////////////////////////////////////////////////////
// Pages
	public function hostServices (Request $request, Response $response) {
		$id = $request->getAttribute('id');
		$this->ci->logger->addInfo("HostServices $id");
		$host = $this->getHost($id);
		if ($host == false)
			return  $response->withStatus(404);

		$this->ci->view["menu"]->activateHost($host["host"]);
		$this->ci->view["menu"]->breadcrumb = array(
			array("name" => "host", "icon" => "fa fa-server", "url" => $this->ci->router->pathFor('hosts')), 
			array("name" => $host["host"], "url" => $this->ci->router->pathFor('host', array('id' => $id))), 
			array("name" => "services", "icon" => "fa fa-building-o", "url" => $this->ci->router->pathFor('services', array('id' => $id))));
		return $this->ci->view->render($response, 'hostServices.twig', [ 
			'h'		=> $host,
			'services'	=> $this->getHostServices($id)
				]);
	}

	public function hostService (Request $request, Response $response) {
		$hid = $request->getAttribute('hid');
		$sid = $request->getAttribute('sid');
		$this->ci->logger->addInfo("HostServices $hid - $sid");
		$host = $this->getHost($hid);
		$serv = $this->getService($sid);
		if ($host == false)
			return  $response->withStatus(404);

		$this->ci->view["menu"]->activateHost($host["host"]);
		$this->ci->view["menu"]->breadcrumb = array(
			array("name" => "host", "icon" => "fa fa-server", "url" => $this->ci->router->pathFor('hosts')), 
			array("name" => $host["host"], "url" => $this->ci->router->pathFor('host', array('id' => $hid))), 
			array("name" => "services", "icon" => "fa fa-building-o", "url" => $this->ci->router->pathFor('services', array('id' => $hid))),
			array("name" => $serv["name"], "url" => $this->ci->router->pathFor('service', array('hid' => $hid, 'sid' => $sid))));
		return $this->ci->view->render($response, 'hostService.twig', [ 
			'h'		=> $host,
			'service'	=> $serv
				]);
	}
}

?>
