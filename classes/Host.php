<?php
use Interop\Container\ContainerInterface;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

class Host extends CorePage {
	public function __construct(ContainerInterface $ci) { 
		parent::__construct($ci);
	}

	public function getList() {
		$results = [];
		$stmt = $this->ci->db->query("select a.id, a.name as host, ifnull(m.cnt, 0)+ifnull(p.cnt, 0)+ifnull(o.cnt, 0) as monitor_items, ifnull(fo.cnt, 0)+ifnull(fp.cnt, 0) as failed, ifnull(e.cnt, 0) as events, ifnull(r.cnt, 0) as ressources, ifnull(s.cnt, 0) as services
  from h\$hosts a 
  left join (
	select host_id, count(*) as cnt
	  from h\$monitoring_items
	 group by host_id
  ) m on a.id=m.host_id 
  left join (
	select host_id, count(*) as cnt
	  from h\$res_events
	 where end_time is null
	 group by host_id
  ) e on a.id=e.host_id 
  left join (
	select host_id, count(*) as cnt 
	  from h\$ressources 
	 group by host_id
  ) r on a.id=r.host_id 
  left join (
	select host_id, count(*) as cnt 
	  from s\$services
	 group by host_id
  ) s on a.id=s.host_id 
  left join (
	select host_id, count(status) as cnt 
	  from s\$services z, s\$process y
	 where z.id = y.serv_id
	 group by host_id
  ) p on a.id=p.host_id 
  left join (
	select host_id, count(status) as cnt
	  from s\$services v, s\$sockets w 
	 where v.id = w.serv_id
	 group by host_id
  ) o on a.id=o.host_id
  left join (
	select host_id, count(*) as cnt 
	  from s\$services z, s\$process y
	 where z.id = y.serv_id
	   and (status not like 'ok%' or (UNIX_TIMESTAMP()*1000-timestamp)/1000>60*15)
	 group by host_id
  ) fp on a.id=fp.host_id
  left join (
	select host_id, count(*) as cnt 
	  from s\$services z, s\$sockets y
	 where z.id = y.serv_id
	   and (status not like 'ok%' or (UNIX_TIMESTAMP()*1000-timestamp)/1000>60*15)
	 group by host_id
  ) fo on a.id=fo.host_id
 order by events+failed desc");
		while($row = $stmt->fetch())
			$results[] = $row;
		return $results;
	}

	public function getMonitoringStatus($id) {
		$ret = [];
		$s1 = $this->ci->db->prepare("select 'Ok' as name, 0 as id, t.cnt+po.cnt+so.cnt - m.cnt as cnt
  from (
	select count(*) as cnt 
	  from s\$process
	 where serv_id in (
		select id 
		  from s\$services
		 where host_id=:id
	) and (UNIX_TIMESTAMP()*1000-timestamp)/1000<60*15
	  and status like 'ok%'
  ) po, (
	select count(*) as cnt
	  from s\$sockets 
	 where serv_id in (
		select id
		  from s\$services
		 where host_id=:id
	 ) and (UNIX_TIMESTAMP()*1000-timestamp)/1000<60*15
	   and status like 'ok%'
  ) so, (
	select count(*) as cnt
	  from h\$monitoring_items
	 where host_id=:id
  ) t, (
	select count(*) as cnt
	  from h\$res_events e
	 where e.host_id=:id 
	   and e.end_time is null
  ) m
union all
select 'Failed' as name, 0 as id, po.cnt+so.cnt as cnt
  from (
	select count(*) as cnt
	  from s\$process
	 where serv_id in (
		select id
		  from s\$services
		 where host_id=:id
	 ) and (UNIX_TIMESTAMP()*1000-timestamp)/1000<60*15
	   and status not like 'ok%'
  ) po,(
	select count(*) as cnt
	  from s\$sockets
	 where serv_id in (
		select id
		  from s\$services
		 where host_id=:id
	 ) and (UNIX_TIMESTAMP()*1000-timestamp)/1000<60*15
	   and status not like 'ok%'
  ) so
union all
select 'Missing' as name, 2 as id, po.cnt+so.cnt as cnt
  from (
	select count(*) as cnt
	  from s\$process
	 where serv_id in (
		select id
		  from s\$services
		 where host_id=:id
	 ) and (UNIX_TIMESTAMP()*1000-timestamp)/1000>60*15
  ) po,(
	select count(*) as cnt
	  from s\$sockets
	 where serv_id in (
		select id
		  from s\$services
		 where host_id=:id
	 ) and (UNIX_TIMESTAMP()*1000-timestamp)/1000>60*15
  ) so
union all
select et.name, et.id, count(e.id) as cnt
  from c\$event_types et 
  left join (
	select * 
	  from h\$res_events s 
	 where s.host_id=:id 
	   and s.end_time is null
  ) e on et.id = e.event_type 
 group by et.name
 order by id");
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
		$s2 = $this->ci->db->prepare("select e.id, r.name, et.name as type, e.property, e.current_value, e.oper, e.value, e.start_time
  from h\$res_events e, c\$ressources r, c\$event_types et 
 where e.host_id=:id and e.res_id=r.id and et.id=e.event_type and e.end_time is null");
		$s2->bindParam(':id', $id, PDO::PARAM_INT);
		$s2->execute();
		while($r2 = $s2->fetch()) {
			$r2["color"] = $this->getEventTextColor($r2["type"]);
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
		$s3 = $this->ci->db->prepare("select et.name, et.id, ifnull(e.cnt,0) as cnt
  from c\$event_types et
  left join (
	select event_type, count(*) as cnt
	  from h\$monitoring_items
	 where host_id=:id
	 group by event_type
  ) e on et.id = e.event_type
 group by et.name
union all
select 'Failed' as name, 0 as id, ifnull(fp.cnt,0)+ifnull(fo.cnt,0) as cnt
  from (
	select count(*) as cnt 
	  from s\$services z, s\$process y
	 where z.id = y.serv_id and host_id=:id
  ) fp, (
	select count(*) as cnt
	  from s\$services z, s\$sockets y
	 where z.id = y.serv_id
	   and host_id=:id
  ) fo
 order by id");
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
		$s5 = $this->ci->db->prepare("select r.name as res_name, e.id, e.start_time, e.end_time, e.property, e.current_value, e.oper, e.value, et.name as event_name
  from h\$res_events e, c\$event_types et, c\$ressources r
 where e.res_id=r.id
   and e.end_time is not null
   and e.event_type=et.id
   and host_id=:aid
 order by start_time desc
 limit 6");
		$s5->bindParam(':aid', $id, PDO::PARAM_INT);
		$s5->execute();
		while($r5 = $s5->fetch()) {
			$r5["color"] = $this->getEventTextColor($r5["event_name"]);
			$r5["value"] = round($r5["value"]);
			$r5["current_value"] = round($r5["current_value"]);
			$r5["encode"] = urldecode($r5["oper"]);
			$r5["start_time"] = $this->formatTimestamp($r5["start_time"]);
			if ($r5["end_time"] != null)
				$r5["end_time"] = $this->formatTimestamp($r5["end_time"]);
			$r5["decode"] = urldecode($r5["res_name"]);
			$ret[] = $r5;
		}
		return $ret;
	}

	public function getStorageStatus($id) {
		$ret = [];
		$s6 = $this->ci->db->prepare("select ar.host_id, ar.res_id, r.name as res_name, d.free, d.ipctfree, d.pctfree, 100-d.pctfree pctused, d.size
  from h\$ressources ar, c\$ressources r, d\$disk_usage d, (
	select host_id, res_id, max(timestamp) ts
	  from d\$disk_usage
	 group by host_id, res_id
  ) t
 where ar.res_id=r.id
   and r.data_type='disk_usage'
   and ar.host_id=d.host_id
   and ar.res_id=d.res_id
   and d.host_id=t.host_id
   and d.res_id=t.res_id
   and d.timestamp=t.ts
   and ar.host_id=:id
   and d.pctfree < 90
 order by pctused desc");
		$s6->bindParam(':id', $id, PDO::PARAM_INT);
		$s6->execute();
		while($r6 = $s6->fetch()) {
			$r6["decode"] = urldecode(substr($r6["res_name"], 10));
			$r6["color"]  = $this->getPctColor($r6["pctused"]);
			$ret[] = $r6;
		}
		return $ret;
	}

	public function getCPUusage($id) {
		$ret = [];
		$s7 = $this->ci->db->prepare("select u.host_id, u.res_id, r.name as res_name, u.iowait, u.irq, u.nice, u.softirq, u.system, u.user, u.iowait+u.irq+u.nice+u.softirq+u.system+u.user as pctused
  from h\$ressources ar, c\$ressources r, d\$cpu_usage u, (
	select host_id, res_id, max(timestamp) as ts
	  from d\$cpu_usage
	 group by host_id, res_id
  ) cu
 where u.host_id=cu.host_id
   and u.res_id = cu.res_id
   and u.timestamp = cu.ts
   and ar.res_id=r.id
   and r.data_type='cpu_usage'
   and ar.host_id=u.host_id
   and ar.res_id=u.res_id
   and ar.host_id=:id
 order by pctused desc");
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
		$s8 = $this->ci->db->prepare("select m.host_id, m.res_id, 'memory' as name, (m.apps+m.buffer+m.slab+m.cached)/1024 as used, m.free/1024 as free, m.pct as pctfree, 100-m.pct as pctused
  from d\$memory_usage m, (
	select host_id, res_id, max(timestamp) ts
	  from d\$memory_usage
	 group by host_id, res_id
  ) t
 where m.host_id=t.host_id
   and m.res_id=t.res_id
   and m.timestamp=t.ts
   and m.host_id=:id
union all
select s.host_id, s.res_id, 'swap' as name, s.used/1024 as used, s.free/1024 as free, s.pct as pctfree, 100-s.pct as pctused
  from d\$swap_usage s, (
	select host_id, res_id, max(timestamp) ts
	  from d\$swap_usage
	 group by host_id, res_id
  ) u
 where s.host_id=u.host_id
   and s.res_id=u.res_id
   and s.timestamp=u.ts
   and s.host_id=:id");
		$s8->bindParam(':id', $id, PDO::PARAM_INT);
		$s8->execute();
		while($r8 = $s8->fetch()) {
			$ret[] = $r8;
		}
		return $ret;
	}

	public function getStats($id) {
		$ret = [];
		$s9 = $this->ci->db->prepare("select r.name, ar.host_id, ar.res_id
  from c\$ressources r, h\$ressources ar
 where r.data_type like '%stat%'
   and ar.res_id=r.id
   and ar.host_id=:id");
		$s9->bindParam(':id', $id, PDO::PARAM_INT);
		$s9->execute();
		while($r9 = $s9->fetch()) {
			$ret[] = $r9;
		}
		return $ret;
	}

	public function getOtherRessources($id) {
		$ret = [];
		$s10 = $this->ci->db->prepare("select r.name, ar.host_id, ar.res_id
  from c\$ressources r, h\$ressources ar
 where r.data_type not in ('disk_usage', 'cpu_usage', 'memory_usage', 'swap_usage')
   and r.data_type not like '%stat%'
   and ar.res_id=r.id
   and ar.host_id=:id");
		$s10->bindParam(':id', $id, PDO::PARAM_INT);
		$s10->execute();
		while($r10 = $s10->fetch()) {
			$ret[] = $r10;
		}
		return $ret;
	}

	public function getAllRessources($id) {
		$stmt = $this->ci->db->prepare("select r.*, ifnull(m.cnt,0) as monitors, ifnull(e.cnt,0) as events 
  from (
	select *
	  from h\$ressources ar, c\$ressources re
	 where ar.res_id=re.id
  ) r
  left join (
	select host_id, res_id, count(*) as cnt
	  from h\$monitoring_items
	 group by host_id, res_id
  ) m on m.host_id=r.host_id and m.res_id=r.res_id
  left join (
	select host_id, res_id, count(*) as cnt
	  from h\$res_events
	 where end_time is null
	 group by host_id, res_id
  ) e on e.host_id=r.host_id and e.res_id=r.res_id
 where r.host_id=:id
 order by events desc, monitors desc, r.name");
		$stmt->bindParam(':id', $id, PDO::PARAM_INT);
		$stmt->execute();
		$results = [];
		while($row = $stmt->fetch()) {
			$row["name"]=urldecode($row["name"]);
			$results[] = $row;
		}
		return $results;
	}

	public function getServices($id) {
		$ret = [];
		$s   = $this->ci->db->prepare("select max(late_sec) as late_sec, count(distinct status) as cnt_stat, min(status) as status, x.serv_id, s.name
  from (
	select (UNIX_TIMESTAMP()*1000-min(timestamp))/1000 as late_sec, status, serv_id
	  from s\$sockets
	 group by status, serv_id
	union
	select (UNIX_TIMESTAMP()*1000-min(timestamp))/1000 as late_sec, status, serv_id
	  from s\$process
	 group by status, serv_id
  ) x, s\$services s
 where x.serv_id = s.id
   and s.host_id=:id
 group by x.serv_id, s.name");
		$s->bindParam(':id', $id, PDO::PARAM_INT);
		$s->execute();
		while($r = $s->fetch()) {
			$r["color"]	= $this->getStatusColor($r["status"], $r["late_sec"]);
			$ret[] = $r;
		}
		return $ret;
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

		$this->ci->view["menu"]->activateHost($host["host"]);
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
			'services'	 => $this->getServices($id),
			'ressources' 	 => $this->getOtherRessources($id)
			]);
	}

	public function ressources(Request $request, Response $response) {
		$id = $request->getAttribute('id');
		$this->ci->logger->addInfo("Host $id");
		$host = $this->getHost($id);
		if ($host == false)
			return  $response->withStatus(404);
		$this->ci->view["menu"]->activateHost($host["host"]);
		$this->ci->view["menu"]->breadcrumb = array(
			array("name" => "hosts", "icon" => "fa fa-server", "url" => $this->ci->router->pathFor('hosts') ), 
			array("name" => $host["host"], "url" => $this->ci->router->pathFor('host', array('id' => $id)) ), 
			array("name" => "resources", "icon" => "fa fa-area-chart", "url" => $this->ci->router->pathFor('ressources', array('id' => $id))) );
		return $this->ci->view->render($response, 'ressources.twig', [ 'host' => $host, 'ressources' => $this->getAllRessources($id) ]);
	}
}

?>
