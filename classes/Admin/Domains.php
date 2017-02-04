<?php
use Interop\Container\ContainerInterface;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

class Domains extends CorePage {
	public function __construct(ContainerInterface $ci) { 
		parent::__construct($ci);
	}

/////////////////////////////////////////////////////////////////////////////////////////////
// Model
	private function getList() {
		$results = [];
		$stmt = $this->db->query('select id, name from c$domains
order by name asc');
		while($row = $stmt->fetch()) {
			$results[] = $row;
		}
		return $results;
	}

	private function getDomain($domainid) {
		$s = $this->db->prepare('select name from c$domains where id=:id');
		$s->bindParam(':id', $domainid,  PDO::PARAM_INT);
		$s->execute();
		return $s->fetch(); // only one line
	}

	private function getTeams($domainid) {
		$results = [];
		$s = $this->db->prepare('select d.role_id, r.name as role_name, d.team_id, t.name as team_name, d.alert 
  from p$domains d, p$teams t, p$roles r 
 where domain_id=:id
   and d.team_id=t.id
   and d.role_id=r.id');
		$s->bindParam(':id', $domainid,  PDO::PARAM_INT);
		$s->execute();
		while($row = $s->fetch()) {
			if ($row['alert']==1) {
				$row['send']  = 'yes';
				$row['type']  = 'alerting';
			} else {
				$row['send']  = 'no';
				$row['type']  = 'permission';
			}
			$results[] = $row;
		}
		return $results;
	}

	private function getHosts($domainid) {
		$results = [];
		$s = $this->db->prepare('select id, name from h$hosts where domain_id=:id');
		$s->bindParam(':id', $domainid,  PDO::PARAM_INT);
		$s->execute();
		while($row = $s->fetch()) {
			$results[] = $row;
		}
		return $results;
	}

	private function getAvailableHosts() {
		$results = [];
		$s = $this->db->prepare('select id, name from h$hosts where domain_id is null');
		$s->execute();
		while($row = $s->fetch()) {
			$results[] = $row;
		}
		return $results;
	}

	private function getAllRoles() {
		$results = [];
		$s = $this->db->prepare('select id, name from p$roles');
		$s->execute();
		while($row = $s->fetch()) {
			$results[] = $row;
		}
		return $results;
	}

	private function getAllTeams() {
		$results = [];
		$s = $this->db->prepare('select id, name from p$teams');
		$s->execute();
		while($row = $s->fetch()) {
			$results[] = $row;
		}
		return $results;
	}

	private function addDomain($domainname) {
		$s = $this->db->prepare('insert into c$domains(name) values(:uname)');
		$s->bindParam(':uname', $domainname,  PDO::PARAM_STR);
		try {
			$s->execute();
		} catch (Exception $e) {
			$this->logger->addWarning('addDomain('.$e->getMessage().')');
			return false;
		}
		return true;
	}

	private function changeDomain($domain_id, $domainname) {
		$s = $this->db->prepare('update c$domains set name=:uname where id=:id');
		$s->bindParam(':id', $domain_id,  PDO::PARAM_INT);
		$s->bindParam(':uname', $domainname,  PDO::PARAM_STR);
		try {
			$s->execute();
		} catch (Exception $e) {
			$this->logger->addWarning('changeDomain('.$e->getMessage().')');
			return false;
		}
		return true;
	}
	
	private function delete($domainid) {
		$s = $this->db->prepare('delete from c$domains where id=:id');
		$s->bindParam(':id', $domainid,  PDO::PARAM_INT);
		try {
			$s->execute();
		} catch (Exception $e) {
			$this->logger->addWarning('Domains::delete('.$e->getMessage().')');
			return false;
		}
		return true;
	}

	private function removeHost($domain_id, $hid) {
		$s = $this->db->prepare('update h$hosts set domain_id=null where id=:hid');
		$s->bindParam(':hid', $hid,  PDO::PARAM_INT);
		try {
			$s->execute();
		} catch (Exception $e) {
			$this->logger->addWarning('Domains::removeHost('.$e->getMessage().')');
			return false;
		}
		return true;
	}

	private function updateHost($domain_id, $hid) {
		$s = $this->db->prepare('update h$hosts set domain_id=:id where id=:hid');
		$s->bindParam(':id', $domain_id,  PDO::PARAM_INT);
		$s->bindParam(':hid', $hid,  PDO::PARAM_INT);
		try {
			$s->execute();
		} catch (Exception $e) {
			$this->logger->addWarning('Domains::updateHost('.$e->getMessage().')');
			return false;
		}
		return true;
	}

	private function addTeamRole($domain_id, $tid, $rid, $alert) {
		$sql='insert into p$domains(domain_id,team_id,role_id';
		if($alert==1)
			$sql.=',alert) values (:did,:tid,:rid,1)';
		else
			$sql.=') values (:did,:tid,:rid)';
		$s = $this->db->prepare($sql);
		$s->bindParam(':did', $domain_id,  PDO::PARAM_INT);
		$s->bindParam(':tid', $tid,  PDO::PARAM_INT);
		$s->bindParam(':rid', $rid,  PDO::PARAM_INT);
		try {
			$s->execute();
		} catch (Exception $e) {
			$this->logger->addWarning('Domains::addTeamRole('.$e->getMessage().')');
			return false;
		}
		return true;
	}

	private function updateTeamRole($domain_id, $tid, $rid, $alert) {
		$sql='update p$domains set alert=';
		if($alert==1)
			$sql.='1';
		else
			$sql.='null';
		$sql.=' where domain_id=:did and team_id=:tid and role_id=:rid';
		$s = $this->db->prepare($sql);
		$s->bindParam(':did', $domain_id,  PDO::PARAM_INT);
		$s->bindParam(':tid', $tid,  PDO::PARAM_INT);
		$s->bindParam(':rid', $rid,  PDO::PARAM_INT);
		try {
			$s->execute();
		} catch (Exception $e) {
			$this->logger->addWarning('Domains::addTeamRole('.$e->getMessage().')');
			return false;
		}
		return true;
	}

	private function removeteam($domain_id, $team_id, $role_id) {
		$s = $this->db->prepare('delete from p$domains where domain_id=:did and team_id=:tid and role_id=:rid');
		$s->bindParam(':did', $domain_id,  PDO::PARAM_INT);
		$s->bindParam(':tid', $team_id,  PDO::PARAM_INT);
		$s->bindParam(':rid', $role_id,  PDO::PARAM_INT);
		try {
			$s->execute();
		} catch (Exception $e) {
			$this->logger->addWarning('Domains::removeteam('.$e->getMessage().')');
			return false;
		}
		return true;
	}

	private function getTeam($team_id) {
		$s = $this->db->prepare('select id, name from p$teams where id=:tid');
		$s->bindParam(':tid', $team_id,  PDO::PARAM_INT);
		$s->execute();
		return $s->fetch(); // only one row
	}

	private function getRole($role_id) {
		$s = $this->db->prepare('select id, name from p$roles where id=:rid');
		$s->bindParam(':rid', $role_id,  PDO::PARAM_INT);
		$s->execute();
		return $s->fetch(); // only one row
	}

	private function getAlert($domain_id,$team_id, $role_id) {
		$s = $this->db->prepare('select alert from p$domains where domain_id=:did and team_id=:tid and  role_id=:rid');
		$s->bindParam(':did', $domain_id,  PDO::PARAM_INT);
		$s->bindParam(':tid', $team_id,  PDO::PARAM_INT);
		$s->bindParam(':rid', $role_id,  PDO::PARAM_INT);
		$s->execute();
		$r=$s->fetch(); // only one row
		return $r['alert'];
	}

/////////////////////////////////////////////////////////////////////////////////////////////
// Controlers
	public function listAll($request, $response, $args) {
		$this->menu->breadcrumb = array(
			array('name' => 'admin', 'icon' => 'fa fa-lock', 'url' => $this->router->pathFor('admin')), 
			array('name' => 'domains', 'icon' => 'fa fa-object-group', 'url' => $this->router->pathFor('admin.domains.list')));
		$this->menu->activateAdmin('Domains');
		return $this->view->render($response, 'admin/domainList.twig', [ 
			'domains'		=> $this->getList()
		]);
	}

	public function domain($request, $response, $args) {
		$domain_id = $request->getAttribute('id');
		$u = $this->getDomain($domain_id);
		$this->menu->breadcrumb = array(
			array('name' => 'admin', 'icon' => 'fa fa-lock', 'url' => $this->router->pathFor('admin')), 
			array('name' => 'domains', 'icon' => 'fa fa-object-group', 'url' => $this->router->pathFor('admin.domains.list')),
			array('name' => $u['name'], 'url' => $this->router->pathFor('admin.domains.change', array('id' => $domain_id))));
		$this->menu->activateAdmin('Domains');
		return $this->view->render($response, 'admin/domainChange.twig', [
				'domain_id'	=> $domain_id,
				'name'		=> $u['name'],
				'teams'		=> $this->getTeams($domain_id),
				'hosts'		=> $this->getHosts($domain_id),
			]);
	}

	public function add($request, $response, $args) {
		$this->menu->breadcrumb = array(
			array('name' => 'admin', 'icon' => 'fa fa-lock', 'url' => $this->router->pathFor('admin')), 
			array('name' => 'domains', 'icon' => 'fa fa-object-group', 'url' => $this->router->pathFor('admin.domains.list')),
			array('name' => 'add', 'icon' => 'fa fa-plus-circle', 'url' => $this->router->pathFor('admin.domains.add')));
		$this->menu->activateAdmin('Domains');
		return $this->view->render($response, 'admin/domainAdd.twig', $args);
	}

	public function addHost($request, $response, $args) {
		$domain_id = $request->getAttribute('id');
		$u = $this->getDomain($domain_id);
		$hl = $this->getAvailableHosts();
		if (count($hl)==0) {
			$this->flash->addMessage('warning', 'No available hosts to add.');
			return $response->withRedirect($this->router->pathFor('admin.domains.change', array('id' => $domain_id)));

		}
		$this->menu->breadcrumb = array(
			array('name' => 'admin', 'icon' => 'fa fa-lock', 'url' => $this->router->pathFor('admin')), 
			array('name' => 'domains', 'icon' => 'fa fa-object-group', 'url' => $this->router->pathFor('admin.domains.list')),
			array('name' => $u['name'], 'url' => $this->router->pathFor('admin.domains.change', array('id' => $domain_id))),
			array('name' => 'host', 'icon' => 'fa fa-plus-circle', 'url' => $this->router->pathFor('admin.domains.addHost', array('id' => $domain_id))));
		$this->menu->activateAdmin('Domains');
		return $this->view->render($response, 'admin/domainAddHost.twig', [
				'domain_id'	=> $domain_id,
				'name'		=> $u['name'],
				'hosts'		=> $hl
			]);
	}

	public function addTeam($request, $response, $args) {
		$domain_id = $request->getAttribute('id');
		$u = $this->getDomain($domain_id);
		$this->menu->breadcrumb = array(
			array('name' => 'admin', 'icon' => 'fa fa-lock', 'url' => $this->router->pathFor('admin')), 
			array('name' => 'domains', 'icon' => 'fa fa-object-group', 'url' => $this->router->pathFor('admin.domains.list')),
			array('name' => $u['name'], 'url' => $this->router->pathFor('admin.domains.change', array('id' => $domain_id))),
			array('name' => 'team', 'icon' => 'fa fa-plus-circle', 'url' => $this->router->pathFor('admin.domains.addTeam', array('id' => $domain_id))));
		$this->menu->activateAdmin('Domains');
		return $this->view->render($response, 'admin/domainAddTeam.twig', [
				'domain_id'	=> $domain_id,
				'name'		=> $u['name'],
				'roles'		=> $this->getAllRoles(),
				'teams'		=> $this->getAllTeams()
			]);
	}

	public function team($request, $response, $args) {
		$domain_id	= $request->getAttribute('id');
		$team_id	= $request->getAttribute('tid');
		$team		= $this->getTeam($team_id);
		$role_id	= $request->getAttribute('rid');
		$role		= $this->getRole($role_id);
		$alert		= $this->getAlert($domain_id,$team_id, $role_id);
		$u = $this->getDomain($domain_id);
		$this->menu->breadcrumb = array(
			array('name' => 'admin', 'icon' => 'fa fa-lock', 'url' => $this->router->pathFor('admin')), 
			array('name' => 'domains', 'icon' => 'fa fa-object-group', 'url' => $this->router->pathFor('admin.domains.list')),
			array('name' => $u['name'], 'url' => $this->router->pathFor('admin.domains.change', array('id' => $domain_id))),
			array('name' => 'team', 'icon' => 'icon ion-person-stalker', 'url' => $this->router->pathFor('admin.domains.changeTeam', 
				array('id' => $domain_id, 'tid' => $team_id, 'rid' => $role_id))
			)
		);
		$this->menu->activateAdmin('Domains');
		return $this->view->render($response, 'admin/domainChangeTeam.twig', [
				'domain_id'	=> $domain_id,
				'name'		=> $u['name'],
				'team'		=> $team,
				'role'		=> $role,
				'alert'		=> $alert
			]);
	}

	public function addPost($request, $response, $args) {
		if ($this->addDomain($request->getParam('name'))) {
			$this->flash->addMessage('success', 'Domain added successfully.');
			return $response->withRedirect($this->router->pathFor('admin.domains.list'));
		} else {
			$this->flash->addMessageNow('warning', 'Failed to add domain');
			return $this->add($request, $response, [
				'name'  => $request->getParam('name')
			]);
		}
	}

	public function change($request, $response, $args) {
		$domain_id = $request->getAttribute('id');
		if ($this->changeDomain($domain_id,$request->getParam('name'))) {
			$this->flash->addMessage('success', 'Domain updated successfully.');
			return $response->withRedirect($this->router->pathFor('admin.domains.list'));
		} else {
			$this->flash->addMessageNow('warning', 'Failed to update domain');
			return $this->domain($request, $response, []);
		}
	}

	public function del($request, $response, $args) {
		if ($this->delete($request->getAttribute('id'))) {
			$this->flash->addMessage('success', 'Domain deleted successfully.');
			return $response->withRedirect($this->router->pathFor('admin.domains.list'));
		} else {
			$this->flash->addMessage('error', 'Failed to delete domain');
			return $response->withRedirect($this->router->pathFor('admin.domains.list'));
		}
	}

	public function deleteHost($request, $response, $args) {
		$domain_id = $request->getAttribute('id');
		if ($this->removeHost($domain_id, $request->getAttribute('hid'))) {
			$this->flash->addMessage('success', 'Host removed successfully.');
			return $response->withRedirect($this->router->pathFor('admin.domains.change', array('id' => $domain_id)));
		} else {
			$this->flash->addMessage('error', 'Failed to remove host');
			return $response->withRedirect($this->router->pathFor('admin.domains.change', array('id' => $domain_id)));
		}
	}

	public function postHost($request, $response, $args) {
		$domain_id = $request->getAttribute('id');
		if ($this->updateHost($domain_id, $request->getParam('hid'))) {
			$this->flash->addMessage('success', 'Host added successfully.');
			return $response->withRedirect($this->router->pathFor('admin.domains.change', array('id' => $domain_id)));
		} else {
			$this->flash->addMessage('error', 'Failed to add host');
			return $response->withRedirect($this->router->pathFor('admin.domains.change', array('id' => $domain_id)));
		}
	}

	public function postTeam($request, $response, $args) {
		$domain_id = $request->getAttribute('id');
		if ($this->addTeamRole($domain_id, $request->getParam('tid'), $request->getParam('rid'), $request->getParam('alert'))) {
			$this->flash->addMessage('success', 'Team added successfully.');
			return $response->withRedirect($this->router->pathFor('admin.domains.change', array('id' => $domain_id)));
		} else {
			$this->flash->addMessage('error', 'Failed to add team');
			return $response->withRedirect($this->router->pathFor('admin.domains.addTeam', array('id' => $domain_id)));
		}
	}

	public function deleteTeam($request, $response, $args) {
		$domain_id = $request->getAttribute('id');
		$team_id = $request->getAttribute('tid');
		$role_id = $request->getAttribute('rid');
		if ($this->removeteam($domain_id, $team_id, $role_id)) {
			$this->flash->addMessage('success', 'Team permission removed successfully.');
			return $response->withRedirect($this->router->pathFor('admin.domains.change', array('id' => $domain_id)));
		} else {
			$this->flash->addMessage('error', 'Failed to remove team permission');
			return $response->withRedirect($this->router->pathFor('admin.domains.change', array('id' => $domain_id)));
		}
	}

	public function changeTeam($request, $response, $args) {
		$domain_id = $request->getAttribute('id');
		$team_id = $request->getAttribute('tid');
		$role_id = $request->getAttribute('rid');
		if ($this->updateTeamRole($domain_id, $team_id, $role_id, $request->getParam('alert'))) {
			$this->flash->addMessage('success', 'Team alerting updated successfully.');
			return $response->withRedirect($this->router->pathFor('admin.domains.change', array('id' => $domain_id)));
		} else {
			$this->flash->addMessage('error', 'Failed to update team alerting');
			return $response->withRedirect($this->router->pathFor('admin.domains.changeTeam', array('id' => $domain_id, 'tid' => $team_id, 'rid' => $role_id)));
		}
	}

}

?>

