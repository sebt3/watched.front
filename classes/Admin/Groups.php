<?php
namespace Admin;
use \Interop\Container\ContainerInterface;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \PDO as PDO;

class Groups extends \CorePage {
	public function __construct(ContainerInterface $ci) { 
		parent::__construct($ci);
	}

/////////////////////////////////////////////////////////////////////////////////////////////
// Model
	private function getList() {
		$results = [];
		$stmt = $this->db->query('select id, name from g$groups
order by name asc');
		while($row = $stmt->fetch()) {
			$results[] = $row;
		}
		return $results;
	}

	private function getGroup($groupid) {
		$s = $this->db->prepare('select name from g$groups where id=:id');
		$s->bindParam(':id', $groupid,  PDO::PARAM_INT);
		$s->execute();
		return $s->fetch(); // only one line
	}

	private function addGroup($groupname) {
		$s = $this->db->prepare('insert into g$groups(name) values(:uname)');
		$s->bindParam(':uname', $groupname,  PDO::PARAM_STR);
		try {
			$s->execute();
		} catch (Exception $e) {
			$this->logger->addWarning('addGroup('.$e->getMessage().')');
			return false;
		}
		return true;
	}

	private function changeGroup($group_id, $groupname) {
		$s = $this->db->prepare('update g$groups set name=:uname where id=:id');
		$s->bindParam(':id', $group_id,  PDO::PARAM_INT);
		$s->bindParam(':uname', $groupname,  PDO::PARAM_STR);
		try {
			$s->execute();
		} catch (Exception $e) {
			$this->logger->addWarning('changeGroup('.$e->getMessage().')');
			return false;
		}
		return true;
	}
	
	private function delete($groupid) {
		$s = $this->db->prepare('delete from g$groups where id=:id');
		$s->bindParam(':id', $groupid,  PDO::PARAM_INT);
		try {
			$s->execute();
		} catch (Exception $e) {
			$this->logger->addWarning('Groups::delete('.$e->getMessage().')');
			return false;
		}
		return true;
	}

///////
	private function getTeams($aid) {
		$_ = $this->trans;
		$results = [];
		$s = $this->db->prepare('select d.role_id, r.name as role_name, d.team_id, t.name as team_name, d.alert 
  from p$groups d, p$teams t, p$roles r 
 where    group_id=:id
   and d.team_id=t.id
   and d.role_id=r.id');
		$s->bindParam(':id', $aid,  PDO::PARAM_INT);
		$s->execute();
		while($row = $s->fetch()) {
			if ($row['alert']==1) {
				$row['send']  = $_('yes');
				$row['type']  = $_('alerting');
			} else {
				$row['send']  = $_('no');
				$row['type']  = $_('permission');
			}
			$results[] = $row;
		}
		return $results;
	}

	private function getApps($aid) {
		$results = [];
		$s = $this->db->prepare('select id, name from a$apps where group_id=:id order by name');
		$s->bindParam(':id', $aid,  PDO::PARAM_INT);
		$s->execute();
		while($row = $s->fetch()) {
			$results[] = $row;
		}
		return $results;
	}

	private function getAvailableApps() {
		$results = [];
		$s = $this->db->prepare('select id, name from a$apps where group_id is null order by name');
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

	private function removeApp($group_id, $sid) {
		$s = $this->db->prepare('update a$apps set group_id=null where id=:sid');
		$s->bindParam(':sid', $sid,  PDO::PARAM_INT);
		try {
			$s->execute();
		} catch (Exception $e) {
			$this->logger->addWarning('Groups::removeApp('.$e->getMessage().')');
			return false;
		}
		return true;
	}

	private function updateApp($group_id, $sid) {
		$s = $this->db->prepare('update a$apps set group_id=:aid where id=:sid');
		$s->bindParam(':aid', $group_id,  PDO::PARAM_INT);
		$s->bindParam(':sid', $sid,  PDO::PARAM_INT);
		try {
			$s->execute();
		} catch (Exception $e) {
			$this->logger->addWarning('Groups::updateApp('.$e->getMessage().')');
			return false;
		}
		return true;
	}

	private function addTeamRole($group_id, $tid, $rid, $alert) {
		$sql='insert into p$groups(group_id,team_id,role_id';
		if($alert==1)
			$sql.=',alert) values (:aid,:tid,:rid,1)';
		else
			$sql.=') values (:aid,:tid,:rid)';
		$s = $this->db->prepare($sql);
		$s->bindParam(':aid', $group_id,  PDO::PARAM_INT);
		$s->bindParam(':tid', $tid,  PDO::PARAM_INT);
		$s->bindParam(':rid', $rid,  PDO::PARAM_INT);
		try {
			$s->execute();
		} catch (Exception $e) {
			$this->logger->addWarning('Groups::addTeamRole('.$e->getMessage().')');
			return false;
		}
		return true;
	}

	private function updateTeamRole($group_id, $tid, $rid, $alert) {
		$sql='update p$groups set alert=';
		if($alert==1)
			$sql.='1';
		else
			$sql.='null';
		$sql.=' where group_id=:aid and team_id=:tid and role_id=:rid';
		$s = $this->db->prepare($sql);
		$s->bindParam(':aid', $group_id,  PDO::PARAM_INT);
		$s->bindParam(':tid', $tid,  PDO::PARAM_INT);
		$s->bindParam(':rid', $rid,  PDO::PARAM_INT);
		try {
			$s->execute();
		} catch (Exception $e) {
			$this->logger->addWarning('Groups::addTeamRole('.$e->getMessage().')');
			return false;
		}
		return true;
	}

	private function removeteam($group_id, $team_id, $role_id) {
		$s = $this->db->prepare('delete from p$groups where group_id=:aid and team_id=:tid and role_id=:rid');
		$s->bindParam(':aid', $group_id,  PDO::PARAM_INT);
		$s->bindParam(':tid', $team_id,  PDO::PARAM_INT);
		$s->bindParam(':rid', $role_id,  PDO::PARAM_INT);
		try {
			$s->execute();
		} catch (Exception $e) {
			$this->logger->addWarning('Groups::removeteam('.$e->getMessage().')');
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

	private function getAlert($group_id,$team_id, $role_id) {
		$s = $this->db->prepare('select alert from p$groups where group_id=:aid and team_id=:tid and  role_id=:rid');
		$s->bindParam(':aid', $group_id,  PDO::PARAM_INT);
		$s->bindParam(':tid', $team_id,  PDO::PARAM_INT);
		$s->bindParam(':rid', $role_id,  PDO::PARAM_INT);
		$s->execute();
		$r=$s->fetch(); // only one row
		return $r['alert'];
	}

/////////////////////////////////////////////////////////////////////////////////////////////
// Controlers
	public function listAll($request, $response, $args) {
		$_ = $this->trans;
		$this->menu->breadcrumb = array(
			array('name' => $_('admin'), 'icon' => 'fa fa-lock', 'url' => $this->router->pathFor('admin')), 
			array('name' => $_('groups'), 'icon' => 'fa fa-briefcase', 'url' => $this->router->pathFor('admin.groups.list')));
		$this->menu->activateAdmin('Groups');
		return $this->view->render($response, 'admin/groupList.twig', [ 
			'groups'		=> $this->getList()
		]);
	}

	public function group($request, $response, $args) {
		$_ = $this->trans;
		$group_id = $request->getAttribute('id');
		$u = $this->getGroup($group_id);
		$this->menu->breadcrumb = array(
			array('name' => $_('admin'), 'icon' => 'fa fa-lock', 'url' => $this->router->pathFor('admin')), 
			array('name' => $_('groups'), 'icon' => 'fa fa-briefcase', 'url' => $this->router->pathFor('admin.groups.list')),
			array('name' => $u['name'], 'url' => $this->router->pathFor('admin.groups.change', array('id' => $group_id))));
		$this->menu->activateAdmin('Groups');
		return $this->view->render($response, 'admin/groupChange.twig', [
				'group_id'	=> $group_id,
				'name'		=> $u['name'],
				'teams'		=> $this->getTeams($group_id),
				'apps'	=> $this->getApps($group_id),
			]);
	}

	public function add($request, $response, $args) {
		$_ = $this->trans;
		$this->menu->breadcrumb = array(
			array('name' => $_('admin'), 'icon' => 'fa fa-lock', 'url' => $this->router->pathFor('admin')), 
			array('name' => $_('groups'), 'icon' => 'fa fa-briefcase', 'url' => $this->router->pathFor('admin.groups.list')),
			array('name' => $_('add'), 'icon' => 'fa fa-plus-circle', 'url' => $this->router->pathFor('admin.groups.add')));
		$this->menu->activateAdmin('Groups');
		return $this->view->render($response, 'admin/groupAdd.twig', $args);
	}
//////
	public function addApp($request, $response, $args) {
		$_ = $this->trans;
		$group_id = $request->getAttribute('id');
		$u = $this->getGroup($group_id);
		$hl = $this->getAvailableApps();
		if (count($hl)==0) {
			$this->flash->addMessage('warning', $_('No available app to add.'));
			return $response->withRedirect($this->router->pathFor('admin.groups.change', array('id' => $group_id)));

		}
		$this->menu->breadcrumb = array(
			array('name' => $_('admin'), 'icon' => 'fa fa-lock', 'url' => $this->router->pathFor('admin')), 
			array('name' => $_('groups'), 'icon' => 'fa fa-briefcase', 'url' => $this->router->pathFor('admin.groups.list')),
			array('name' => $u['name'], 'url' => $this->router->pathFor('admin.groups.change', array('id' => $group_id))),
			array('name' => $_('app'), 'icon' => 'fa fa-plus-circle', 'url' => $this->router->pathFor('admin.groups.addApp', array('id' => $group_id))));
		$this->menu->activateAdmin('Groups');
		return $this->view->render($response, 'admin/groupAddApp.twig', [
				'group_id'	=> $group_id,
				'name'		=> $u['name'],
				'apps'	=> $hl
			]);
	}

	public function addTeam($request, $response, $args) {
		$_ = $this->trans;
		$group_id = $request->getAttribute('id');
		$u = $this->getGroup($group_id);
		$this->menu->breadcrumb = array(
			array('name' => $_('admin'), 'icon' => 'fa fa-lock', 'url' => $this->router->pathFor('admin')), 
			array('name' => $_('groups'), 'icon' => 'fa fa-briefcase', 'url' => $this->router->pathFor('admin.groups.list')),
			array('name' => $u['name'], 'url' => $this->router->pathFor('admin.groups.change', array('id' => $group_id))),
			array('name' => $_('team'), 'icon' => 'fa fa-plus-circle', 'url' => $this->router->pathFor('admin.groups.addTeam', array('id' => $group_id))));
		$this->menu->activateAdmin('Groups');
		return $this->view->render($response, 'admin/groupAddTeam.twig', [
				'group_id'	=> $group_id,
				'name'		=> $u['name'],
				'roles'		=> $this->getAllRoles(),
				'teams'		=> $this->getAllTeams()
			]);
	}

	public function team($request, $response, $args) {
		$_ = $this->trans;
		$group_id	= $request->getAttribute('id');
		$team_id	= $request->getAttribute('tid');
		$team		= $this->getTeam($team_id);
		$role_id	= $request->getAttribute('rid');
		$role		= $this->getRole($role_id);
		$alert		= $this->getAlert($group_id,$team_id, $role_id);
		$u = $this->getGroup($group_id);
		$this->menu->breadcrumb = array(
			array('name' => $_('admin'), 'icon' => 'fa fa-lock', 'url' => $this->router->pathFor('admin')), 
			array('name' => $_('groups'), 'icon' => 'fa fa-briefcase', 'url' => $this->router->pathFor('admin.groups.list')),
			array('name' => $u['name'], 'url' => $this->router->pathFor('admin.groups.change', array('id' => $group_id))),
			array('name' => $_('team'), 'icon' => 'icon ion-person-stalker', 'url' => $this->router->pathFor('admin.groups.changeTeam', 
				array('id' => $group_id, 'tid' => $team_id, 'rid' => $role_id))
			)
		);
		$this->menu->activateAdmin('Groups');
		return $this->view->render($response, 'admin/groupChangeTeam.twig', [
				'group_id'	=> $group_id,
				'name'		=> $u['name'],
				'team'		=> $team,
				'role'		=> $role,
				'alert'		=> $alert
			]);
	}
//////
	public function addPost($request, $response, $args) {
		$_ = $this->trans;
		if ($this->addGroup($request->getParam('name'))) {
			$this->flash->addMessage('success', $_('Group added successfully.'));
			return $response->withRedirect($this->router->pathFor('admin.groups.list'));
		} else {
			$this->flash->addMessageNow('warning', 'Failed to add group');
			return $this->add($request, $response, [
				'name'  => $request->getParam('name')
			]);
		}
	}

	public function change($request, $response, $args) {
		$_ = $this->trans;
		$group_id = $request->getAttribute('id');
		if ($this->changeGroup($group_id,$request->getParam('name'))) {
			$this->flash->addMessage('success', $_('Group updated successfully.'));
			return $response->withRedirect($this->router->pathFor('admin.groups.list'));
		} else {
			$this->flash->addMessageNow('warning', 'Failed to update group');
			return $this->group($request, $response, []);
		}
	}

	public function del($request, $response, $args) {
		$_ = $this->trans;
		if ($this->delete($request->getAttribute('id'))) {
			$this->flash->addMessage('success', $_('Group deleted successfully.'));
			return $response->withRedirect($this->router->pathFor('admin.groups.list'));
		} else {
			$this->flash->addMessage('error', $_('Failed to delete group'));
			return $response->withRedirect($this->router->pathFor('admin.groups.list'));
		}
	}

	public function deleteApp($request, $response, $args) {
		$_ = $this->trans;
		$group_id = $request->getAttribute('id');
		if ($this->removeApp($group_id, $request->getAttribute('aid'))) {
			$this->flash->addMessage('success', $_('App removed successfully.'));
			return $response->withRedirect($this->router->pathFor('admin.groups.change', array('id' => $group_id)));
		} else {
			$this->flash->addMessage('error', $_('Failed to remove app'));
			return $response->withRedirect($this->router->pathFor('admin.groups.change', array('id' => $group_id)));
		}
	}

	public function postApp($request, $response, $args) {
		$_ = $this->trans;
		$group_id = $request->getAttribute('id');
		if ($this->updateApp($group_id, $request->getParam('sid'))) {
			$this->flash->addMessage('success', 'App added successfully.');
			return $response->withRedirect($this->router->pathFor('admin.groups.change', array('id' => $group_id)));
		} else {
			$this->flash->addMessage('error', 'Failed to add app');
			return $response->withRedirect($this->router->pathFor('admin.groups.change', array('id' => $group_id)));
		}
	}

	public function postTeam($request, $response, $args) {
		$_ = $this->trans;
		$group_id = $request->getAttribute('id');
		if ($this->addTeamRole($group_id, $request->getParam('tid'), $request->getParam('rid'), $request->getParam('alert'))) {
			$this->flash->addMessage('success', $_('Team added successfully.'));
			return $response->withRedirect($this->router->pathFor('admin.groups.change', array('id' => $group_id)));
		} else {
			$this->flash->addMessage('error', $_('Failed to add team'));
			return $response->withRedirect($this->router->pathFor('admin.groups.addTeam', array('id' => $group_id)));
		}
	}

	public function deleteTeam($request, $response, $args) {
		$_ = $this->trans;
		$group_id = $request->getAttribute('id');
		$team_id = $request->getAttribute('tid');
		$role_id = $request->getAttribute('rid');
		if ($this->removeteam($group_id, $team_id, $role_id)) {
			$this->flash->addMessage('success', $_('Team permission removed successfully.'));
			return $response->withRedirect($this->router->pathFor('admin.groups.change', array('id' => $group_id)));
		} else {
			$this->flash->addMessage('error', $_('Failed to remove team permission'));
			return $response->withRedirect($this->router->pathFor('admin.groups.change', array('id' => $group_id)));
		}
	}

	public function changeTeam($request, $response, $args) {
		$_ = $this->trans;
		$group_id = $request->getAttribute('id');
		$team_id = $request->getAttribute('tid');
		$role_id = $request->getAttribute('rid');
		if ($this->updateTeamRole($group_id, $team_id, $role_id, $request->getParam('alert'))) {
			$this->flash->addMessage('success', $_('Team alerting updated successfully.'));
			return $response->withRedirect($this->router->pathFor('admin.groups.change', array('id' => $group_id)));
		} else {
			$this->flash->addMessage('error', $_('Failed to update team alerting'));
			return $response->withRedirect($this->router->pathFor('admin.groups.changeTeam', array('id' => $group_id, 'tid' => $team_id, 'rid' => $role_id)));
		}
	}

}
