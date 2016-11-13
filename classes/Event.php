<?php
use Interop\Container\ContainerInterface;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

class Event {

	protected $ci;

	public function __construct(ContainerInterface $ci) { 
		$this->ci = $ci;
	}

	public function events (Request $request, Response $response) {
		$this->ci->view["menu"]->breadcrumb = array(
			array("name" => "events", "icon" => "fa fa-calendar", "url" => $this->ci->router->pathFor('events')));
		return $this->ci->view->render($response, 'events.twig', [ 'e' => $event ]);
	}

	public function event (Request $request, Response $response) {
		$id = $request->getAttribute('id');
		$stmt = $this->ci->db->prepare("select e.id, et.name as event_type, et.id as event_type_id, e.res_id, r.name as res_name, r.type as res_type, e.host_id, a.name as host_name, e.start_time, e.end_time, e.property, e.current_value, e.oper, round(e.value) as value, concat(round(start_time-(1200000)), case when end_time is not null then concat('/',round(end_time+(1200000))) else '' end) as params from res_events e, hosts a, ressources r, event_types et where e.id=:id and e.host_id=a.id and e.res_id=r.id and e.event_type = et.id");
		$stmt->bindParam(':id', $id, PDO::PARAM_INT);
		$stmt->execute();
		$event = $stmt->fetch();
		if ($event == false) {
			return  $response->withStatus(404);
		}
		$date = new DateTime();
		$date->setTimestamp(round($event["start_time"]/1000));
		$event["start_time"] = $date->format('Y-m-d H:i:s');
		if ($event["end_time"] != null) {
			$date->setTimestamp(round($event["end_time"]/1000));
			$event["end_time"] = $date->format('Y-m-d H:i:s');
		}
		$event["res_name"] = urldecode($event["res_name"]);
		$this->ci->view["menu"]->breadcrumb = array(
			array("name" => "events", "icon" => "fa fa-calendar", "url" => $this->ci->router->pathFor('events')), 
			array("name" => $event["id"], "url" => $this->ci->router->pathFor('event', array('id' => $id))));
		return $this->ci->view->render($response, 'event.twig', [ 'e' => $event ]);
	}
}

?>
