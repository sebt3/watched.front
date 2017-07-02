<?php
use Interop\Container\ContainerInterface;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

class Event extends CorePage {
	public function __construct(ContainerInterface $ci) { 
		parent::__construct($ci);
	}

/////////////////////////////////////////////////////////////////////////////////////////////
// Model
	private function getEventList() {
		$_ = $this->trans;
		$ret = [];
		$ret['cols'] = [];
		$ret['cols'][] = array( 'text' => $_('host name'), 'class'=> 'sortable');
		$ret['cols'][] = array( 'text' => $_('ressource name'), 'class'=> 'sortable');
		$ret['cols'][] = array( 'text' => $_('type'), 'class'=> 'sortable');
		$ret['cols'][] = array( 'text' => $_('property'), 'class'=> 'sortable');
		$ret['cols'][] = array( 'text' => $_('status'), 'class'=> 'sortable');
		$ret['cols'][] = array( 'text' => $_('since'), 'class'=> 'sortable');
		$ret['body'] = [];
		$stmt = $this->db->query('select e.id, r.id as res_id, h.id as host_id, h.name as host_name, r.name, et.name as type, e.property, e.current_value, e.oper, round(e.value) as value, e.start_time
 from h$res_events e, c$ressources r, c$event_types et, h$hosts h 
where e.res_id=r.id
  and et.id=e.event_type
  and e.end_time is null
  and h.id = e.host_id
order by et.id asc,e.start_time asc');
		while($r = $stmt->fetch()) {
			$ret['body'][] = array(
				'rowProperties'	=> array( 'color' => $this->getEventTextColor($r['type'])),
				'hname'	=> array('text'	=> $r['host_name'], 'url' => $this->router->pathFor('host', array('id' => $r['host_id']))),
				'rname'	=> array('text'	=> urldecode($r['name']), 'url' => $this->router->pathFor('ressource', array('rid' => $r['res_id'], 'aid'=> $r['host_id']))),
				'type'	=> array('text'	=> $r['type']),
				'prop'	=> array('text'	=> $r['property'], 'url' => $this->router->pathFor('event', array('id' => $r['id']))),
				'status'=> array('text'	=> $r['current_value']." ".$r['oper']." ".$r['value'], 'url' => $this->router->pathFor('event', array('id' => $r['id']))),
				'start'	=> array('text'	=> $this->formatTimestamp($r['start_time']), 'url' => $this->router->pathFor('event', array('id' => $r['id'])))
			);
		}
		return $ret;
	}

	private function getEvent($id) {
		$stmt = $this->db->prepare('select e.id, et.name as event_type, et.id as event_type_id, e.res_id, r.name as res_name, r.data_type as res_type, e.host_id, a.name as host_name, e.start_time, e.end_time, e.property, e.current_value, e.oper, round(e.value) as value, round(start_time-(1200000)) as min_time, case when end_time is not null then round(end_time+(1200000)) else 0 end as max_time
  from h$res_events e, h$hosts a, c$ressources r, c$event_types et 
 where e.id=:id
   and e.host_id=a.id 
   and e.res_id=r.id 
   and e.event_type = et.id');
		$stmt->bindParam(':id', $id, PDO::PARAM_INT);
		$stmt->execute();
		$ret = $stmt->fetch();
		if ($ret == false)
			return $ret;
		$ret['start_time'] = $this->formatTimestamp($ret['start_time']);
		if ($ret['end_time'] != null)
			$ret['end_time'] = $this->formatTimestamp($ret['end_time']);
		$ret['color'] = $this->getEventTextColor($ret['event_type']);
		$ret['res_name'] = urldecode($ret['res_name']);
		return $ret;
	}

	private function getEventProperty($e) {
		$_ = $this->trans;
		$ret = [];
		$ret['title'] = $_('Event Properties');
		$ret['body'] = [
			array('left' => $_('id'), 'right' => floatval($e['id'])),
			array('left' => $_('type'), 'right' => $e['event_type']),
			array('left' => $_('Host'), 'right' => $e['host_name'], 
				'url' => $this->router->pathFor('host', array('id' => $e['host_id']))),
			array('left' => $_('ressource type'), 'right' => $e['res_type']),
			array('left' => $_('ressource'), 'right' => $e['res_name'], 
				'url' => $this->router->pathFor('ressource', array('aid' => $e['host_id'], 'rid' => $e['res_id']))),
			array('left' => $_('property'), 'right' => $e['property']),
			array('left' => $_('value'), 'right' => floatval($e['current_value'])),
			array('left' => $_('rule'), 'right' => $e['oper'].$e['value']),
			array('left' => $_('start'), 'right' => $e['start_time']),
			array('left' => $_('end'), 'right' => $e['end_time'])
		];
		return $ret;
	}
	
/////////////////////////////////////////////////////////////////////////////////////////////
// WidgetControlers

	public function widgetProperty(Request $request, Response $response) {
		$id = $request->getAttribute('id');
		$e  = $this->getEvent($id);
		if ($e == false)
			throw new Slim\Exception\NotFoundException($request, $response);
		$this->auth->assertHost($e['host_id'], $request, $response);
		$response->getBody()->write(json_encode($this->getEventProperty($e)));
		return $response->withHeader('Content-type', 'application/json');
	}

/////////////////////////////////////////////////////////////////////////////////////////////
// Controlers

	public function events (Request $request, Response $response) {
		$_ = $this->trans;
		$this->menu->breadcrumb = array(
			array('name' => $_('events'), 'icon' => 'fa fa-calendar', 'url' => $this->router->pathFor('events')));
		return $this->view->render($response, 'events/events.twig', [ 'events' => $this->getEventList() ]);
	}

	public function event (Request $request, Response $response) {
		$_ = $this->trans;
		$id = $request->getAttribute('id');
		$event = $this->getEvent($id);
		if ($event == false)
			throw new Slim\Exception\NotFoundException($request, $response);
		$this->auth->assertHost($event['host_id'], $request, $response);

		$this->menu->activateHost($event['host_name']);
		$this->menu->breadcrumb = array(
			array('name' => $_('events'), 'icon' => 'fa fa-calendar', 'url' => $this->router->pathFor('events')), 
			array('name' => $event['id'], 'url' => $this->router->pathFor('event', array('id' => $id))));
		return $this->view->render($response, 'events/event.twig', [ 'e' => $event, 'prop' => $this->getEventProperty($event) ]);
	}
}
