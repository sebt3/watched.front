<?php
namespace Admin;
use \Interop\Container\ContainerInterface;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \PDO as PDO;

class Clean extends \CorePage {
	public function __construct(ContainerInterface $ci) { 
		parent::__construct($ci);
	}

/////////////////////////////////////////////////////////////////////////////////////////////
// Model
	//TODO: add the ability to detect missing agent and clean them
	private function getMissingServices() {
		$results = [];
		$stmt = $this->db->query('select s.id, s.name, h.name as host_name, h.id as host_id, d.id as domain_id, ifnull(d.name, "unset") as domain_name
  from s$missing s, h$hosts h
  left join c$domains d on d.id=h.domain_id
 where s.host_id=h.id
  order by domain_name,host_name,name');
		while($row = $stmt->fetch())
			$results[] = $row;
		return $results;
	}

	private function getMissingHosts() {
		$results = [];
		$stmt = $this->db->query('select s.id, s.name, h.name as host_name, h.id as host_id, d.id as domain_id, ifnull(d.name, "unset") as domain_name
  from s$missing s, h$hosts h
  left join c$domains d on d.id=h.domain_id
 where s.host_id=h.id and 0=1
  order by domain_name,host_name,name'); //TODO: add the ability to detect missing host
		while($row = $stmt->fetch())
			$results[] = $row;
		return $results;
	}

	private function removeService($serv_id) {
		$s = $this->db->prepare('delete from s$services where id=:id');
		$s->bindParam(':id', $serv_id,  PDO::PARAM_INT);
		try {
			$s->execute();
		} catch (Exception $e) {
			$this->logger->addWarning('Clean::removeService('.$e->getMessage().')');
			return false;
		}
		return true;
	}

	private function removeHost($host_id) {
		$s = $this->db->prepare('delete from h$hosts where id=:id');
		$s->bindParam(':id', $host_id,  PDO::PARAM_INT);
		try {
			$s->execute();
		} catch (Exception $e) {
			$this->logger->addWarning('Clean::removeHost('.$e->getMessage().')');
			return false;
		}
		return true;
	}

/////////////////////////////////////////////////////////////////////////////////////////////
// Controlers
	public function listAll($request, $response, $args) {
		$this->menu->breadcrumb = array(
			array('name' => 'admin', 'icon' => 'fa fa-lock', 'url' => $this->router->pathFor('admin')),
			array('name' => 'Clean', 'icon' => 'fa fa-eraser', 'url' => $this->router->pathFor('admin.clean'))); 
		$this->menu->activateAdmin('Clean');
		return $this->view->render($response, 'admin/clean.twig', [ 
			'services'	=> $this->getMissingServices(),
			'hosts'		=> $this->getMissingHosts()
		]);
	}

	public function deleteService($request, $response, $args) {
		$serv_id = $request->getAttribute('id');
		if ($this->removeService($serv_id)) {
			$this->flash->addMessage('success', 'Service successfully deleted.');
			return $response->withRedirect($this->router->pathFor('admin.clean'));
		} else {
			$this->flash->addMessage('error', 'Failed to delete service');
			return $response->withRedirect($this->router->pathFor('admin.clean'));
		}
	}

	public function deleteHost($request, $response, $args) {
		$host_id = $request->getAttribute('id');
		if ($this->removeHost($host_id)) {
			$this->flash->addMessage('success', 'Host successfully deleted.');
			return $response->withRedirect($this->router->pathFor('admin.clean'));
		} else {
			$this->flash->addMessage('error', 'Failed to delete host');
			return $response->withRedirect($this->router->pathFor('admin.clean'));
		}
	}
}

?>

