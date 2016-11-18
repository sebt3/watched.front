<?php
use Interop\Container\ContainerInterface;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

class HostRessource extends CorePage {
	public function __construct(ContainerInterface $ci) { 
		parent::__construct($ci);
	}

	public function haveMonitoring($aid, $rid) {
		$s0 = $this->ci->db->prepare("select count(*) as cnt from monitoring_items where host_id=:aid and res_id=:rid");
		$s0->bindParam(':aid', $aid, PDO::PARAM_INT);
		$s0->bindParam(':rid', $rid, PDO::PARAM_INT);
		$s0->execute();
		$c = $s0->fetch();
		return ($c["cnt"] > 0);
	}

	public function getMonitoringStatus($aid, $rid) {
		$ret = [];
		$s1  = $this->ci->db->prepare("select 'Ok' as name, 0 as id, t.cnt - m.cnt as cnt from (select count(*) as cnt from monitoring_items where host_id=:aid and res_id=:rid) t, (select count(*) as cnt from res_events e where e.host_id=:aid and e.res_id=:rid and e.end_time is null) m union all select et.name, et.id, count(e.id) as cnt from event_types et left join (select * from  res_events s where s.host_id=:aid and s.res_id=:rid and s.end_time is null) e on et.id = e.event_type group by et.name order by id");
		$s1->bindParam(':aid', $aid, PDO::PARAM_INT);
		$s1->bindParam(':rid', $rid, PDO::PARAM_INT);
		$s1->execute();
		while($r1 = $s1->fetch()) {
			$r1["color"] = $this->getEventColor($r1["name"]);
			$r1["text"]  = $this->getEventTextColor($r1["name"]);
			$ret[] = $r1;
		}
		return $ret;
	}

	public function getActivesEvents($aid, $rid) {
		$ret = [];
		$s2  = $this->ci->db->prepare("select e.id, r.name, et.name as type, e.property, e.current_value, e.oper, e.value, e.start_time from res_events e, ressources r, event_types et where e.host_id=:aid and e.res_id=:rid and e.res_id=r.id and et.id=e.event_type and e.end_time is null");
		$s2->bindParam(':aid', $aid, PDO::PARAM_INT);
		$s2->bindParam(':rid', $rid, PDO::PARAM_INT);
		$s2->execute();
		while($r2 = $s2->fetch()) {
			$r2["color"]  = $this->getEventTextColor($r2["type"]);
			$r2["current_value"] = round($r2["current_value"]);
			$r2["value"] = round($r2["value"]);
			$r2["decode"] = urldecode($r2["name"]);
			$r2["encode"] = urldecode($r2["oper"]);
			$ret[] = $r2;
		}
		return $ret;
	}

	public function getMonitoringItems($aid, $rid) {
		$ret = [];
		$s3  = $this->ci->db->prepare("select et.name, et.id, ifnull(e.cnt,0) as cnt from event_types et left join (select event_type, count(*) as cnt from monitoring_items where host_id=:aid and res_id=:rid group by event_type) e on et.id = e.event_type group by et.name order by id");
		$s3->bindParam(':aid', $aid, PDO::PARAM_INT);
		$s3->bindParam(':rid', $rid, PDO::PARAM_INT);
		$s3->execute();
		while($r3 = $s3->fetch()) {
			$r3["color"] = $this->getEventColor($r3["name"]);
			$r3["text"]  = $this->getEventTextColor($r3["name"]);
			$ret[] = $r3;
		}
		return $ret;
	}

	public function getMonitoringList($aid, $rid) {
		$ret = [];
		$s4  = $this->ci->db->prepare("select res_name, res_type, event_name, property, oper, value from monitoring_items where host_id=:aid and res_id=:rid order by property, event_type");
		$s4->bindParam(':aid', $aid, PDO::PARAM_INT);
		$s4->bindParam(':rid', $rid, PDO::PARAM_INT);
		$s4->execute();
		while($r4 = $s4->fetch()) {
			$r4["color"]  = $this->getEventTextColor($r4["event_name"]);
			$r4["value"] = round($r4["value"]);
			$r4["decode"] = urldecode($r4["res_name"]);
			$r4["encode"] = urldecode($r4["oper"]);
			$ret[] = $r4;
		}
		return $ret;
	}

	public function getMonitoringHistory($aid, $rid) {
		$ret = [];
		$s5  = $this->ci->db->prepare("select e.id, e.start_time, e.end_time, e.property, e.current_value, e.oper, e.value, et.name as event_name from res_events e, event_types et where e.end_time is not null and e.event_type=et.id and host_id=:aid and res_id=:rid order by start_time desc limit 8");
		$s5->bindParam(':aid', $aid, PDO::PARAM_INT);
		$s5->bindParam(':rid', $rid, PDO::PARAM_INT);
		$s5->execute();
		while($r5 = $s5->fetch()) {
			$r5["color"]  = $this->getEventTextColor($r5["event_name"]);
			$r5["value"] = round($r5["value"]);
			$r5["current_value"] = round($r5["current_value"]);
			$r5["encode"] = urldecode($r5["oper"]);
			$r5["start_time"] = $this->formatTimestamp($r5["start_time"]);
			if ($r5["end_time"] != null)
				$r5["end_time"] = $this->formatTimestamp($r5["end_time"]);
			$ret[] = $r5;
		}
		return $ret;
	}

/////////////////////////////////////////////////////////////////////////////////////////////
// Pages
	public function ressource (Request $request, Response $response) {
		$aid = $request->getAttribute('aid');
		$rid = $request->getAttribute('rid');
		$this->ci->logger->addInfo("Ressource $aid - $rid");
		$agent = $this->getHost($aid);
		$res = $this->getRessource($rid);
		if ($agent == false || $res == false)
			return  $response->withStatus(404);

		// Monitoring stuff

		$this->ci->view["menu"]->activateHost($agent["host"]);
		$this->ci->view["menu"]->breadcrumb = array(
			array("name" => "host", "icon" => "fa fa-server", "url" => $this->ci->router->pathFor('hosts')), 
			array("name" => $agent["host"], "url" => $this->ci->router->pathFor('host', array('id' => $aid))), 
			array("name" => "resources", "icon" => "fa fa-area-chart", "url" => $this->ci->router->pathFor('ressources', array('id' => $aid))),
			array("name" => urldecode($res["name"]), "url" => $this->ci->router->pathFor('ressource', array('aid' => $aid, 'rid' => $rid))));
		if($this->haveMonitoring($aid, $rid))
			return $this->ci->view->render($response, 'ressource.twig', [ 
				'a'		=> $agent,
				'r'		=> array("id" => $res["id"], "type" => $res["type"], "name" => urldecode($res["name"])),
				'monitorStatus' => $this->getMonitoringStatus($aid, $rid),
				'activeEvent'	=> $this->getActivesEvents($aid, $rid),
				'monitorItems'	=> $this->getMonitoringItems($aid, $rid),
				'monitorList'	=> $this->getMonitoringList($aid, $rid),
				'monitorHistory' => $this->getMonitoringHistory($aid, $rid)
				]);
		else
			return $this->ci->view->render($response, 'ressource.twig', [ 
				'a'		=> $agent,
				'r'		=> array("id" => $res["id"], "type" => $res["type"], "name" => urldecode($res["name"]))
				]);
	}
}

?>
