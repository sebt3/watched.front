<?php
use Interop\Container\ContainerInterface;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

class HostRessource extends CorePage {
	public function __construct(ContainerInterface $ci) { 
		parent::__construct($ci);
	}

/////////////////////////////////////////////////////////////////////////////////////////////
// Model
	public function haveMonitoring($aid, $rid) {
		$s0 = $this->db->prepare('select count(*) as cnt from h$monitoring_items where host_id=:aid and res_id=:rid');
		$s0->bindParam(':aid', $aid, PDO::PARAM_INT);
		$s0->bindParam(':rid', $rid, PDO::PARAM_INT);
		$s0->execute();
		$c = $s0->fetch();
		return ($c['cnt'] > 0);
	}

	public function getMonitoringStatus($aid, $rid) {
		$_ = $this->trans;
		$ret = [];
		$ret['title'] = $_('Monitoring status');
		$ret['body'] = [];
		$ret['footer'] = [];
		$s1  = $this->db->prepare('select "Ok" as name, 0 as id, t.cnt - m.cnt as cnt 
  from (
	select count(*) as cnt 
	  from h$monitoring_items 
	 where host_id=:aid and res_id=:rid
  ) t, (
	select count(*) as cnt 
	  from h$res_events e 
	 where e.host_id=:aid 
	   and e.res_id=:rid
	   and e.end_time is null
  ) m
union all 
select et.name, et.id, count(e.id) as cnt 
  from c$event_types et 
  left join (
	select * 
	  from h$res_events s 
	 where s.host_id=:aid 
	   and s.res_id=:rid 
	   and s.end_time is null
  ) e on et.id = e.event_type 
 group by et.name
 order by id');
		$s1->bindParam(':aid', $aid, PDO::PARAM_INT);
		$s1->bindParam(':rid', $rid, PDO::PARAM_INT);
		$s1->execute();
		while($r = $s1->fetch()) {
			$ret['body'][] = array(
				'value'	=> $r['cnt'],
				'color'	=> $this->getEventColor($r['name']),
				'label'	=> $_($r['name'])
			);
		}
		$s2  = $this->db->prepare('select e.id, r.name, et.name as type, e.property, e.current_value, e.oper, e.value, e.start_time
  from h$res_events e, c$ressources r, c$event_types et 
 where e.host_id=:aid
   and e.res_id=:rid
   and e.res_id=r.id
   and et.id=e.event_type
   and e.end_time is null');
		$s2->bindParam(':aid', $aid, PDO::PARAM_INT);
		$s2->bindParam(':rid', $rid, PDO::PARAM_INT);
		$s2->execute();
		while($r2 = $s2->fetch()) {
			$ret['footer'][] = array(
				'left'	=> $r2['property'],
				'right'	=> round($r2['current_value']).$r2['oper'].round($r2['value']),
				'color'	=> $this->getEventTextColor($r2['type']),
				'url'	=> $this->router->pathFor('event', [ 'id' => $r2['id']])
			);
		}
		return $ret;
	}

	public function getMonitoringItems($aid, $rid) {
		$_ = $this->trans;
		$ret = [];
		$ret['title'] = $_('All monitoring item by types');
		$ret['body'] = [];
		$ret['footer'] = [];
		$s3  = $this->db->prepare('select et.name, et.id, ifnull(e.cnt,0) as cnt 
  from c$event_types et 
  left join (
	select event_type, count(*) as cnt 
	  from h$monitoring_items 
	 where host_id=:aid
	   and res_id=:rid
	 group by event_type
) e on et.id = e.event_type 
 group by et.name
 order by id');
		$s3->bindParam(':aid', $aid, PDO::PARAM_INT);
		$s3->bindParam(':rid', $rid, PDO::PARAM_INT);
		$s3->execute();
		while($r = $s3->fetch()) {
			$ret['body'][] = array(
				'value'	=> $r['cnt'],
				'color'	=> $this->getEventColor($r['name']),
				'label'	=> $_($r['name'])
			);
		}

		$s4  = $this->db->prepare('select res_name, res_type, event_name, property, oper, value 
  from h$monitoring_items
 where host_id=:aid 
   and res_id=:rid 
 order by property, event_type');
		$s4->bindParam(':aid', $aid, PDO::PARAM_INT);
		$s4->bindParam(':rid', $rid, PDO::PARAM_INT);
		$s4->execute();
		while($r2 = $s4->fetch()) {
			$ret['footer'][] = array(
				'left'	=> $r2['property'],
				'right'	=> $r2['oper'].round($r2['value']),
				'color'	=> $this->getEventTextColor($r2['event_name'])
			);
		}
		return $ret;
	}

	public function getMonitoringHistory($aid, $rid) {
		$_ = $this->trans;
		$ret = [];
		$ret['title'] = $_('Events history');
		$ret['cols'] = [];
		$ret['cols'][] = array( 'text' => $_('id'), 'class'=> 'sortable');
		$ret['cols'][] = array( 'text' => $_('start'), 'class'=> 'sortable');
		$ret['cols'][] = array( 'text' => $_('end'), 'class'=> 'sortable');
		$ret['cols'][] = array( 'text' => $_('property'), 'class'=> 'sortable');
		$ret['cols'][] = array( 'text' => $_('value'), 'class'=> 'sortable');
		$ret['cols'][] = array( 'text' => $_('rule'), 'class'=> 'sortable');
		$ret['body'] = [];
		$s5  = $this->db->prepare('select e.id, e.start_time, e.end_time, e.property, e.current_value, e.oper, e.value, et.name as event_name 
  from h$res_events e, c$event_types et 
 where e.end_time is not null 
   and e.event_type=et.id
   and host_id=:aid
   and res_id=:rid
 order by start_time desc
 limit 8');
		$s5->bindParam(':aid', $aid, PDO::PARAM_INT);
		$s5->bindParam(':rid', $rid, PDO::PARAM_INT);
		$s5->execute();
		while($r = $s5->fetch()) {
			$ret['body'][] = array(
				'rowProperties'	=> array(
					'color'	=> $this->getEventTextColor($r['event_name']),
					'url'	=> $this->router->pathFor('event', [ 'id' => $r['id']])
				), 'id'	=> array('text'	=> floatval($r['id'])),
				'stime'	=> array('text'	=> $this->formatTimestamp($r['start_time'])),
				'etime'	=> array('text'	=> $r['end_time']!=null?$this->formatTimestamp($r['end_time']):''),
				'prop'	=> array('text'	=> $r['property']),
				'value'	=> array('text'	=> floatval($r['current_value'])),
				'rule'	=> array('text'	=> $r['oper'].intval($r['value']))
			);
		}
		return $ret;
	}

/////////////////////////////////////////////////////////////////////////////////////////////
// WidgetControlers

	public function widgetTableHistory(Request $request, Response $response) {
		$host_id = $request->getAttribute('host_id');
		 $res_id = $request->getAttribute( 'res_id');
		if ($this->getHost($host_id) == false || $this->getRessource($res_id) == false)
			throw new Slim\Exception\NotFoundException($request, $response);
		$this->auth->assertHost($host_id, $request, $response);
		$response->getBody()->write(json_encode($this->getMonitoringHistory($host_id, $res_id)));
		return $response->withHeader('Content-type', 'application/json');
	}
	public function widgetDonutStatus(Request $request, Response $response) {
		$host_id = $request->getAttribute('host_id');
		 $res_id = $request->getAttribute( 'res_id');
		if ($this->getHost($host_id) == false || $this->getRessource($res_id) == false)
			throw new Slim\Exception\NotFoundException($request, $response);
		$this->auth->assertHost($host_id, $request, $response);
		$response->getBody()->write(json_encode($this->getMonitoringStatus($host_id, $res_id)));
		return $response->withHeader('Content-type', 'application/json');
	}
	public function widgetDonutItems(Request $request, Response $response) {
		$host_id = $request->getAttribute('host_id');
		 $res_id = $request->getAttribute( 'res_id');
		if ($this->getHost($host_id) == false || $this->getRessource($res_id) == false)
			throw new Slim\Exception\NotFoundException($request, $response);
		$this->auth->assertHost($host_id, $request, $response);
		$response->getBody()->write(json_encode($this->getMonitoringItems($host_id, $res_id)));
		return $response->withHeader('Content-type', 'application/json');
	}

/////////////////////////////////////////////////////////////////////////////////////////////
// Controlers
	public function ressource (Request $request, Response $response) {
		$aid = $request->getAttribute('aid');
		$rid = $request->getAttribute('rid');
		//$this->logger->addInfo("Ressource $aid - $rid");
		$agent = $this->getHost($aid);
		$res = $this->getRessource($rid);
		if ($agent == false || $res == false)
			throw new Slim\Exception\NotFoundException($request, $response);
		$this->auth->assertHost($aid, $request, $response);

		// Monitoring stuff

		$this->menu->activateHost($agent["host"]);
		$this->menu->breadcrumb = array(
			array('name' => 'host', 'icon' => 'fa fa-server', 'url' => $this->router->pathFor('hosts')), 
			array('name' => $agent['host'], 'url' => $this->router->pathFor('host', array('id' => $aid))), 
			array('name' => 'resources', 'icon' => 'fa fa-area-chart', 'url' => $this->router->pathFor('ressources', array('id' => $aid))),
			array('name' => urldecode($res['name']), 'url' => $this->router->pathFor('ressource', array('aid' => $aid, 'rid' => $rid))));
		return $this->view->render($response, 'hosts/ressource.twig', [ 
			'a'		=> $agent,
			'r'		=> array('id' => $res['id'], 'type' => $res['data_type'], 'name' => urldecode($res['name'])),
			'monit'		=> $this->haveMonitoring($aid, $rid)
		]);
	}
}
