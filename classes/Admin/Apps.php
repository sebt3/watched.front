<?php
use Interop\Container\ContainerInterface;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

class Apps extends CorePage {
	public function __construct(ContainerInterface $ci) { 
		parent::__construct($ci);
	}

/////////////////////////////////////////////////////////////////////////////////////////////
// Model
	private function getList() {
		$results = [];
		$stmt = $this->db->query('select id, name from a$apps
order by name asc');
		while($row = $stmt->fetch()) {
			$results[] = $row;
		}
		return $results;
	}

	private function getApp($appid) {
		$s = $this->db->prepare('select name from a$apps where id=:id');
		$s->bindParam(':id', $appid,  PDO::PARAM_INT);
		$s->execute();
		return $s->fetch(); // only one line
	}

	private function addApp($appname) {
		$s = $this->db->prepare('insert into a$apps(name) values(:uname)');
		$s->bindParam(':uname', $appname,  PDO::PARAM_STR);
		try {
			$s->execute();
		} catch (Exception $e) {
			$this->logger->addWarning('addApp('.$e->getMessage().')');
			return false;
		}
		return true;
	}

	private function changeApp($app_id, $appname) {
		$s = $this->db->prepare('update a$apps set name=:uname where id=:id');
		$s->bindParam(':id', $app_id,  PDO::PARAM_INT);
		$s->bindParam(':uname', $appname,  PDO::PARAM_STR);
		try {
			$s->execute();
		} catch (Exception $e) {
			$this->logger->addWarning('changeApp('.$e->getMessage().')');
			return false;
		}
		return true;
	}
	
	private function delete($appid) {
		$s = $this->db->prepare('delete from a$apps where id=:id');
		$s->bindParam(':id', $appid,  PDO::PARAM_INT);
		try {
			$s->execute();
		} catch (Exception $e) {
			$this->logger->addWarning('Apps::delete('.$e->getMessage().')');
			return false;
		}
		return true;
	}

///////
	private function getTeams($aid) {
		$results = [];
		$s = $this->db->prepare('select d.role_id, r.name as role_name, d.team_id, t.name as team_name, d.alert 
  from p$apps d, p$teams t, p$roles r 
 where    app_id=:id
   and d.team_id=t.id
   and d.role_id=r.id');
		$s->bindParam(':id', $aid,  PDO::PARAM_INT);
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

	private function getServices($aid) {
		$results = [];
		$s = $this->db->prepare('select s.id, s.name, h.name as host_name, h.id as host_id, d.id as domain_id, ifnull(d.name, "unset") as domain_name
  from s$services s, a$services a, h$hosts h
  left join c$domains d on d.id=h.domain_id
 where s.host_id=h.id
   and s.id=a.serv_id and a.app_id=:id
  order by domain_name,host_name,name');
		$s->bindParam(':id', $aid,  PDO::PARAM_INT);
		$s->execute();
		while($row = $s->fetch()) {
			$results[] = $row;
		}
		return $results;
	}

	private function getAvailableServices() {
		$results = [];
		$s = $this->db->prepare('select s.id, s.name, h.name as host_name, h.id as host_id, d.id as domain_id, ifnull(d.name, "unset") as domain_name
  from s$services s, h$hosts h
  left join c$domains d on d.id=h.domain_id
 where s.host_id=h.id
 order by domain_name,host_name,name'); //TODO: do a wizard to select Domain and type
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

	private function removeService($app_id, $sid) {
		$s = $this->db->prepare('delete from a$services where app_id=:aid and serv_id=:sid');
		$s->bindParam(':aid', $app_id,  PDO::PARAM_INT);
		$s->bindParam(':sid', $sid,  PDO::PARAM_INT);
		try {
			$s->execute();
		} catch (Exception $e) {
			$this->logger->addWarning('Apps::removeService('.$e->getMessage().')');
			return false;
		}
		return true;
	}

	private function updateService($app_id, $sid) {
		$s = $this->db->prepare('insert into a$services(app_id,serv_id) values(:aid,:sid)');
		$s->bindParam(':aid', $app_id,  PDO::PARAM_INT);
		$s->bindParam(':sid', $sid,  PDO::PARAM_INT);
		try {
			$s->execute();
		} catch (Exception $e) {
			$this->logger->addWarning('Apps::updateService('.$e->getMessage().')');
			return false;
		}
		return true;
	}

	private function addTeamRole($app_id, $tid, $rid, $alert) {
		$sql='insert into p$apps(app_id,team_id,role_id';
		if($alert==1)
			$sql.=',alert) values (:aid,:tid,:rid,1)';
		else
			$sql.=') values (:aid,:tid,:rid)';
		$s = $this->db->prepare($sql);
		$s->bindParam(':aid', $app_id,  PDO::PARAM_INT);
		$s->bindParam(':tid', $tid,  PDO::PARAM_INT);
		$s->bindParam(':rid', $rid,  PDO::PARAM_INT);
		try {
			$s->execute();
		} catch (Exception $e) {
			$this->logger->addWarning('Apps::addTeamRole('.$e->getMessage().')');
			return false;
		}
		return true;
	}

	private function updateTeamRole($app_id, $tid, $rid, $alert) {
		$sql='update p$apps set alert=';
		if($alert==1)
			$sql.='1';
		else
			$sql.='null';
		$sql.=' where app_id=:aid and team_id=:tid and role_id=:rid';
		$s = $this->db->prepare($sql);
		$s->bindParam(':aid', $app_id,  PDO::PARAM_INT);
		$s->bindParam(':tid', $tid,  PDO::PARAM_INT);
		$s->bindParam(':rid', $rid,  PDO::PARAM_INT);
		try {
			$s->execute();
		} catch (Exception $e) {
			$this->logger->addWarning('Apps::addTeamRole('.$e->getMessage().')');
			return false;
		}
		return true;
	}

	private function removeteam($app_id, $team_id, $role_id) {
		$s = $this->db->prepare('delete from p$apps where app_id=:aid and team_id=:tid and role_id=:rid');
		$s->bindParam(':aid', $app_id,  PDO::PARAM_INT);
		$s->bindParam(':tid', $team_id,  PDO::PARAM_INT);
		$s->bindParam(':rid', $role_id,  PDO::PARAM_INT);
		try {
			$s->execute();
		} catch (Exception $e) {
			$this->logger->addWarning('Apps::removeteam('.$e->getMessage().')');
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

	private function getAlert($app_id,$team_id, $role_id) {
		$s = $this->db->prepare('select alert from p$apps where app_id=:aid and team_id=:tid and  role_id=:rid');
		$s->bindParam(':aid', $app_id,  PDO::PARAM_INT);
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
			array('name' => 'apps', 'icon' => 'fa fa-rocket', 'url' => $this->router->pathFor('admin.apps.list')));
		$this->menu->activateAdmin('Apps');
		return $this->view->render($response, 'admin/appList.twig', [ 
			'apps'		=> $this->getList()
		]);
	}

	public function app($request, $response, $args) {
		$app_id = $request->getAttribute('id');
		$u = $this->getApp($app_id);
		$this->menu->breadcrumb = array(
			array('name' => 'admin', 'icon' => 'fa fa-lock', 'url' => $this->router->pathFor('admin')), 
			array('name' => 'apps', 'icon' => 'fa fa-rocket', 'url' => $this->router->pathFor('admin.apps.list')),
			array('name' => $u['name'], 'url' => $this->router->pathFor('admin.apps.change', array('id' => $app_id))));
		$this->menu->activateAdmin('Apps');
		return $this->view->render($response, 'admin/appChange.twig', [
				'app_id'	=> $app_id,
				'name'		=> $u['name'],
				'teams'		=> $this->getTeams($app_id),
				'services'	=> $this->getServices($app_id),
			]);
	}

	public function add($request, $response, $args) {
		$this->menu->breadcrumb = array(
			array('name' => 'admin', 'icon' => 'fa fa-lock', 'url' => $this->router->pathFor('admin')), 
			array('name' => 'apps', 'icon' => 'fa fa-rocket', 'url' => $this->router->pathFor('admin.apps.list')),
			array('name' => 'add', 'icon' => 'fa fa-plus-circle', 'url' => $this->router->pathFor('admin.apps.add')));
		$this->menu->activateAdmin('Apps');
		return $this->view->render($response, 'admin/appAdd.twig', $args);
	}
//////
	public function addService($request, $response, $args) {
		$app_id = $request->getAttribute('id');
		$u = $this->getApp($app_id);
		$hl = $this->getAvailableServices();
		if (count($hl)==0) {
			$this->flash->addMessage('warning', 'No available service to add.');
			return $response->withRedirect($this->router->pathFor('admin.apps.change', array('id' => $app_id)));

		}
		$this->menu->breadcrumb = array(
			array('name' => 'admin', 'icon' => 'fa fa-lock', 'url' => $this->router->pathFor('admin')), 
			array('name' => 'apps', 'icon' => 'fa fa-rocket', 'url' => $this->router->pathFor('admin.apps.list')),
			array('name' => $u['name'], 'url' => $this->router->pathFor('admin.apps.change', array('id' => $app_id))),
			array('name' => 'service', 'icon' => 'fa fa-plus-circle', 'url' => $this->router->pathFor('admin.apps.addService', array('id' => $app_id))));
		$this->menu->activateAdmin('Apps');
		return $this->view->render($response, 'admin/appAddService.twig', [
				'app_id'	=> $app_id,
				'name'		=> $u['name'],
				'services'	=> $hl
			]);
	}

	public function addTeam($request, $response, $args) {
		$app_id = $request->getAttribute('id');
		$u = $this->getApp($app_id);
		$this->menu->breadcrumb = array(
			array('name' => 'admin', 'icon' => 'fa fa-lock', 'url' => $this->router->pathFor('admin')), 
			array('name' => 'apps', 'icon' => 'fa fa-rocket', 'url' => $this->router->pathFor('admin.apps.list')),
			array('name' => $u['name'], 'url' => $this->router->pathFor('admin.apps.change', array('id' => $app_id))),
			array('name' => 'team', 'icon' => 'fa fa-plus-circle', 'url' => $this->router->pathFor('admin.apps.addTeam', array('id' => $app_id))));
		$this->menu->activateAdmin('Apps');
		return $this->view->render($response, 'admin/appAddTeam.twig', [
				'app_id'	=> $app_id,
				'name'		=> $u['name'],
				'roles'		=> $this->getAllRoles(),
				'teams'		=> $this->getAllTeams()
			]);
	}

	public function team($request, $response, $args) {
		$app_id	= $request->getAttribute('id');
		$team_id	= $request->getAttribute('tid');
		$team		= $this->getTeam($team_id);
		$role_id	= $request->getAttribute('rid');
		$role		= $this->getRole($role_id);
		$alert		= $this->getAlert($app_id,$team_id, $role_id);
		$u = $this->getApp($app_id);
		$this->menu->breadcrumb = array(
			array('name' => 'admin', 'icon' => 'fa fa-lock', 'url' => $this->router->pathFor('admin')), 
			array('name' => 'apps', 'icon' => 'fa fa-rocket', 'url' => $this->router->pathFor('admin.apps.list')),
			array('name' => $u['name'], 'url' => $this->router->pathFor('admin.apps.change', array('id' => $app_id))),
			array('name' => 'team', 'icon' => 'icon ion-person-stalker', 'url' => $this->router->pathFor('admin.apps.changeTeam', 
				array('id' => $app_id, 'tid' => $team_id, 'rid' => $role_id))
			)
		);
		$this->menu->activateAdmin('Apps');
		return $this->view->render($response, 'admin/appChangeTeam.twig', [
				'app_id'	=> $app_id,
				'name'		=> $u['name'],
				'team'		=> $team,
				'role'		=> $role,
				'alert'		=> $alert
			]);
	}
//////
	public function addPost($request, $response, $args) {
		if ($this->addApp($request->getParam('name'))) {
			$this->flash->addMessage('success', 'App '.$request->getParam('name').' added successfully.');
			return $response->withRedirect($this->router->pathFor('admin.apps.list'));
		} else {
			$this->flash->addMessageNow('warning', 'Failed to add app');
			return $this->add($request, $response, [
				'name'  => $request->getParam('name')
			]);
		}
	}

	public function change($request, $response, $args) {
		$app_id = $request->getAttribute('id');
		if ($this->changeApp($app_id,$request->getParam('name'))) {
			$this->flash->addMessage('success', 'App '.$request->getParam('name').' updated successfully.');
			return $response->withRedirect($this->router->pathFor('admin.apps.list'));
		} else {
			$this->flash->addMessageNow('warning', 'Failed to update app');
			return $this->app($request, $response, []);
		}
	}

	public function del($request, $response, $args) {
		if ($this->delete($request->getAttribute('id'))) {
			$this->flash->addMessage('success', 'App deleted successfully.');
			return $response->withRedirect($this->router->pathFor('admin.apps.list'));
		} else {
			$this->flash->addMessage('error', 'Failed to delete app');
			return $response->withRedirect($this->router->pathFor('admin.apps.list'));
		}
	}

	public function deleteService($request, $response, $args) {
		$app_id = $request->getAttribute('id');
		if ($this->removeService($app_id, $request->getAttribute('sid'))) {
			$this->flash->addMessage('success', 'Service removed successfully.');
			return $response->withRedirect($this->router->pathFor('admin.apps.change', array('id' => $app_id)));
		} else {
			$this->flash->addMessage('error', 'Failed to remove service');
			return $response->withRedirect($this->router->pathFor('admin.apps.change', array('id' => $app_id)));
		}
	}

	public function postService($request, $response, $args) {
		$app_id = $request->getAttribute('id');
		if ($this->updateService($app_id, $request->getParam('sid'))) {
			$this->flash->addMessage('success', 'Service added successfully.');
			return $response->withRedirect($this->router->pathFor('admin.apps.change', array('id' => $app_id)));
		} else {
			$this->flash->addMessage('error', 'Failed to add service');
			return $response->withRedirect($this->router->pathFor('admin.apps.change', array('id' => $app_id)));
		}
	}

	public function postTeam($request, $response, $args) {
		$app_id = $request->getAttribute('id');
		if ($this->addTeamRole($app_id, $request->getParam('tid'), $request->getParam('rid'), $request->getParam('alert'))) {
			$this->flash->addMessage('success', 'Team added successfully.');
			return $response->withRedirect($this->router->pathFor('admin.apps.change', array('id' => $app_id)));
		} else {
			$this->flash->addMessage('error', 'Failed to add team');
			return $response->withRedirect($this->router->pathFor('admin.apps.addTeam', array('id' => $app_id)));
		}
	}

	public function deleteTeam($request, $response, $args) {
		$app_id = $request->getAttribute('id');
		$team_id = $request->getAttribute('tid');
		$role_id = $request->getAttribute('rid');
		if ($this->removeteam($app_id, $team_id, $role_id)) {
			$this->flash->addMessage('success', 'Team permission removed successfully.');
			return $response->withRedirect($this->router->pathFor('admin.apps.change', array('id' => $app_id)));
		} else {
			$this->flash->addMessage('error', 'Failed to remove team permission');
			return $response->withRedirect($this->router->pathFor('admin.apps.change', array('id' => $app_id)));
		}
	}

	public function changeTeam($request, $response, $args) {
		$app_id = $request->getAttribute('id');
		$team_id = $request->getAttribute('tid');
		$role_id = $request->getAttribute('rid');
		if ($this->updateTeamRole($app_id, $team_id, $role_id, $request->getParam('alert'))) {
			$this->flash->addMessage('success', 'Team alerting updated successfully.');
			return $response->withRedirect($this->router->pathFor('admin.apps.change', array('id' => $app_id)));
		} else {
			$this->flash->addMessage('error', 'Failed to update team alerting');
			return $response->withRedirect($this->router->pathFor('admin.apps.changeTeam', array('id' => $app_id, 'tid' => $team_id, 'rid' => $role_id)));
		}
	}

}

?>

