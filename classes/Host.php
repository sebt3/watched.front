<?php
use Interop\Container\ContainerInterface;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

class Host {

	protected $ci;

	public function __construct(ContainerInterface $ci) { 
		$this->ci = $ci;
	}

	public function getList() {
		$results = [];
		$stmt = $this->ci->db->query("select a.id, a.name as host, ifnull(m.cnt, 0) as monitor_items, ifnull(e.cnt, 0) as events, ifnull(r.cnt, 0) as ressources from hosts a left join (select host_id, count(*) as cnt from monitoring_items group by host_id) m on a.id=m.host_id left join (select host_id, count(*) as cnt from res_events where end_time is null group by host_id) e on a.id=e.host_id left join (select host_id, count(*) as cnt from host_ressources group by host_id) r on a.id=r.host_id order by events desc");
		while($row = $stmt->fetch())
			$results[] = $row;
		return $results;
	}

	public function getHost($id) {
		$stmt = $this->ci->db->prepare("SELECT a.id, a.name as host from hosts a where a.id = :id");
		$stmt->bindParam(':id', $id, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetch();
	}

	public function getEventColor($name) {
		switch($name) {
		case "Ok":
			return "#00a65a";
		case "Critical":
			return "#dd4b39";
		case "Error":
			return "#ff851b";
		case "Warning":
			return "#f39c12";
		case "Notice":
			return "#0073b7";
		default:
			return "#3c8dbc";
		}
	}
	public function getEventTextColor($name) {
		switch($name) {
		case "Ok":
			return "text-green";
		case "Critical":
			return "text-red";
		case "Error":
			return "text-orange";
		case "Warning":
			return "text-yellow";
		case "Notice":
			return "text-blue";
		default:
			return "text-light-blue";
		}
	}

	public function getMonitoringStatus($id) {
		$ret = [];
		$s1 = $this->ci->db->prepare("select 'Ok' as name, 0 as id, t.cnt - m.cnt as cnt from (select count(*) as cnt from monitoring_items where host_id=:id) t, (select count(*) as cnt from res_events e where e.host_id=:id and e.end_time is null) m union all select et.name, et.id, count(e.id) as cnt from event_types et left join (select * from  res_events s where s.host_id=:id and s.end_time is null) e on et.id = e.event_type group by et.name order by id");
		$s1->bindParam(':id', $id, PDO::PARAM_INT);
		$s1->execute();
		while($r1 = $s1->fetch()) {
			$r1["color"] = $this->getEventColor($r1["name"]);
			$r1["text"]  = $this->getEventTextColor($r1["name"]);
			$ret[] = $r1;
		}
		return $ret;
	}

	public function getActivesEvents($id) {
		$ret = [];
		$s2 = $this->ci->db->prepare("select e.id, r.name, et.name as type, e.property, e.current_value, e.oper, e.value, e.start_time from res_events e, ressources r, event_types et where e.host_id=:id and e.res_id=r.id and et.id=e.event_type and e.end_time is null");
		$s2->bindParam(':id', $id, PDO::PARAM_INT);
		$s2->execute();
		while($r2 = $s2->fetch()) {
			$r2["color"] = $this->getEventTextColor($r2["name"]);
			$r2["current_value"] = round($r2["current_value"]);
			$r2["value"] = round($r2["value"]);
			$r2["decode"] = urldecode($r2["name"]);
			$r2["encode"] = urldecode($r2["oper"]);
			$ret[] = $r2;
		}
		return $ret;
	}

	public function getMonitoringItems($id) {
		$ret = [];
		$s3 = $this->ci->db->prepare("select et.name, et.id, ifnull(e.cnt,0) as cnt from event_types et left join (select event_type, count(*) as cnt from monitoring_items where host_id=:id group by event_type) e on et.id = e.event_type group by et.name order by id");
		$s3->bindParam(':id', $id, PDO::PARAM_INT);
		$s3->execute();
		while($r3 = $s3->fetch()) {
			$r3["color"] = $this->getEventColor($r3["name"]);
			$r3["text"]  = $this->getEventTextColor($r3["name"]);
			$ret[] = $r3;
		}
		return $ret;
	}

	public function getMonitoringHistory($id) {
		$ret = [];
		$s5 = $this->ci->db->prepare("select r.name as res_name, e.id, e.start_time, e.end_time, e.property, e.current_value, e.oper, e.value, et.name as event_name from res_events e, event_types et, ressources r where e.res_id=r.id and e.end_time is not null and e.event_type=et.id and host_id=:aid order by start_time desc limit 6");
		$s5->bindParam(':aid', $id, PDO::PARAM_INT);
		$s5->execute();
		while($r5 = $s5->fetch()) {
			$r5["color"] = $this->getEventTextColor($r5["event_name"]);
			$r5["value"] = round($r5["value"]);
			$r5["current_value"] = round($r5["current_value"]);
			$r5["encode"] = urldecode($r5["oper"]);
			$date = new DateTime();
			$date->setTimestamp(round($r5["start_time"]/1000));
			$r5["start_time"] = $date->format('Y-m-d H:i:s');
			if ($r5["end_time"] != null) {
				$date->setTimestamp(round($r5["end_time"]/1000));
				$r5["end_time"] = $date->format('Y-m-d H:i:s');
			}
			$r5["decode"] = urldecode($r5["res_name"]);
			$ret[] = $r5;
		}
		return $ret;
	}

	public function getStorageStatus($id) {
		$ret = [];
		$s6 = $this->ci->db->prepare("select ar.host_id, ar.res_id, r.name as res_name, d.free, d.ipctfree, d.pctfree, 100-d.pctfree pctused, d.size from host_ressources ar, ressources r, disk_usage d, (select host_id, res_id, max(timestamp) ts from disk_usage group by host_id, res_id) t where ar.res_id=r.id and r.type='disk_usage' and ar.host_id=d.host_id and ar.res_id=d.res_id and d.host_id=t.host_id and d.res_id=t.res_id and d.timestamp=t.ts and ar.host_id=:id and d.pctfree < 90 order by pctused desc");
		$s6->bindParam(':id', $id, PDO::PARAM_INT);
		$s6->execute();
		while($r6 = $s6->fetch()) {
			$r6["decode"] = urldecode(substr($r6["res_name"], 10));
			$ret[] = $r6;
		}
		return $ret;
	}

	public function getCPUusage($id) {
		$ret = [];
		$s7 = $this->ci->db->prepare("select u.host_id, u.res_id, r.name as res_name, u.iowait+u.irq+u.nice+u.softirq+u.system+u.user as pctused from host_ressources ar, ressources r, cpu_usage u, (select host_id, res_id, max(timestamp) as ts from cpu_usage group by host_id, res_id) cu where u.host_id=cu.host_id and u.res_id = cu.res_id and u.timestamp = cu.ts and ar.res_id=r.id and r.type='cpu_usage' and ar.host_id=u.host_id and ar.res_id=u.res_id and ar.host_id=:id order by pctused desc");
		$s7->bindParam(':id', $id, PDO::PARAM_INT);
		$s7->execute();
		while($r7 = $s7->fetch()) {
			$r7["decode"] = substr($r7["res_name"], 7);
			$ret[] = $r7;
		}
		return $ret;
	}

	public function getMemoryUsage($id) {
		$ret = [];
		$s8 = $this->ci->db->prepare("select m.host_id, m.res_id, 'memory' as name, (m.apps+m.buffer+m.slab+m.cached)/1024 as used, m.free/1024 as free, m.pct as pctfree, 100-m.pct as pctused from memory_usage m, (select host_id, res_id, max(timestamp) ts from memory_usage group by host_id, res_id) t where m.host_id=t.host_id and m.res_id=t.res_id and m.timestamp=t.ts and m.host_id=:id union all select s.host_id, s.res_id, 'swap' as name, s.used/1024 as used, s.free/1024 as free, s.pct as pctfree, 100-s.pct as pctused from swap_usage s, (select host_id, res_id, max(timestamp) ts from swap_usage group by host_id, res_id) u where s.host_id=u.host_id and s.res_id=u.res_id and s.timestamp=u.ts and s.host_id=:id");
		$s8->bindParam(':id', $id, PDO::PARAM_INT);
		$s8->execute();
		while($r8 = $s8->fetch()) {
			$ret[] = $r8;
		}
		return $ret;
	}

	public function getStats($id) {
		$ret = [];
		$s9 = $this->ci->db->prepare("select r.name, ar.host_id, ar.res_id from ressources r, host_ressources ar where r.type like '%stat%' and ar.res_id=r.id and ar.host_id=:id");
		$s9->bindParam(':id', $id, PDO::PARAM_INT);
		$s9->execute();
		while($r9 = $s9->fetch()) {
			$ret[] = $r9;
		}
		return $ret;
	}

	public function getOtherRessources($id) {
		$ret = [];
		$s10 = $this->ci->db->prepare("select r.name, ar.host_id, ar.res_id from ressources r, host_ressources ar where r.type not in ('disk_usage', 'cpu_usage', 'memory_usage', 'swap_usage') and r.type not like '%stat%' and ar.res_id=r.id  and ar.host_id=:id");
		$s10->bindParam(':id', $id, PDO::PARAM_INT);
		$s10->execute();
		while($r10 = $s10->fetch()) {
			$ret[] = $r10;
		}
		return $ret;
	}

	public function getAllRessources($id) {
		$stmt = $this->ci->db->prepare("select r.*, ifnull(m.cnt,0) as monitors, ifnull(e.cnt,0) as events from (select * from host_ressources ar, ressources re where ar.res_id=re.id) r left join (select host_id, res_id, count(*) as cnt from monitoring_items group by host_id, res_id) m on m.host_id=r.host_id and m.res_id=r.res_id left join (select host_id, res_id, count(*) as cnt from res_events where end_time is null group by host_id, res_id) e on e.host_id=r.host_id and e.res_id=r.res_id where r.host_id=:id order by events desc, monitors desc, r.name");
		$stmt->bindParam(':id', $id, PDO::PARAM_INT);
		$stmt->execute();
		$results = [];
		while($row = $stmt->fetch()) {
			$row["name"]=urldecode($row["name"]);
			$results[] = $row;
		}
		return $results;
	}

/////////////////////////////////////////////////////////////////////////////////////////////
// Pages
	public function Hosts (Request $request, Response $response) {
		$this->ci->logger->addInfo("Host list");
		$this->ci->view["menu"]->breadcrumb = array( array("name" => "Hosts", "icon" => "fa fa-server", "url" => $this->ci->router->pathFor('hosts')) );
		return $this->ci->view->render($response, 'hosts.twig', [ 'hosts' => $this->getList() ]);
	}

	public function Host (Request $request, Response $response) {
		$id = $request->getAttribute('id');
		$this->ci->logger->addInfo("Host $id");
		$host = $this->getHost($id);
		if ($host == false)
			return $response->withStatus(404);

		$this->ci->view["menu"]->breadcrumb = array(
			array("name" => "hosts", "icon" => "fa fa-server", "url" => $this->ci->router->pathFor('hosts') ), 
			array("name" => $host["host"], "url" => $this->ci->router->pathFor('host', array('id' => $id)) ));
		return $this->ci->view->render($response, 'host.twig', [ 
			'host'		 => $host, 
			'monitorStatus'	 => $this->getMonitoringStatus($id),
			'activeEvent'	 => $this->getActivesEvents($id),
			'monitorItems'	 => $this->getMonitoringItems($id),
			'monitorHistory' => $this->getMonitoringHistory($id),
			'storageStatus'  => $this->getStorageStatus($id),
			'cpuUsage'	 => $this->getCPUusage($id),
			'memoryUsage'	 => $this->getMemoryUsage($id),
			'stats'		 => $this->getStats($id),
			'ressources' 	 => $this->getOtherRessources($id)
			]);
	}

	public function ressources(Request $request, Response $response) {
		$id = $request->getAttribute('id');
		$this->ci->logger->addInfo("Host $id");
		$host = $this->getHost($id);
		if ($host == false)
			return  $response->withStatus(404);
		$this->ci->view["menu"]->breadcrumb = array(
			array("name" => "hosts", "icon" => "fa fa-server", "url" => $this->ci->router->pathFor('hosts') ), 
			array("name" => $host["host"], "url" => $this->ci->router->pathFor('host', array('id' => $id)) ), 
			array("name" => "resources", "icon" => "fa fa-area-chart", "url" => $this->ci->router->pathFor('ressources', array('id' => $id))) );
		return $this->ci->view->render($response, 'ressources.twig', [ 'host' => $host, 'ressources' => $this->getAllRessources($id) ]);
	}
}

?>