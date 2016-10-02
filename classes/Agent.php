<?php
use Interop\Container\ContainerInterface;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

class Agent {

	protected $ci;

	public function __construct(ContainerInterface $ci) { 
		$this->ci = $ci;
	}

	public function agents (Request $request, Response $response) {
		$this->ci->logger->addInfo("Agent list");
		$sql = "select a.id, a.host, ifnull(m.cnt, 0) as monitor_items, ifnull(e.cnt, 0) as events, ifnull(r.cnt, 0) as ressources from agents a left join (select agent_id, count(*) as cnt from monitoring_items group by agent_id) m on a.id=m.agent_id left join (select agent_id, count(*) as cnt from events where end_time is null group by agent_id) e on a.id=e.agent_id left join (select agent_id, count(*) as cnt from agent_ressources group by agent_id) r on a.id=r.agent_id order by events desc";
		$stmt = $this->ci->db->query($sql);
		$results = [];
		while($row = $stmt->fetch())
			$results[] = $row;
		$this->ci->view["menu"]->breadcrumb = array( array("name" => "agents", "icon" => "fa fa-server", "url" => $this->ci->router->pathFor('agents')) );
		return $this->ci->view->render($response, 'agents.twig', [ 'agents' => $results ]);
	}

	public function agent (Request $request, Response $response) {
		$id = $request->getAttribute('id');
		$this->ci->logger->addInfo("Agent $id");
		$stmt = $this->ci->db->prepare("SELECT a.id, a.host, a.port, a.pool_freq, a.central_id from agents a where a.id = :id");
		$stmt->bindParam(':id', $id, PDO::PARAM_INT);
		$stmt->execute();
		$agent = $stmt->fetch();
		if ($agent == false) {
			return $response->withStatus(404);
		}

		// monitoring status graph
		$s1 = $this->ci->db->prepare("select 'Ok' as name, 0 as id, t.cnt - m.cnt as cnt from (select count(*) as cnt from monitoring_items where agent_id=:id) t, (select count(*) as cnt from events e where e.agent_id=:id and e.end_time is null) m union all select et.name, et.id, count(e.id) as cnt from event_types et left join (select * from  events s where s.agent_id=:id and s.end_time is null) e on et.id = e.event_type group by et.name order by id");
		$s1->bindParam(':id', $id, PDO::PARAM_INT);
		$s1->execute();
		$ms = [];
		while($r1 = $s1->fetch()) {
			switch($r1["name"]) {
				case "Ok":
					$r1["color"] = "#00a65a";
					$r1["text"]  = "text-green";
				break;
				case "Critical":
					$r1["color"] = "#dd4b39";
					$r1["text"]  = "text-red";
				break;
				case "Error":
					$r1["color"] = "#ff851b";
					$r1["text"]  = "text-orange";
				break;
				case "Warning":
					$r1["color"] = "#f39c12";
					$r1["text"]  = "text-yellow";
				break;
				case "Notice":
					$r1["color"] = "#0073b7";
					$r1["text"]  = "text-blue";
				break;
				default:
					$r1["color"] = "#3c8dbc";
					$r1["text"]  = "text-light-blue";
			}
			$ms[] = $r1;
		}

		// actives events
		$s2 = $this->ci->db->prepare("select e.id, r.name, et.name as type, e.property, e.current_value, e.oper, e.value, e.start_time from events e, ressources r, event_types et where e.agent_id=:id and e.res_id=r.id and et.id=e.event_type and e.end_time is null");
		$s2->bindParam(':id', $id, PDO::PARAM_INT);
		$s2->execute();
		$ae = [];
		while($r2 = $s2->fetch()) {
			switch($r2["type"]) {
				case "Ok":
					$r2["color"]  = "text-green";
				break;
				case "Critical":
					$r2["color"] = "text-red";
				break;
				case "Error":
					$r2["color"] = "text-orange";
				break;
				case "Warning":
					$r2["color"] = "text-yellow";
				break;
				case "Notice":
					$r2["color"] = "text-blue";
				break;
				default:
					$r2["color"] = "text-light-blue";
			}
			$r2["current_value"] = round($r2["current_value"]);
			$r2["value"] = round($r2["value"]);
			$r2["decode"] = urldecode($r2["name"]);
			$r2["encode"] = urldecode($r2["oper"]);
			$ae[] = $r2;
		}

		// Monitoring items
		$s3 = $this->ci->db->prepare("select et.name, et.id, ifnull(e.cnt,0) as cnt from event_types et left join (select event_type, count(*) as cnt from monitoring_items where agent_id=:id group by event_type) e on et.id = e.event_type group by et.name order by id");
		$s3->bindParam(':id', $id, PDO::PARAM_INT);
		$s3->execute();
		$mi = [];
		while($r3 = $s3->fetch()) {
			switch($r3["name"]) {
				case "Ok":
					$r3["color"] = "#00a65a";
					$r3["text"]  = "text-green";
				break;
				case "Critical":
					$r3["color"] = "#dd4b39";
					$r3["text"]  = "text-red";
				break;
				case "Error":
					$r3["color"] = "#ff851b";
					$r3["text"]  = "text-orange";
				break;
				case "Warning":
					$r3["color"] = "#f39c12";
					$r3["text"]  = "text-yellow";
				break;
				case "Notice":
					$r3["color"] = "#0073b7";
					$r3["text"]  = "text-blue";
				break;
				default:
					$r3["color"] = "#3c8dbc";
					$r3["text"]  = "text-light-blue";
			}
			$mi[] = $r3;
		}

		// Monitoring history
		$s5 = $this->ci->db->prepare("select r.name as res_name, e.id, e.start_time, e.end_time, e.property, e.current_value, e.oper, e.value, et.name as event_name from events e, event_types et, ressources r where e.res_id=r.id and e.end_time is not null and e.event_type=et.id and agent_id=:aid order by start_time");
		$s5->bindParam(':aid', $id, PDO::PARAM_INT);
		$s5->execute();
		$mh = [];
		while($r5 = $s5->fetch()) {
			switch($r5["event_name"]) {
				case "Critical":
					$r5["color"] = "text-red";
				break;
				case "Error":
					$r5["color"] = "text-orange";
				break;
				case "Warning":
					$r5["color"] = "text-yellow";
				break;
				case "Notice":
					$r5["color"] = "text-blue";
				break;
				default:
					$r5["color"] = "text-light-blue";
			}
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


			$mh[] = $r5;
		}

		// Storage status
		$s6 = $this->ci->db->prepare("select ar.agent_id, ar.res_id, r.name as res_name, d.free, d.ipctfree, d.pctfree, 100-d.pctfree pctused, d.size from agent_ressources ar, ressources r, disk_usage d, (select agent_id, res_id, max(timestamp) ts from disk_usage group by agent_id, res_id) t where ar.res_id=r.id and r.type='disk_usage' and ar.agent_id=d.agent_id and ar.res_id=d.res_id and d.agent_id=t.agent_id and d.res_id=t.res_id and d.timestamp=t.ts and ar.agent_id=:id and d.pctfree < 90 order by pctused desc");
		$s6->bindParam(':id', $id, PDO::PARAM_INT);
		$s6->execute();
		$ss = [];
		while($r6 = $s6->fetch()) {
			$r6["decode"] = urldecode(substr($r6["res_name"], 10));
			$ss[] = $r6;
		}

		// CPU usage
		$s7 = $this->ci->db->prepare("select u.agent_id, u.res_id, r.name as res_name, u.iowait+u.irq+u.nice+u.softirq+u.system+u.user as pctused from agent_ressources ar, ressources r, cpu_usage u, (select agent_id, res_id, max(timestamp) as ts from cpu_usage group by agent_id, res_id) cu where u.agent_id=cu.agent_id and u.res_id = cu.res_id and u.timestamp = cu.ts and ar.res_id=r.id and r.type='cpu_usage' and ar.agent_id=u.agent_id and ar.res_id=u.res_id and ar.agent_id=:id order by pctused desc");
		$s7->bindParam(':id', $id, PDO::PARAM_INT);
		$s7->execute();
		$cu = [];
		while($r7 = $s7->fetch()) {
			$r7["decode"] = substr($r7["res_name"], 7);
			$cu[] = $r7;
		}

		// memory usage
		$s8 = $this->ci->db->prepare("select m.agent_id, m.res_id, 'memory' as name, (m.apps+m.buffer+m.slab+m.cached)/1024 as used, m.free/1024 as free, m.pct as pctfree, 100-m.pct as pctused from memory_usage m, (select agent_id, res_id, max(timestamp) ts from memory_usage group by agent_id, res_id) t where m.agent_id=t.agent_id and m.res_id=t.res_id and m.timestamp=t.ts and m.agent_id=:id union all select s.agent_id, s.res_id, 'swap' as name, s.used/1024 as used, s.free/1024 as free, s.pct as pctfree, 100-s.pct as pctused from swap_usage s, (select agent_id, res_id, max(timestamp) ts from swap_usage group by agent_id, res_id) u where s.agent_id=u.agent_id and s.res_id=u.res_id and s.timestamp=u.ts and s.agent_id=:id");
		$s8->bindParam(':id', $id, PDO::PARAM_INT);
		$s8->execute();
		$mu = [];
		while($r8 = $s8->fetch()) {
			$mu[] = $r8;
		}

		// Stats
		$s9 = $this->ci->db->prepare("select r.name, ar.agent_id, ar.res_id from ressources r, agent_ressources ar where r.type like '%stat%' and ar.res_id=r.id and ar.agent_id=:id");
		$s9->bindParam(':id', $id, PDO::PARAM_INT);
		$s9->execute();
		$st = [];
		while($r9 = $s9->fetch()) {
			$st[] = $r9;
		}

		// ressources
		$s10 = $this->ci->db->prepare("select r.name, ar.agent_id, ar.res_id from ressources r, agent_ressources ar where r.type not in ('disk_usage', 'cpu_usage', 'memory_usage', 'swap_usage') and r.type not like '%stat%' and ar.res_id=r.id  and ar.agent_id=:id");
		$s10->bindParam(':id', $id, PDO::PARAM_INT);
		$s10->execute();
		$res = [];
		while($r10 = $s10->fetch()) {
			$res[] = $r10;
		}

		$this->ci->view["menu"]->breadcrumb = array(
			array("name" => "agents", "icon" => "fa fa-server", "url" => $this->ci->router->pathFor('agents') ), 
			array("name" => $agent["host"], "url" => $this->ci->router->pathFor('agent', array('id' => $id)) ));
		return $this->ci->view->render($response, 'agent.twig', [ 'agent' => $agent, 'monitorStatus' => $ms, 'activeEvent' => $ae, 'monitorItems' => $mi, 'monitorHistory' => $mh, 'storageStatus' => $ss, 'cpuUsage' => $cu, 'memoryUsage' => $mu, 'stats' => $st, 'ressources' => $res ]);
	}
	
	public function ressources(Request $request, Response $response) {
		$id = $request->getAttribute('id');
		$this->ci->logger->addInfo("Agent $id");
		$stmt = $this->ci->db->prepare("SELECT a.id, a.host from agents a where a.id = :id");
		$stmt->bindParam(':id', $id, PDO::PARAM_INT);
		$stmt->execute();
		$agent = $stmt->fetch();
		if ($agent == false) {
			return  $response->withStatus(404);
		}
		$stmt = $this->ci->db->prepare("select r.*, ifnull(m.cnt,0) as monitors, ifnull(e.cnt,0) as events from (select * from agent_ressources ar, ressources re where ar.res_id=re.id) r left join (select agent_id, res_id, count(*) as cnt from monitoring_items group by agent_id, res_id) m on m.agent_id=r.agent_id and m.res_id=r.res_id left join (select agent_id, res_id, count(*) as cnt from events where end_time is null group by agent_id, res_id) e on e.agent_id=r.agent_id and e.res_id=r.res_id where r.agent_id=:id order by events desc, monitors desc, r.name");
		$stmt->bindParam(':id', $id, PDO::PARAM_INT);
		$stmt->execute();
		$results = [];
		while($row = $stmt->fetch()) {
			$row["name"]=urldecode($row["name"]);
			$results[] = $row;
		}
		$this->ci->view["menu"]->breadcrumb = array(
			array("name" => "agents", "icon" => "fa fa-server", "url" => $this->ci->router->pathFor('agents') ), 
			array("name" => $agent["host"], "url" => $this->ci->router->pathFor('agent', array('id' => $id)) ), 
			array("name" => "resources", "icon" => "fa fa-area-chart", "url" => $this->ci->router->pathFor('ressources', array('id' => $id))) );
		return $this->ci->view->render($response, 'ressources.twig', [ 'agent' => $agent, 'ressources' => $results ]);
	}
	
	public function ressource (Request $request, Response $response) {
		$aid = $request->getAttribute('aid');
		$rid = $request->getAttribute('rid');
		$this->ci->logger->addInfo("Ressource $aid - $rid");
		$stmt = $this->ci->db->prepare("SELECT a.id, a.host, a.port, a.pool_freq, a.central_id from agents a where a.id = :id");
		$stmt->bindParam(':id', $aid, PDO::PARAM_INT);
		$stmt->execute();
		$agent = $stmt->fetch();
		if ($agent == false) {
			return  $response->withStatus(404);
		}
		$stmt = $this->ci->db->prepare("SELECT id, name, type from ressources where id = :id");
		$stmt->bindParam(':id', $rid, PDO::PARAM_INT);
		$stmt->execute();
		$res = $stmt->fetch();
		if ($res == false) {
			return  $response->withStatus(404);
		}

		// Monitoring stuff
		$s0 = $this->ci->db->prepare("select count(*) as cnt from monitoring_items where agent_id=:aid and res_id=:rid");
		$s0->bindParam(':aid', $aid, PDO::PARAM_INT);
		$s0->bindParam(':rid', $rid, PDO::PARAM_INT);
		$s0->execute();
		$c = $s0->fetch();
		if($c["cnt"] > 0) {
			// monitoring status graph
			$s1 = $this->ci->db->prepare("select 'Ok' as name, 0 as id, t.cnt - m.cnt as cnt from (select count(*) as cnt from monitoring_items where agent_id=:aid and res_id=:rid) t, (select count(*) as cnt from events e where e.agent_id=:aid and e.res_id=:rid and e.end_time is null) m union all select et.name, et.id, count(e.id) as cnt from event_types et left join (select * from  events s where s.agent_id=:aid and s.res_id=:rid and s.end_time is null) e on et.id = e.event_type group by et.name order by id");
			$s1->bindParam(':aid', $aid, PDO::PARAM_INT);
			$s1->bindParam(':rid', $rid, PDO::PARAM_INT);
			$s1->execute();
			$ms = [];
			while($r1 = $s1->fetch()) {
				switch($r1["name"]) {
					case "Ok":
						$r1["color"] = "#00a65a";
						$r1["text"]  = "text-green";
					break;
					case "Critical":
						$r1["color"] = "#dd4b39";
						$r1["text"]  = "text-red";
					break;
					case "Error":
						$r1["color"] = "#ff851b";
						$r1["text"]  = "text-orange";
					break;
					case "Warning":
						$r1["color"] = "#f39c12";
						$r1["text"]  = "text-yellow";
					break;
					case "Notice":
						$r1["color"] = "#0073b7";
						$r1["text"]  = "text-blue";
					break;
					default:
						$r1["color"] = "#3c8dbc";
						$r1["text"]  = "text-light-blue";
				}
				$ms[] = $r1;
			}
			
			// actives events
			$s2 = $this->ci->db->prepare("select e.id, r.name, et.name as type, e.property, e.current_value, e.oper, e.value, e.start_time from events e, ressources r, event_types et where e.agent_id=:aid and e.res_id=:rid and e.res_id=r.id and et.id=e.event_type and e.end_time is null");
			$s2->bindParam(':aid', $aid, PDO::PARAM_INT);
			$s2->bindParam(':rid', $rid, PDO::PARAM_INT);
			$s2->execute();
			$ae = [];
			while($r2 = $s2->fetch()) {
				switch($r2["type"]) {
					case "Ok":
						$r2["color"]  = "text-green";
					break;
					case "Critical":
						$r2["color"] = "text-red";
					break;
					case "Error":
						$r2["color"] = "text-orange";
					break;
					case "Warning":
						$r2["color"] = "text-yellow";
					break;
					case "Notice":
						$r2["color"] = "text-blue";
					break;
					default:
						$r2["color"] = "text-light-blue";
				}
				$r2["current_value"] = round($r2["current_value"]);
				$r2["value"] = round($r2["value"]);
				$r2["decode"] = urldecode($r2["name"]);
				$r2["encode"] = urldecode($r2["oper"]);
				$ae[] = $r2;
			}

			// Monitoring items
			$s3 = $this->ci->db->prepare("select et.name, et.id, ifnull(e.cnt,0) as cnt from event_types et left join (select event_type, count(*) as cnt from monitoring_items where agent_id=:aid and res_id=:rid group by event_type) e on et.id = e.event_type group by et.name order by id");
			$s3->bindParam(':aid', $aid, PDO::PARAM_INT);
			$s3->bindParam(':rid', $rid, PDO::PARAM_INT);
			$s3->execute();
			$mi = [];
			while($r3 = $s3->fetch()) {
				switch($r3["name"]) {
					case "Ok":
						$r3["color"] = "#00a65a";
						$r3["text"]  = "text-green";
					break;
					case "Critical":
						$r3["color"] = "#dd4b39";
						$r3["text"]  = "text-red";
					break;
					case "Error":
						$r3["color"] = "#ff851b";
						$r3["text"]  = "text-orange";
					break;
					case "Warning":
						$r3["color"] = "#f39c12";
						$r3["text"]  = "text-yellow";
					break;
					case "Notice":
						$r3["color"] = "#0073b7";
						$r3["text"]  = "text-blue";
					break;
					default:
						$r3["color"] = "#3c8dbc";
						$r3["text"]  = "text-light-blue";
				}
				$mi[] = $r3;
			}
		
			// Monitoring list
			$s4 = $this->ci->db->prepare("select res_name, res_type, event_name, property, oper, value from monitoring_items where agent_id=:aid and res_id=:rid order by property, event_type");
			$s4->bindParam(':aid', $aid, PDO::PARAM_INT);
			$s4->bindParam(':rid', $rid, PDO::PARAM_INT);
			$s4->execute();
			$ml = [];
			while($r4 = $s4->fetch()) {
				switch($r4["event_name"]) {
					case "Critical":
						$r4["color"] = "text-red";
					break;
					case "Error":
						$r4["color"] = "text-orange";
					break;
					case "Warning":
						$r4["color"] = "text-yellow";
					break;
					case "Notice":
						$r4["color"] = "text-blue";
					break;
					default:
						$r4["color"] = "text-light-blue";
				}
				$r4["value"] = round($r4["value"]);
				$r4["decode"] = urldecode($r4["res_name"]);
				$r4["encode"] = urldecode($r4["oper"]);
				$ml[] = $r4;
			}

			// Monitoring history
			$s5 = $this->ci->db->prepare("select e.id, e.start_time, e.end_time, e.property, e.current_value, e.oper, e.value, et.name as event_name from events e, event_types et where e.end_time is not null and e.event_type=et.id and agent_id=:aid and res_id=:rid order by start_time");
			$s5->bindParam(':aid', $aid, PDO::PARAM_INT);
			$s5->bindParam(':rid', $rid, PDO::PARAM_INT);
			$s5->execute();
			$mh = [];
			while($r5 = $s5->fetch()) {
				switch($r5["event_name"]) {
					case "Critical":
						$r5["color"] = "text-red";
					break;
					case "Error":
						$r5["color"] = "text-orange";
					break;
					case "Warning":
						$r5["color"] = "text-yellow";
					break;
					case "Notice":
						$r5["color"] = "text-blue";
					break;
					default:
						$r5["color"] = "text-light-blue";
				}
				$r5["value"] = round($r4["value"]);
				$r5["current_value"] = round($r5["current_value"]);
				$r5["encode"] = urldecode($r5["oper"]);
				$date = new DateTime();
				$date->setTimestamp(round($r5["start_time"]/1000));
				$r5["start_time"] = $date->format('Y-m-d H:i:s');
				if ($r5["end_time"] != null) {
					$date->setTimestamp(round($r5["end_time"]/1000));
					$r5["end_time"] = $date->format('Y-m-d H:i:s');
				}

				$mh[] = $r5;
			}

		}

		$this->ci->view["menu"]->breadcrumb = array(
			array("name" => "agents", "icon" => "fa fa-server", "url" => $this->ci->router->pathFor('agents')), 
			array("name" => $agent["host"], "url" => $this->ci->router->pathFor('agent', array('id' => $aid))), 
			array("name" => "resources", "icon" => "fa fa-area-chart", "url" => $this->ci->router->pathFor('ressources', array('id' => $aid))),
			array("name" => urldecode($res["name"]), "url" => $this->ci->router->pathFor('ressource', array('aid' => $aid, 'rid' => $rid))));
		return $this->ci->view->render($response, 'ressource.twig', [ 'a' => $agent, 'r' => array("id" => $res["id"], "type" => $res["type"], "name" => urldecode($res["name"])), 'monitorStatus' => $ms, 'activeEvent' => $ae, 'monitorItems' => $mi, 'monitorList' => $ml, 'monitorHistory' => $mh ]);
	}
}

?>
