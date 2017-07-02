<?php
namespace Admin;
use \Interop\Container\ContainerInterface;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \PDO as PDO;

class Teams extends \CorePage {
	public function __construct(ContainerInterface $ci) { 
		parent::__construct($ci);
	}

/////////////////////////////////////////////////////////////////////////////////////////////
// Model
	private function getList() {
		$results = [];
		$stmt = $this->db->query('select t.id,t.name,t.superadmin, ifnull(h.cnt,0) as hosts, ifnull(s.cnt,0) as services
  from p$teams t
left join (
	select team_id, count(distinct host_id) as cnt from p$teams_all_hosts group by team_id
) h on t.id=h.team_id
left join (
	select team_id, count(distinct serv_id) as cnt from p$teams_all_services group by team_id
  ) s  on t.id=s.team_id
order by superadmin desc, hosts desc, services desc, name asc');
		while($row = $stmt->fetch()) {
			$results[] = $row;
		}
		return $results;
	}

	private function getTeam($userid) {
		$s = $this->db->prepare('select name from p$teams where id=:id');
		$s->bindParam(':id', $userid,  PDO::PARAM_INT);
		$s->execute();
		return $s->fetch(); // only one line
	}

	private function addTeam($username) {
		$s = $this->db->prepare('insert into p$teams(name) values(:uname)');
		$s->bindParam(':uname', $username,  PDO::PARAM_STR);
		try {
			$s->execute();
		} catch (Exception $e) {
			$this->logger->addWarning('addTeam('.$e->getMessage().')');
			return false;
		}
		return true;
	}

	private function changeTeam($user_id, $username) {
		$s = $this->db->prepare('update p$teams set name=:uname where id=:id');
		$s->bindParam(':id', $user_id,  PDO::PARAM_INT);
		$s->bindParam(':uname', $username,  PDO::PARAM_STR);
		try {
			$s->execute();
		} catch (Exception $e) {
			$this->logger->addWarning('changeTeam('.$e->getMessage().')');
			return false;
		}
		return true;
	}
	
	private function delete($userid) {
		$s = $this->db->prepare('delete from p$teams where id=:id');
		$s->bindParam(':id', $userid,  PDO::PARAM_INT);
		try {
			$s->execute();
		} catch (Exception $e) {
			$this->logger->addWarning('Teams::delete('.$e->getMessage().')');
			return false;
		}
		return true;
	}

	private function isLastAdmin($user_id, $team_id) {
		$stmt = $this->db->prepare('select count(*) as cnt from p$users_admin where team_id!=:tid and user_id=:uid');
		$stmt->bindParam(':uid', $user_id,  PDO::PARAM_INT);
		$stmt->bindParam(':tid', $team_id,  PDO::PARAM_INT);
		$stmt->execute();
		$r = $stmt->fetch(); // only one line
		return $r['cnt']==0;
	}

/////////////////////////////////////////////////////////////////////////////////////////////
// Controlers
	public function listAll($request, $response, $args) {
		$_ = $this->trans;
		$this->menu->breadcrumb = array(
			array('name' => $_('admin'), 'icon' => 'fa fa-lock', 'url' => $this->router->pathFor('admin')), 
			array('name' => $_('teams'), 'icon' => 'fa fa-users', 'url' => $this->router->pathFor('admin.teams.list')));
		$this->menu->activateAdmin('Teams');
		return $this->view->render($response, 'admin/teamList.twig', [ 
			'teams'		=> $this->getList()
		]);
	}

	public function team($request, $response, $args) {
		$_ = $this->trans;
		$team_id = $request->getAttribute('id');
		$u = $this->getTeam($team_id);
		$this->menu->breadcrumb = array(
			array('name' => $_('admin'), 'icon' => 'fa fa-lock', 'url' => $this->router->pathFor('admin')), 
			array('name' => $_('teams'), 'icon' => 'fa fa-users', 'url' => $this->router->pathFor('admin.teams.list')),
			array('name' => $u['name'], 'url' => $this->router->pathFor('admin.teams.change', array('id' => $team_id))));
		$this->menu->activateAdmin('Teams');
		return $this->view->render($response, 'admin/teamChange.twig', [
				'team_id' => $team_id,
				'name'    => $u['name'],
			]);
	}

	public function add($request, $response, $args) {
		$_ = $this->trans;
		$this->menu->breadcrumb = array(
			array('name' => $_('admin'), 'icon' => 'fa fa-lock', 'url' => $this->router->pathFor('admin')), 
			array('name' => $_('teams'), 'icon' => 'fa fa-users', 'url' => $this->router->pathFor('admin.teams.list')),
			array('name' => 'add', 'icon' => 'fa fa-plus-circle', 'url' => $this->router->pathFor('admin.teams.add')));
		$this->menu->activateAdmin('Teams');
		return $this->view->render($response, 'admin/teamAdd.twig', $args);
	}

	public function addPost($request, $response, $args) {
		$_ = $this->trans;
		if ($this->addTeam($request->getParam('name'))) {
			$this->flash->addMessage('success', $_('Team added successfully.'));
			return $response->withRedirect($this->router->pathFor('admin.teams.list'));
		} else {
			$this->flash->addMessageNow('warning', $_('Failed to add team'));
			return $this->add($request, $response, [
				'name'  => $request->getParam('name')
			]);
		}
	}

	public function change($request, $response, $args) {
		$_ = $this->trans;
		$team_id = $request->getAttribute('id');
		if ($this->changeTeam($team_id,$request->getParam('name'))) {
			$this->flash->addMessage('success', $_('Team updated successfully.'));
			return $response->withRedirect($this->router->pathFor('admin.teams.list'));
		} else {
			$this->flash->addMessageNow('warning', $_('Failed to update team'));
			return $this->team($request, $response, []);
		}
	}

	public function del($request, $response, $args) {
		$_ = $this->trans;
		$team_id = $request->getAttribute('id');
		if($this->isLastAdmin($this->auth->getUserId(), $team_id)) {
			$this->flash->addMessage('error', $_('Cannot delete your last superadmin team'));
			return $response->withRedirect($this->router->pathFor('admin.teams.list'));
		}
		if ($this->delete($team_id)) {
			$this->flash->addMessage('success', $_('Team deleted successfully.'));
			return $response->withRedirect($this->router->pathFor('admin.teams.list'));
		} else {
			$this->flash->addMessage('error', $_('Failed to delete team'));
			return $response->withRedirect($this->router->pathFor('admin.teams.list'));
		}
	}
}
