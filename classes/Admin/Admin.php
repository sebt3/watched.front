<?php
use Interop\Container\ContainerInterface;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

class Admin extends CorePage {
	public function __construct(ContainerInterface $ci) { 
		parent::__construct($ci);
	}

/////////////////////////////////////////////////////////////////////////////////////////////
// Model
	private function getGroup() {
		$stmt = $this->db->query('select count(*) as cnt from g$groups');
		$row = $stmt->fetch();
		return $row['cnt'];
	}

	private function getAgent() {
		$stmt = $this->db->query('select count(*) as cnt from c$agents');
		$row = $stmt->fetch();
		return $row['cnt'];
	}

	private function getDomain() {
		$stmt = $this->db->query('select count(*) as cnt from c$domains');
		$row = $stmt->fetch();
		return $row['cnt'];
	}

	private function getApp() {
		$stmt = $this->db->query('select count(*) as cnt from a$apps');
		$row = $stmt->fetch();
		return $row['cnt'];
	}

	private function getClean() {
		$stmt = $this->db->query('select count(*) as cnt from s$missing s');
		$row = $stmt->fetch();
		//TODO: add count for agent and host missing
		return $row['cnt'];
	}

	private function getUser() {
		$stmt = $this->db->query('select count(*) as cnt from u$users');
		$row = $stmt->fetch();
		return $row['cnt'];
	}

	private function getTeam() {
		$stmt = $this->db->query('select count(*) as cnt from p$teams');
		$row = $stmt->fetch();
		return $row['cnt'];
	}

	private function getRole() {
		$stmt = $this->db->query('select count(*) as cnt from p$roles');
		$row = $stmt->fetch();
		return $row['cnt'];
	}

/////////////////////////////////////////////////////////////////////////////////////////////
// Controlers
	public function admin($request, $response, $args) {
		$this->menu->breadcrumb = array(
			array('name' => 'admin', 'icon' => 'fa fa-lock', 'url' => $this->router->pathFor('admin'))); 
		return $this->view->render($response, 'admin/admin.twig', [ 
			'agents'	=> $this->getAgent(),
			'domains'	=> $this->getDomain(),
			'apps'		=> $this->getApp(),
			'clean'		=> $this->getClean(),
			'users'		=> $this->getUser(),
			'teams'		=> $this->getTeam(),
			'groups'	=> $this->getGroup(),
			'roles'		=> $this->getRole(),
		]);
	}
}

?>

