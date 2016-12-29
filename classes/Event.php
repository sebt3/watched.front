<?php
use Interop\Container\ContainerInterface;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

class Event extends CorePage {
	public function __construct(ContainerInterface $ci) { 
		parent::__construct($ci);
	}

	public function getEventList() {
		$results = [];
		$stmt = $this->ci->db->query("select e.id, r.id as res_id, h.id as host_id, h.name as host_name, r.name, et.name as type, e.property, e.current_value, e.oper, round(e.value) as value, e.start_time
 from h\$res_events e, c\$ressources r, c\$event_types et, h\$hosts h 
where e.res_id=r.id
  and et.id=e.event_type
  and e.end_time is null
  and h.id = e.host_id
order by et.id asc,e.start_time asc");
		while($row = $stmt->fetch()) {
			$row["start_time"] = $this->formatTimestamp($row["start_time"]);
			$row["color"] = $this->getEventTextColor($row["type"]);
			$row["name"] = urldecode($row["name"]);
			$results[] = $row;
		}
		return $results;
	}
	
	public function getEvent($id) {
		$stmt = $this->ci->db->prepare("select e.id, et.name as event_type, et.id as event_type_id, e.res_id, r.name as res_name, r.data_type as res_type, e.host_id, a.name as host_name, e.start_time, e.end_time, e.property, e.current_value, e.oper, round(e.value) as value, concat(round(start_time-(1200000)), case when end_time is not null then concat('/',round(end_time+(1200000))) else '' end) as params 
  from h\$res_events e, h\$hosts a, c\$ressources r, c\$event_types et 
 where e.id=:id
   and e.host_id=a.id 
   and e.res_id=r.id 
   and e.event_type = et.id");
		$stmt->bindParam(':id', $id, PDO::PARAM_INT);
		$stmt->execute();
		$ret = $stmt->fetch();
		if ($ret == false)
			return $ret;
		$ret["start_time"] = $this->formatTimestamp($ret["start_time"]);
		if ($ret["end_time"] != null)
			$ret["end_time"] = $this->formatTimestamp($ret["end_time"]);
		$ret["color"] = $this->getEventTextColor($ret["event_type"]);
		$ret["res_name"] = urldecode($ret["res_name"]);
		return $ret;
	}

	
/////////////////////////////////////////////////////////////////////////////////////////////
// Pages

	public function events (Request $request, Response $response) {
		$this->ci->view["menu"]->breadcrumb = array(
			array("name" => "events", "icon" => "fa fa-calendar", "url" => $this->ci->router->pathFor('events')));
		return $this->ci->view->render($response, 'events.twig', [ 'events' => $this->getEventList() ]);
	}

	public function event (Request $request, Response $response) {
		$id = $request->getAttribute('id');
		$event = $this->getEvent($id);
		if ($event == false)
			return  $response->withStatus(404);

		$this->ci->view["menu"]->activateHost($event["host_name"]);
		$this->ci->view["menu"]->breadcrumb = array(
			array("name" => "events", "icon" => "fa fa-calendar", "url" => $this->ci->router->pathFor('events')), 
			array("name" => $event["id"], "url" => $this->ci->router->pathFor('event', array('id' => $id))));
		return $this->ci->view->render($response, 'event.twig', [ 'e' => $event ]);
	}
}

?>
