<?php
use Interop\Container\ContainerInterface;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

class Dashboard extends CorePage {
	public function __construct(ContainerInterface $ci) { 
		parent::__construct($ci);
	}

	public function getMonitoringStatus() {
		$ret = [];
		$s1 = $this->ci->db->prepare("select 'Ok' as name, 0 as id, t.cnt+po.cnt+so.cnt - m.cnt as cnt from (select count(*) as cnt from serviceProcess where (UNIX_TIMESTAMP()*1000-timestamp)/1000<60*15 and status like 'ok%') po, (select count(*) as cnt from serviceSockets where (UNIX_TIMESTAMP()*1000-timestamp)/1000<60*15 and  status like 'ok%') so, (select count(*) as cnt from monitoring_items) t, (select count(*) as cnt from res_events e where e.end_time is null) m union all select 'Failed' as name, 0 as id, po.cnt+so.cnt as cnt from (select count(*) as cnt from serviceProcess where (UNIX_TIMESTAMP()*1000-timestamp)/1000<60*15 and status not like 'ok%') po, (select count(*) as cnt from serviceSockets where (UNIX_TIMESTAMP()*1000-timestamp)/1000<60*15 and  status not like 'ok%') so union all select 'Missing' as name, 2 as id, po.cnt+so.cnt as cnt from (select count(*) as cnt from serviceProcess where (UNIX_TIMESTAMP()*1000-timestamp)/1000>60*15) po, (select count(*) as cnt from serviceSockets where (UNIX_TIMESTAMP()*1000-timestamp)/1000>60*15) so union all select et.name, et.id, count(e.id) as cnt from event_types et left join (select * from  res_events s where s.end_time is null) e on et.id = e.event_type group by et.name order by id");
		$s1->execute();
		while($r1 = $s1->fetch()) {
			$r1["color"] = $this->getEventColor($r1["name"]);
			$r1["text"]  = $this->getEventTextColor($r1["name"]);
			$ret[] = $r1;
		}
		return $ret;
	}

	public function getActivesEvents() {
		$ret = [];
		$s = $this->ci->db->prepare("select h.name as host_name, e.id, r.name, et.name as type, e.property, e.current_value, e.oper, e.value, e.start_time from hosts h, res_events e, ressources r, event_types et where h.id = e.host_id and e.res_id=r.id and et.id=e.event_type and e.end_time is null order by et.id");
		$s->execute();
		while($r = $s->fetch()) {
			$r["color"] = $this->getEventTextColor($r["type"]);
			$r["current_value"] = round($r["current_value"]);
			$r["value"] = round($r["value"]);
			$r["decode"] = urldecode($r["name"]);
			$r["encode"] = urldecode($r["oper"]);
			$r["start_time"] = $this->formatTimestamp($r["start_time"]);
			$ret[] = $r;
		}
		return $ret;
	}

	public function getHostDomains() {
		$ret = [];
		$s = $this->ci->db->prepare("select 0 as id, \"unset\" as name, count(*) as cnt from hosts where domain_id is null union all select id, name, ifnull(h.cnt, 0) as cnt from domains d left join (select domain_id, count(*) as cnt from hosts where domain_id is not null  group by domain_id) h on h.domain_id = d.id order by id");
		$s->execute();
		while($r = $s->fetch()) {
			$r["text"]  = $this->getDomainTextColor($r["name"]);
			$r["color"] = $this->getDomainColor($r["name"]);
			$ret[] = $r;
		}
		return $ret;
	}

	public function getMonitoringItems() {
		$ret = [];
		$s = $this->ci->db->prepare("select et.name, et.id, ifnull(e.cnt,0) as cnt from event_types et left join (select event_type, count(*) as cnt from monitoring_items group by event_type) e on et.id = e.event_type group by et.name union all select 'Failed' as name, 0 as id, ifnull(fp.cnt,0)+ifnull(fo.cnt,0) as cnt from (select count(*) as cnt from services z, serviceProcess y where z.id = y.serv_id) fp, (select count(*) as cnt from services z, serviceSockets y where z.id = y.serv_id) fo order by id");
		$s->execute();
		while($r = $s->fetch()) {
			$r["color"] = $this->getEventColor($r["name"]);
			$r["text"]  = $this->getEventTextColor($r["name"]);
			$ret[] = $r;
		}
		return $ret;
	}
	public function getFailedServices() {
		$ret = [];
		$s = $this->ci->db->prepare("select (UNIX_TIMESTAMP()*1000-f.timestamp)/1000 as late_sec, h.id as host_id, s.id as serv_id, f.status, s.name as service, h.name as host, f.timestamp from failed_services f, services s, hosts h where f.serv_id = s.id and s.host_id = h.id order by status, late_sec desc");
		$s->execute();
		while($r = $s->fetch()) {
			$r["color"]  = $this->getStatusColor($r["status"],$r["late_sec"]);
			$r["timestamp"] = $this->formatTimestamp($r["timestamp"]);
			$ret[] = $r;
		}
		return $ret;
	}


/////////////////////////////////////////////////////////////////////////////////////////////
// Pages

	public function dashboard (Request $request, Response $response) {
		$this->ci->logger->addInfo("Dashboard");
		return $this->ci->view->render($response, 'dashboard.twig', [ 
			'monitorItems'	 => $this->getMonitoringItems(),
			'events' 	 => $this->getActivesEvents(),
			'domains' 	 => $this->getHostDomains(),
			'services' 	 => $this->getFailedServices(),
			'monitorStatus'	 => $this->getMonitoringStatus()
			]);
	}
}

?>
