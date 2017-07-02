<?php
use Interop\Container\ContainerInterface;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;


class Dashboard extends CorePage {
	public function __construct(ContainerInterface $ci) { 
		parent::__construct($ci);
	}

/////////////////////////////////////////////////////////////////////////////////////////////
// WidgetData
	private function getFailedServices() {
		$_ = $this->trans;
		$ret = [];
		$ret['title'] = $_('Failed services');
		$ret['cols'] = [];
		$ret['cols'][] = array( 'text' => $_('host'), 'class'=> 'sortable');
		$ret['cols'][] = array( 'text' => $_('service'), 'class'=> 'sortable');
		$ret['cols'][] = array( 'text' => $_('since'), 'class'=> 'sortable');
		$ret['cols'][] = array( 'text' => $_('status'), 'class'=> 'sortable');
		$ret['body'] = [];
		$s = $this->db->prepare('select (UNIX_TIMESTAMP()*1000-f.timestamp)/1000 as late_sec, h.id as host_id, s.id as serv_id, f.status, s.name as service, h.name as host, f.timestamp 
  from s$failed f, s$services s, h$hosts h 
 where f.serv_id = s.id and s.host_id = h.id order by status, late_sec desc');
		$s->execute();
		while($r = $s->fetch()) {
			$ret['body'][] = array(
				'rowProperties'	=> array(
					'color'	=> $this->getStatusColor($r['status'],$r['late_sec']),
					'url'	=> $this->router->pathFor('service', [ 'sid' => $r['serv_id'], 'hid'=> $r['host_id']])
				),
				'host'	=> array('text'	=> $r['host']),
				'serv'	=> array('text'	=> $r['service']),
				'time'	=> array('text'	=> $this->formatTimestamp($r['timestamp'])),
				'status'=> array('text'	=> $r['status'])
			);
		}
		return $ret;
	}

	private function getActivesEvents() {
		$_ = $this->trans;
		$ret = [];
		$ret['title'] = $_('Current events');
		$ret['cols'] = [];
		$ret['cols'][] = array( 'text' => $_('id'), 'class'=> 'sortable');
		$ret['cols'][] = array( 'text' => $_('since'), 'class'=> 'sortable');
		$ret['cols'][] = array( 'text' => $_('host'), 'class'=> 'sortable');
		$ret['cols'][] = array( 'text' => $_('ressource'), 'class'=> 'sortable');
		$ret['cols'][] = array( 'text' => $_('property'), 'class'=> 'sortable');
		$ret['cols'][] = array( 'text' => $_('value'), 'class'=> 'sortable');
		$ret['cols'][] = array( 'text' => $_('rule'), 'class'=> 'sortable');
		$ret['body'] = [];
		$s = $this->db->prepare('select h.name as host_name, e.id, r.name, et.name as type, e.property, e.current_value, e.oper, e.value, e.start_time 
  from h$hosts h, h$res_events e, c$ressources r, c$event_types et 
 where h.id = e.host_id and e.res_id=r.id and et.id=e.event_type and e.end_time is null order by et.id');
		$s->execute();
		while($r = $s->fetch()) {
			$ret['body'][] = array(
				'rowProperties'	=> array(
					'color'	=> $this->getEventTextColor($r['type']),
					'url'	=> $this->router->pathFor('event', [ 'id' => $r['id']])
				), 'id'	=> array('text'	=> $r['id']),
				'stime'	=> array('text'	=> $this->formatTimestamp($r['start_time'])),
				'hname'	=> array('text'	=> $r['host_name']),
				'res'	=> array('text'	=> urldecode($r['name'])),
				'prop'	=> array('text'	=> $r['property']),
				'value'	=> array('text'	=> $r['current_value']),
				'rule'	=> array('text'	=> $r['oper'].intval($r['value']))
			);
		}
		return $ret;
	}

	private function getMonitoringStatus() {
		$_ = $this->trans;
		$ret = [];
		$ret['title'] = $_('Monitoring status');
		$ret['body'] = [];
		$s1 = $this->db->prepare('select "Ok" as name, 0 as id, t.cnt+po.cnt+so.cnt - m.cnt as cnt
  from (
	select count(*) as cnt 
	  from s$process
	 where (UNIX_TIMESTAMP()*1000-timestamp)/1000<60*15 
	   and status like "ok%"
  ) po, (
	select count(*) as cnt 
	  from s$sockets 
	 where (UNIX_TIMESTAMP()*1000-timestamp)/1000<60*15
	   and status like "ok%"
  ) so, (
	select count(*) as cnt
	  from h$monitoring_items
  ) t, (
	select count(*) as cnt 
	  from h$res_events e 
	 where e.end_time is null
  ) m 
union all 
select "Failed" as name, 0 as id, po.cnt+so.cnt as cnt 
  from (
	select count(*) as cnt
	  from s$process
	 where (UNIX_TIMESTAMP()*1000-timestamp)/1000<60*15 
	   and status not like "ok%"
  ) po, (
	select count(*) as cnt
	  from s$sockets
	 where (UNIX_TIMESTAMP()*1000-timestamp)/1000<60*15 
	   and  status not like "ok%"
  ) so
union all
select "Missing" as name, 2 as id, po.cnt+so.cnt as cnt 
  from (
	select count(*) as cnt 
	  from s$process 
	 where (UNIX_TIMESTAMP()*1000-timestamp)/1000>60*15
  ) po, (
	select count(*) as cnt 
	  from s$sockets 
	 where (UNIX_TIMESTAMP()*1000-timestamp)/1000>60*15
  ) so
union all
select et.name, et.id, count(e.id) as cnt 
  from c$event_types et 
  left join (
	select * 
	  from  h$res_events s
	 where s.end_time is null
  ) e on et.id = e.event_type 
 group by et.name
 order by id');
		$s1->execute();
		while($r = $s1->fetch()) {
			$ret['body'][] = array(
				'value'	=> $r['cnt'],
				'color'	=> $this->getEventColor($r['name']),
				'label'	=> $_($r['name'])
			);
		}
		return $ret;
	}

	private function getHostDomains() {
		$_ = $this->trans;
		$ret = [];
		$ret['title'] = $_('Hosts by domain');
		$ret['body'] = [];
		$s = $this->db->prepare('select 0 as id, "unset" as name, count(*) as cnt 
  from h$hosts 
 where domain_id is null 
union all 
select id, name, ifnull(h.cnt, 0) as cnt 
  from c$domains d 
  left join (
	select domain_id, count(*) as cnt 
	  from h$hosts 
	 where domain_id is not null
	 group by domain_id
  ) h on h.domain_id = d.id
 order by id');
		$s->execute();
		while($r = $s->fetch()) {
			$ret['body'][] = array(
				'value'	=> $r['cnt'],
				'label'	=> $_($r['name'])
			);
		}
		return $ret;
	}

	private function getMonitoringItems() {
		$_ = $this->trans;
		$ret = [];
		$ret['title'] = $_('All monitoring item by types');
		$ret['body'] = [];
		$s = $this->db->prepare('select et.name, et.id, ifnull(e.cnt,0) as cnt 
 from c$event_types et 
 left join (
	select event_type, count(*) as cnt 
	  from h$monitoring_items
	 group by event_type
 ) e on et.id = e.event_type group by et.name 
union all 
select "Failed" as name, 0 as id, ifnull(fp.cnt,0)+ifnull(fo.cnt,0) as cnt 
 from (
	select count(*) as cnt 
	 from s$services z, s$process y 
	where z.id = y.serv_id
 ) fp, (
	select count(*) as cnt 
	  from s$services z, s$sockets y 
	 where z.id = y.serv_id
 ) fo
 order by id');
		$s->execute();
		while($r = $s->fetch()) {
			$ret['body'][] = array(
				'value'	=> $r['cnt'],
				'color'	=> $this->getEventColor($r['name']),
				'label'	=> $_($r['name'])
			);
		}
		return $ret;
	}

	private function getServicesByType() {
		$_ = $this->trans;
		$ret = [];
		$ret['title'] = $_('Service by type');
		$ret['body'] = [];
		$s = $this->db->prepare('select t.id, t.name as label, count(s.id) as value from s$types t
 left join s$services s on t.id=s.type_id
group by t.id');
		$s->execute();
		while($r = $s->fetch()) {
			$ret['body'][] = $r;
		}
		return $ret;
	}

/////////////////////////////////////////////////////////////////////////////////////////////
// WidgetControlers

	public function widgetTableEvent (Request $request, Response $response) {
		$response->getBody()->write(json_encode($this->getActivesEvents()));
		return $response->withHeader('Content-type', 'application/json');
	}
	public function widgetTableFailed (Request $request, Response $response) {
		$response->getBody()->write(json_encode($this->getFailedServices()));
		return $response->withHeader('Content-type', 'application/json');
	}
	public function widgetDonutStatus (Request $request, Response $response) {
		$response->getBody()->write(json_encode($this->getMonitoringStatus()));
		return $response->withHeader('Content-type', 'application/json');
	}
	public function widgetDonutItem (Request $request, Response $response) {
		$response->getBody()->write(json_encode($this->getMonitoringItems()));
		return $response->withHeader('Content-type', 'application/json');
	}
	public function widgetDonutDomains (Request $request, Response $response) {
		$response->getBody()->write(json_encode($this->getHostDomains()));
		return $response->withHeader('Content-type', 'application/json');
	}
	public function widgetDonutServices (Request $request, Response $response) {
		$response->getBody()->write(json_encode($this->getServicesByType()));
		return $response->withHeader('Content-type', 'application/json');
	}

/////////////////////////////////////////////////////////////////////////////////////////////
// Controlers

	public function dashboard (Request $request, Response $response) {
		//$this->logger->addInfo("Dashboard");
		//$this->flash->addMessage('error', 'Could not change password with those details.');
 		return $this->view->render($response, 'dashboard.twig', []);
	}
}
