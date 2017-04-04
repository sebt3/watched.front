<?php
use Interop\Container\ContainerInterface;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

class Host extends CorePage {
	public function __construct(ContainerInterface $ci) { 
		parent::__construct($ci);
	}

/////////////////////////////////////////////////////////////////////////////////////////////
// Model2Widget
	private function getList() {
		$_ = $this->trans;
		$ret = [];
		$ret['body'] = [];
		$ret['cols'] = [];
		$ret['cols'][] = array( 'text' => $_('name'), 'class'=> 'sortable');
		$ret['cols'][] = array( 'text' => $_('monitor items'), 'class'=> 'sortable');
		$ret['cols'][] = array( 'text' => $_('current events'), 'class'=> 'sortable');
		$ret['cols'][] = array( 'text' => $_('ressources'), 'class'=> 'sortable');
		$ret['cols'][] = array( 'text' => $_('services'), 'class'=> 'sortable');
		$stmt = $this->db->query('select a.id, a.name as host, ifnull(m.cnt, 0)+ifnull(p.cnt, 0)+ifnull(o.cnt, 0) as monitor_items, ifnull(fo.cnt, 0)+ifnull(fp.cnt, 0) as failed, ifnull(e.cnt, 0) as events, ifnull(r.cnt, 0) as ressources, ifnull(s.cnt, 0) as services
  from h$hosts a 
  left join (
	select host_id, count(*) as cnt
	  from h$monitoring_items
	 group by host_id
  ) m on a.id=m.host_id 
  left join (
	select host_id, count(*) as cnt
	  from h$res_events
	 where end_time is null
	 group by host_id
  ) e on a.id=e.host_id 
  left join (
	select host_id, count(*) as cnt 
	  from h$ressources 
	 group by host_id
  ) r on a.id=r.host_id 
  left join (
	select host_id, count(*) as cnt 
	  from s$services
	 group by host_id
  ) s on a.id=s.host_id 
  left join (
	select host_id, count(status) as cnt 
	  from s$services z, s$process y
	 where z.id = y.serv_id
	 group by host_id
  ) p on a.id=p.host_id 
  left join (
	select host_id, count(status) as cnt
	  from s$services v, s$sockets w 
	 where v.id = w.serv_id
	 group by host_id
  ) o on a.id=o.host_id
  left join (
	select host_id, count(*) as cnt 
	  from s$services z, s$process y
	 where z.id = y.serv_id
	   and (status not like "ok%" or (UNIX_TIMESTAMP()*1000-timestamp)/1000>60*15)
	 group by host_id
  ) fp on a.id=fp.host_id
  left join (
	select host_id, count(*) as cnt 
	  from s$services z, s$sockets y
	 where z.id = y.serv_id
	   and (status not like "ok%" or (UNIX_TIMESTAMP()*1000-timestamp)/1000>60*15)
	 group by host_id
  ) fo on a.id=fo.host_id
 order by events+failed desc');
		while($r = $stmt->fetch()) {
			$ret['body'][] = array(
				'name'	=> array('text'	=> $r['host'], 'url' => $this->router->pathFor('host', [ 'id' => $r['id']])),
				'items'	=> array('text'	=> floatval($r['monitor_items'])),
				'events'=> array('text'	=> floatval($r['events'])+floatval($r['failed'])),
				'res'	=> array('text'	=> floatval($r['ressources']), 'url' => $this->router->pathFor('ressources', [ 'id' => $r['id']])),
				'serv'	=> array('text'	=> floatval($r['services']), 'url' => $this->router->pathFor('services', [ 'id' => $r['id']]))
			);
		}
		return $ret;
	}

	private function getMonitoringStatus($id) {
		$_ = $this->trans;
		$ret = [];
		$ret['title'] = $_('All monitoring item by types');
		$ret['body'] = [];
		$ret['footer'] = [];
		$s2 = $this->db->prepare('select e.id, r.name, et.name as type, e.property, e.current_value, e.oper, e.value, e.start_time
  from h$res_events e, c$ressources r, c$event_types et 
 where e.host_id=:id and e.res_id=r.id and et.id=e.event_type and e.end_time is null');
		$s2->bindParam(':id', $id, PDO::PARAM_INT);
		$s2->execute();
		while($r2 = $s2->fetch()) {
			$ret['footer'][] = array(
				'left'	=> urldecode($r2['name']).".".$r2['property'],
				'right'	=> round($r2['current_value']).$r2['oper'].round($r2['value']),
				'color'	=> $this->getEventTextColor($r2['type']),
				'url'	=> $this->router->pathFor('event', [ 'id' => $r2['id']])
			);
		}
		$s3 = $this->db->prepare('select "Ok" as name, 0 as id, t.cnt+po.cnt+so.cnt - m.cnt as cnt
  from (
	select count(*) as cnt 
	  from s$process
	 where serv_id in (
		select id 
		  from s$services
		 where host_id=:id
	) and (UNIX_TIMESTAMP()*1000-timestamp)/1000<60*15
	  and status like "ok%"
  ) po, (
	select count(*) as cnt
	  from s$sockets 
	 where serv_id in (
		select id
		  from s$services
		 where host_id=:id
	 ) and (UNIX_TIMESTAMP()*1000-timestamp)/1000<60*15
	   and status like "ok%"
  ) so, (
	select count(*) as cnt
	  from h$monitoring_items
	 where host_id=:id
  ) t, (
	select count(*) as cnt
	  from h$res_events e
	 where e.host_id=:id 
	   and e.end_time is null
  ) m
union all
select "Failed" as name, 0 as id, po.cnt+so.cnt as cnt
  from (
	select count(*) as cnt
	  from s$process
	 where serv_id in (
		select id
		  from s$services
		 where host_id=:id
	 ) and (UNIX_TIMESTAMP()*1000-timestamp)/1000<60*15
	   and status not like "ok%"
  ) po,(
	select count(*) as cnt
	  from s$sockets
	 where serv_id in (
		select id
		  from s$services
		 where host_id=:id
	 ) and (UNIX_TIMESTAMP()*1000-timestamp)/1000<60*15
	   and status not like "ok%"
  ) so
union all
select "Missing" as name, 2 as id, po.cnt+so.cnt as cnt
  from (
	select count(*) as cnt
	  from s$process
	 where serv_id in (
		select id
		  from s$services
		 where host_id=:id
	 ) and (UNIX_TIMESTAMP()*1000-timestamp)/1000>60*15
  ) po,(
	select count(*) as cnt
	  from s$sockets
	 where serv_id in (
		select id
		  from s$services
		 where host_id=:id
	 ) and (UNIX_TIMESTAMP()*1000-timestamp)/1000>60*15
  ) so
union all
select et.name, et.id, count(e.id) as cnt
  from c$event_types et 
  left join (
	select * 
	  from h$res_events s 
	 where s.host_id=:id 
	   and s.end_time is null
  ) e on et.id = e.event_type 
 group by et.name
 order by id');
		$s3->bindParam(':id', $id, PDO::PARAM_INT);
		$s3->execute();
		while($r = $s3->fetch()) {
			$ret['body'][] = array(
				'value'	=> $r['cnt'],
				'color'	=> $this->getEventColor($r['name']),
				'label'	=> $_($r['name'])
			);
		}
		return $ret;
	}

	private function getMonitoringItems($id) {
		$_ = $this->trans;
		$ret = [];
		$ret['title'] = $_('All monitoring item by types');
		$ret['body'] = [];
		$s3 = $this->db->prepare('select et.name, et.id, ifnull(e.cnt,0) as cnt
  from c$event_types et
  left join (
	select event_type, count(*) as cnt
	  from h$monitoring_items
	 where host_id=:id
	 group by event_type
  ) e on et.id = e.event_type
 group by et.name
union all
select "Failed" as name, 0 as id, ifnull(fp.cnt,0)+ifnull(fo.cnt,0) as cnt
  from (
	select count(*) as cnt 
	  from s$services z, s$process y
	 where z.id = y.serv_id and host_id=:id
  ) fp, (
	select count(*) as cnt
	  from s$services z, s$sockets y
	 where z.id = y.serv_id
	   and host_id=:id
  ) fo
 order by id');
		$s3->bindParam(':id', $id, PDO::PARAM_INT);
		$s3->execute();
		while($r = $s3->fetch()) {
			$ret['body'][] = array(
				'value'	=> $r['cnt'],
				'color'	=> $this->getEventColor($r['name']),
				'label'	=> $_($r['name'])
			);
		}
		return $ret;
	}

	private function getServicesByType($id) {
		$_ = $this->trans;
		$ret = [];
		$ret['title'] = $_('Services by type');
		$ret['body'] = [];
		$s3 = $this->db->prepare('select t.id, t.name as label, count(s.id) as value from s$types t
 left join s$services s on t.id=s.type_id
where s.host_id=:id
group by t.id');
		$s3->bindParam(':id', $id, PDO::PARAM_INT);
		$s3->execute();
		while($r = $s3->fetch()) {
			$ret['body'][] = $r;
		}
		return $ret;
	}

	private function getMonitoringHistory($id) {
		$_ = $this->trans;
		$ret = [];
		$ret['title'] = $_('Events history');
		$ret['cols'] = [];
		$ret['cols'][] = array( 'text' => $_('id'), 'class'=> 'sortable');
		$ret['cols'][] = array( 'text' => $_('start'), 'class'=> 'sortable');
		$ret['cols'][] = array( 'text' => $_('end'), 'class'=> 'sortable');
		$ret['cols'][] = array( 'text' => $_('ressource'), 'class'=> 'sortable');
		$ret['cols'][] = array( 'text' => $_('property'), 'class'=> 'sortable');
		$ret['cols'][] = array( 'text' => $_('value'), 'class'=> 'sortable');
		$ret['cols'][] = array( 'text' => $_('rule'), 'class'=> 'sortable');
		$ret['body'] = [];
		$s5 = $this->db->prepare('select r.name as res_name, e.id, e.start_time, e.end_time, e.property, e.current_value, e.oper, e.value, et.name as event_name
  from h$res_events e, c$event_types et, c$ressources r
 where e.res_id=r.id
   and e.end_time is not null
   and e.event_type=et.id
   and host_id=:aid
 order by start_time desc
 limit 6');
		$s5->bindParam(':aid', $id, PDO::PARAM_INT);
		$s5->execute();
		while($r = $s5->fetch()) {
			$ret['body'][] = array(
				'rowProperties'	=> array(
					'color'	=> $this->getEventTextColor($r['event_name']),
					'url'	=> $this->router->pathFor('event', [ 'id' => $r['id']])
				), 'id'	=> array('text'	=> floatval($r['id'])),
				'stime'	=> array('text'	=> $this->formatTimestamp($r['start_time'])),
				'etime'	=> array('text'	=> $r['end_time']!=null?$this->formatTimestamp($r['end_time']):''),
				'res'	=> array('text'	=> urldecode($r['res_name'])),
				'prop'	=> array('text'	=> $r['property']),
				'value'	=> array('text'	=> floatval($r['current_value'])),
				'rule'	=> array('text'	=> $r['oper'].intval($r['value']))
			);
		}
		return $ret;
	}

	private function getStorageStatus($id) {
		$_ = $this->trans;
		$ret = [];
		$ret['title'] = $_('Storage status');
		$ret['body'] = [];
		$s6 = $this->db->prepare('select ar.host_id, ar.res_id, r.name as res_name, d.free, d.ipctfree, d.pctfree, 100-d.pctfree pctused, d.size
  from h$ressources ar, c$ressources r, d$disk_usage d, (
	select host_id, res_id, max(timestamp) ts
	  from d$disk_usage
	 group by host_id, res_id
  ) t
 where ar.res_id=r.id
   and r.data_type="disk_usage"
   and ar.host_id=d.host_id
   and ar.res_id=d.res_id
   and d.host_id=t.host_id
   and d.res_id=t.res_id
   and d.timestamp=t.ts
   and ar.host_id=:id
   and d.pctfree < 90
 order by pctused desc');
		$s6->bindParam(':id', $id, PDO::PARAM_INT);
		$s6->execute();
		while($r = $s6->fetch()) {
			$ret['body'][] = array(
				'title'	=> urldecode(substr($r['res_name'], 10)),
				'url'	=> $this->router->pathFor('ressource', [ 'aid' => $r['host_id'], 'rid' => $r['res_id']]),
				'items'	=> array(
					array(	'pct'	=> $r['pctused'],
						'class'	=> 'progress-bar-'.$this->getPctColor($r['pctused'])
					)
			));
		}
		return $ret;
	}

	private function getCPUusage($id) {
		$_ = $this->trans;
		$ret = [];
		$ret['title'] = $_('CPU usage');
		$ret['body'] = [];
		$s7 = $this->db->prepare('select u.host_id, u.res_id, r.name as res_name, u.iowait, u.irq, u.nice, u.softirq, u.system, u.user, u.iowait+u.irq+u.nice+u.softirq+u.system+u.user as pctused
  from h$ressources ar, c$ressources r, d$cpu_usage u, (
	select host_id, res_id, max(timestamp) as ts
	  from d$cpu_usage
	 group by host_id, res_id
  ) cu
 where u.host_id=cu.host_id
   and u.res_id = cu.res_id
   and u.timestamp = cu.ts
   and ar.res_id=r.id
   and r.data_type="cpu_usage"
   and ar.host_id=u.host_id
   and ar.res_id=u.res_id
   and ar.host_id=:id
 order by res_name asc');
		$s7->bindParam(':id', $id, PDO::PARAM_INT);
		$s7->execute();
		while($r = $s7->fetch()) {
			$ret['body'][] = array(
				'title'	=> substr($r['res_name'], 7),
				'url'	=> $this->router->pathFor('ressource', [ 'aid' => $r['host_id'], 'rid' => $r['res_id']]),
				'items'	=> array(
					array('pct'	=> floatval($r['user']),	'class'	=> 'progress-bar-red'),
					array('pct'	=> floatval($r['system']),	'class'	=> 'progress-bar-yellow'),
					array('pct'	=> floatval($r['nice']),	'class'	=> 'progress-bar-green'),
					array('pct'	=> floatval($r['iowait']),	'class'	=> 'progress-bar-blue'),
					array('pct'	=> floatval($r['irq']),		'class'	=> 'progress-bar-light-blue'),
					array('pct'	=> floatval($r['softirq']),	'class'	=> 'progress-bar-aqua')
			));
		}
		return $ret;
	}

	private function getMemoryUsage($id) {
		$_ = $this->trans;
		$ret = [];
		$ret['title'] = $_('Memory usage (MB)');
		$ret['body'] = [];
		$s8 = $this->db->prepare('select m.host_id, m.res_id, "memory" as name, (m.apps+m.buffer+m.slab+m.cached)/1024 as used, m.free/1024 as free, m.pct as pctfree, 100-m.pct as pctused
  from d$memory_usage m, (
	select host_id, res_id, max(timestamp) ts
	  from d$memory_usage
	 group by host_id, res_id
  ) t
 where m.host_id=t.host_id
   and m.res_id=t.res_id
   and m.timestamp=t.ts
   and m.host_id=:id
union all
select s.host_id, s.res_id, "swap" as name, s.used/1024 as used, s.free/1024 as free, s.pct as pctfree, 100-s.pct as pctused
  from d$swap_usage s, (
	select host_id, res_id, max(timestamp) ts
	  from d$swap_usage
	 group by host_id, res_id
  ) u
 where s.host_id=u.host_id
   and s.res_id=u.res_id
   and s.timestamp=u.ts
   and s.host_id=:id');
		$s8->bindParam(':id', $id, PDO::PARAM_INT);
		$s8->execute();
		while($r = $s8->fetch()) {
			$ret['body'][] = array(
				'type'	=> $r['name'],
				'used'	=> intval($r['used']),
				'free'	=> intval($r['free']),
				'url'	=> $this->router->pathFor('ressource', [ 'aid' => $r['host_id'], 'rid' => $r['res_id']])
			);
		}
		return $ret;
	}

	private function getStats($id) {
		$_ = $this->trans;
		$ret = [];
		$ret['title'] = $_('Statistics');
		$ret['body'] = [];
		$s9 = $this->db->prepare('select r.name, ar.host_id, ar.res_id
  from c$ressources r, h$ressources ar
 where r.data_type like "%stat%"
   and ar.res_id=r.id
   and ar.host_id=:id');
		$s9->bindParam(':id', $id, PDO::PARAM_INT);
		$s9->execute();
		while($r = $s9->fetch()) {
			$ret['body'][] = array(
				'text'	=> $r['name'],
				'url'	=> $this->router->pathFor('ressource', [ 'aid' => $r['host_id'], 'rid' => $r['res_id']])
			);
		}
		return $ret;
	}

	private function getOtherRessources($id) {
		$_ = $this->trans;
		$ret = [];
		$ret['title'] = $_('Other ressources');
		$ret['body'] = [];
		$s10 = $this->db->prepare('select r.name, ar.host_id, ar.res_id
  from c$ressources r, h$ressources ar
 where r.data_type not in ("disk_usage", "cpu_usage", "memory_usage", "swap_usage")
   and r.data_type not like "%stat%"
   and ar.res_id=r.id
   and ar.host_id=:id');
		$s10->bindParam(':id', $id, PDO::PARAM_INT);
		$s10->execute();
		while($r = $s10->fetch()) {
			$ret['body'][] = array(
				'text'	=> $r['name'],
				'url'	=> $this->router->pathFor('ressource', [ 'aid' => $r['host_id'], 'rid' => $r['res_id']])
			);
		}
		return $ret;
	}

	private function getServices($id) {
		$_ = $this->trans;
		$ret = [];
		$ret['title'] = $_('Services');
		$ret['body'] = [];
		$s   = $this->db->prepare('select max(late_sec) as late_sec, count(distinct status) as cnt_stat, min(status) as status, x.serv_id, s.name
  from (
	select (UNIX_TIMESTAMP()*1000-min(timestamp))/1000 as late_sec, status, serv_id
	  from s$sockets
	 group by status, serv_id
	union
	select (UNIX_TIMESTAMP()*1000-min(timestamp))/1000 as late_sec, status, serv_id
	  from s$process
	 group by status, serv_id
  ) x, s$services s
 where x.serv_id = s.id
   and s.host_id=:id
 group by x.serv_id, s.name');
		$s->bindParam(':id', $id, PDO::PARAM_INT);
		$s->execute();
		while($r = $s->fetch()) {
			$ret['body'][] = array(
				'text'	=> $r['name'],
				'color'	=> $this->getStatusColor($r['status'], $r['late_sec']),
				'url'	=> $this->router->pathFor('service', [ 'hid' => $id, 'sid'=> $r['serv_id']])
			);
		}
		return $ret;
	}

	private function getAllRessources($id) {
		$_ = $this->trans;
		$ret = [];
		$ret['cols'] = [];
		$ret['cols'][] = array( 'text' => $_('name'), 'class'=> 'sortable');
		$ret['cols'][] = array( 'text' => $_('monitor items'), 'class'=> 'sortable');
		$ret['cols'][] = array( 'text' => $_('current events'), 'class'=> 'sortable');
		$ret['body'] = [];
		$stmt = $this->db->prepare('select r.*, ifnull(m.cnt,0) as monitors, ifnull(e.cnt,0) as events 
  from (
	select *
	  from h$ressources ar, c$ressources re
	 where ar.res_id=re.id
  ) r
  left join (
	select host_id, res_id, count(*) as cnt
	  from h$monitoring_items
	 group by host_id, res_id
  ) m on m.host_id=r.host_id and m.res_id=r.res_id
  left join (
	select host_id, res_id, count(*) as cnt
	  from h$res_events
	 where end_time is null
	 group by host_id, res_id
  ) e on e.host_id=r.host_id and e.res_id=r.res_id
 where r.host_id=:id
 order by events desc, monitors desc, r.name');
		$stmt->bindParam(':id', $id, PDO::PARAM_INT);
		$stmt->execute();
		while($r = $stmt->fetch()) {
			$ret['body'][] = array(
				'name'	=> array('text'	=> urldecode($r['name']), 'url' => $this->router->pathFor('ressource', [ 'aid' => $id, 'rid' => $r['id']])),
				'mon'	=> array('text'	=> floatval($r['monitors'])),
				'events'=> array('text'	=> floatval($r['events']))
			);
		}
		return $ret;
	}

/////////////////////////////////////////////////////////////////////////////////////////////
// WidgetControlers

	public function widgetDonutStatus(Request $request, Response $response) {
		$id = $request->getAttribute('id');
		if ($this->getHost($id) == false)
			throw new Slim\Exception\NotFoundException($request, $response);
		$this->auth->assertHost($id, $request, $response);
		$response->getBody()->write(json_encode($this->getMonitoringStatus($id)));
		return $response->withHeader('Content-type', 'application/json');
	}
	public function widgetDonutServices(Request $request, Response $response) {
		$id = $request->getAttribute('id');
		if ($this->getHost($id) == false)
			throw new Slim\Exception\NotFoundException($request, $response);
		$this->auth->assertHost($id, $request, $response);
		$response->getBody()->write(json_encode($this->getServicesByType($id)));
		return $response->withHeader('Content-type', 'application/json');
	}
	public function widgetDonutItems(Request $request, Response $response) {
		$id = $request->getAttribute('id');
		if ($this->getHost($id) == false)
			throw new Slim\Exception\NotFoundException($request, $response);
		$this->auth->assertHost($id, $request, $response);
		$response->getBody()->write(json_encode($this->getMonitoringItems($id)));
		return $response->withHeader('Content-type', 'application/json');
	}
	public function widgetTableHistory(Request $request, Response $response) {
		$id = $request->getAttribute('id');
		if ($this->getHost($id) == false)
			throw new Slim\Exception\NotFoundException($request, $response);
		$this->auth->assertHost($id, $request, $response);
		$response->getBody()->write(json_encode($this->getMonitoringHistory($id)));
		return $response->withHeader('Content-type', 'application/json');
	}
	public function widgetListServices(Request $request, Response $response) {
		$id = $request->getAttribute('id');
		if ($this->getHost($id) == false)
			throw new Slim\Exception\NotFoundException($request, $response);
		$this->auth->assertHost($id, $request, $response);
		$response->getBody()->write(json_encode($this->getServices($id)));
		return $response->withHeader('Content-type', 'application/json');
	}
	public function widgetListStats(Request $request, Response $response) {
		$id = $request->getAttribute('id');
		if ($this->getHost($id) == false)
			throw new Slim\Exception\NotFoundException($request, $response);
		$this->auth->assertHost($id, $request, $response);
		$response->getBody()->write(json_encode($this->getStats($id)));
		return $response->withHeader('Content-type', 'application/json');
	}
	public function widgetListRessources(Request $request, Response $response) {
		$id = $request->getAttribute('id');
		if ($this->getHost($id) == false)
			throw new Slim\Exception\NotFoundException($request, $response);
		$this->auth->assertHost($id, $request, $response);
		$response->getBody()->write(json_encode($this->getOtherRessources($id)));
		return $response->withHeader('Content-type', 'application/json');
	}
	public function widgetProgressCpu(Request $request, Response $response) {
		$id = $request->getAttribute('id');
		if ($this->getHost($id) == false)
			throw new Slim\Exception\NotFoundException($request, $response);
		$this->auth->assertHost($id, $request, $response);
		$response->getBody()->write(json_encode($this->getCPUusage($id)));
		return $response->withHeader('Content-type', 'application/json');
	}
	public function widgetProgressStorage(Request $request, Response $response) {
		$id = $request->getAttribute('id');
		if ($this->getHost($id) == false)
			throw new Slim\Exception\NotFoundException($request, $response);
		$this->auth->assertHost($id, $request, $response);
		$response->getBody()->write(json_encode($this->getStorageStatus($id)));
		return $response->withHeader('Content-type', 'application/json');
	}
	public function widgetMemSwap(Request $request, Response $response) {
		$id = $request->getAttribute('id');
		if ($this->getHost($id) == false)
			throw new Slim\Exception\NotFoundException($request, $response);
		$this->auth->assertHost($id, $request, $response);
		$response->getBody()->write(json_encode($this->getMemoryUsage($id)));
		return $response->withHeader('Content-type', 'application/json');
	}

/////////////////////////////////////////////////////////////////////////////////////////////
// Controlers
	public function Hosts (Request $request, Response $response) {
		//$this->logger->addInfo("Host list");
		$this->menu->breadcrumb = array( array('name' => 'Hosts', 'icon' => 'fa fa-server', 'url' => $this->router->pathFor('hosts')) );
		return $this->view->render($response, 'hosts/hosts.twig', [ 'hosts' => $this->getList() ]);
	}

	public function Host (Request $request, Response $response) {
		$id = $request->getAttribute('id');
		//$this->logger->addInfo("Host $id");
		$host = $this->getHost($id);
		if ($host == false)
			throw new Slim\Exception\NotFoundException($request, $response);
		$this->auth->assertHost($id, $request, $response);

		$this->menu->activateHost($host['host']);
		$this->menu->breadcrumb = array(
			array('name' => 'hosts', 'icon' => 'fa fa-server', 'url' => $this->router->pathFor('hosts') ), 
			array('name' => $host['host'], 'url' => $this->router->pathFor('host', array('id' => $id)) ));
		return $this->view->render($response, 'hosts/host.twig', [ 
			'host'		 => $host
			]);
	}

	public function ressources(Request $request, Response $response) {
		$id = $request->getAttribute('id');
		$this->auth->assertHost($id, $request, $response);
		//$this->logger->addInfo("Host $id");
		$host = $this->getHost($id);
		if ($host == false)
			throw new Slim\Exception\NotFoundException($request, $response);
		$this->menu->activateHost($host['host']);
		$this->menu->breadcrumb = array(
			array('name' => 'hosts', 'icon' => 'fa fa-server', 'url' => $this->router->pathFor('hosts') ), 
			array('name' => $host['host'], 'url' => $this->router->pathFor('host', array('id' => $id)) ), 
			array('name' => 'resources', 'icon' => 'fa fa-area-chart', 'url' => $this->router->pathFor('ressources', array('id' => $id))) );
		return $this->view->render($response, 'hosts/ressources.twig', [ 'host' => $host, 'ressources' => $this->getAllRessources($id) ]);
	}
}

?>
