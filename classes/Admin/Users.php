<?php
use Interop\Container\ContainerInterface;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

class Users extends CorePage {
	public function __construct(ContainerInterface $ci) { 
		parent::__construct($ci);
	}

/////////////////////////////////////////////////////////////////////////////////////////////
// Model
	private function getList() {
		$results = [];
		$stmt = $this->db->query('select id, username, firstname, lastname from u$users');
		while($row = $stmt->fetch()) {
			$results[] = $row;
		}
		return $results;
	}

	private function getUser($userid) {
		$s = $this->db->prepare('select username, firstname, lastname, id from u$users where id=:id');
		$s->bindParam(':id', $userid,  PDO::PARAM_INT);
		$s->execute();
		return $s->fetch(); // only one line
	}

	private function adduser($username, $firstname, $lastname, $password) {
		$s = $this->db->prepare('insert into u$users(username, firstname,lastname,passhash) values(:uname,:fname,:lname,:pass)');
		$s->bindParam(':uname', $username,  PDO::PARAM_STR);
		$s->bindParam(':fname', $firstname, PDO::PARAM_STR);
		$s->bindParam(':lname', $lastname,  PDO::PARAM_STR);
		$s->bindParam(':pass', password_hash($password, PASSWORD_DEFAULT), PDO::PARAM_STR);
		try {
			$s->execute();
		} catch (Exception $e) {
			$this->logger->addWarning('adduser('.$e->getMessage().')');
			return false;
		}
		return true;
	}

	private function changeuser($user_id, $username, $firstname, $lastname, $password) {
		$sql  = 'update u$users set username=:uname, firstname=:fname, lastname=:lname';
		if (isset($password) && $password!="")
			$sql .= ', passhash=:pass' ;
		$sql .= ' where id=:id';
		$s = $this->db->prepare($sql);
		$s->bindParam(':id', $user_id,  PDO::PARAM_INT);
		$s->bindParam(':uname', $username,  PDO::PARAM_STR);
		$s->bindParam(':fname', $firstname, PDO::PARAM_STR);
		$s->bindParam(':lname', $lastname,  PDO::PARAM_STR);
		if (isset($password) && $password!="")
			$s->bindParam(':pass', password_hash($password, PASSWORD_DEFAULT), PDO::PARAM_STR);
		try {
			$s->execute();
		} catch (Exception $e) {
			$this->logger->addWarning('changeuser('.$e->getMessage().')');
			return false;
		}
		return true;
	}
	
	private function delete($userid) {
		$s = $this->db->prepare('delete from u$users where id=:id');
		$s->bindParam(':id', $userid,  PDO::PARAM_INT);
		try {
			$s->execute();
		} catch (Exception $e) {
			$this->logger->addWarning('Users::delete('.$e->getMessage().')');
			return false;
		}
		return true;
	}

	private function getTeams($user_id) {
		$results = [];
		$stmt = $this->db->prepare('select t.id, t.name from u$teams u, p$teams t where u.team_id=t.id and u.user_id=:id');
		$stmt->bindParam(':id', $user_id,  PDO::PARAM_INT);
		$stmt->execute();
		while($row = $stmt->fetch()) {
			$results[] = $row;
		}
		return $results;
	}

	private function getProperties($user_id) {
		$results = [];
		$stmt = $this->db->prepare('select p.id,p.name,u.value from c$properties p, u$properties u where p.id=u.prop_id and u.user_id=:id');
		$stmt->bindParam(':id', $user_id,  PDO::PARAM_INT);
		$stmt->execute();
		while($row = $stmt->fetch()) {
			$results[] = $row;
		}
		return $results;
	}

	private function getProp($prop_id) {
		$stmt = $this->db->prepare('select p.id,p.name from c$properties p where p.id=:id');
		$stmt->bindParam(':id', $prop_id,  PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetch(); // only one line
	}

	private function getPropValue($user_id, $prop_id) {
		$stmt = $this->db->prepare('select value from u$properties where user_id=:id and prop_id=:pid');
		$stmt->bindParam(':id',  $user_id,  PDO::PARAM_INT);
		$stmt->bindParam(':pid', $prop_id,  PDO::PARAM_INT);
		$stmt->execute();
		$r = $stmt->fetch(); // only one line
		return $r['value'];
	}

	private function getAllTeams($user_id) {
		$results = [];
		$s = $this->db->prepare('select id, name from p$teams
 where id not in (select u.team_id from u$teams u where user_id=:id)');
		$s->bindParam(':id', $user_id,  PDO::PARAM_INT);
		$s->execute();
		while($row = $s->fetch()) {
			$results[] = $row;
		}
		return $results;
	}

	private function getAllProp($user_id) {
		$results = [];
		$s = $this->db->prepare('select p.id, p.name from c$properties p
 where p.id not in (select u.prop_id from u$properties u where user_id=:id)');
		$s->bindParam(':id', $user_id,  PDO::PARAM_INT);
		$s->execute();
		while($row = $s->fetch()) {
			$results[] = $row;
		}
		return $results;
	}

	private function addTeamModel($user_id, $tid) {
		$s = $this->db->prepare('insert into u$teams(user_id,team_id) values(:id,:tid)');
		$s->bindParam(':id', $user_id,  PDO::PARAM_INT);
		$s->bindParam(':tid', $tid,  PDO::PARAM_INT);
		try {
			$s->execute();
		} catch (Exception $e) {
			$this->logger->addWarning('Users::addTeamModel('.$e->getMessage().')');
			return false;
		}
		return true;
	}

	private function delTeamModel($user_id, $tid) {
		$s = $this->db->prepare('delete from u$teams where user_id=:id and team_id=:tid');
		$s->bindParam(':id', $user_id,  PDO::PARAM_INT);
		$s->bindParam(':tid', $tid,  PDO::PARAM_INT);
		try {
			$s->execute();
		} catch (Exception $e) {
			$this->logger->addWarning('Users::delTeamModel('.$e->getMessage().')');
			return false;
		}
		return true;
	}

	private function addProperty($user_id, $pid, $value) {
		$s = $this->db->prepare('insert into u$properties(user_id,prop_id,value) values(:id,:pid,:v)');
		$s->bindParam(':id', $user_id,  PDO::PARAM_INT);
		$s->bindParam(':pid', $pid,  PDO::PARAM_INT);
		$s->bindParam(':v', $value,  PDO::PARAM_STR);
		try {
			$s->execute();
		} catch (Exception $e) {
			$this->logger->addWarning('Users::addProperty('.$e->getMessage().')');
			return false;
		}
		return true;
	}

	private function delProperty($user_id, $prop_id) {
		$s = $this->db->prepare('delete from u$properties where user_id=:id and prop_id=:pid');
		$s->bindParam(':id', $user_id,  PDO::PARAM_INT);
		$s->bindParam(':pid', $prop_id, PDO::PARAM_INT);
		try {
			$s->execute();
		} catch (Exception $e) {
			$this->logger->addWarning('Users::delProperty('.$e->getMessage().')');
			return false;
		}
		return true;
	}

	private function updateProperty($user_id, $prop_id, $value) {
		$s = $this->db->prepare('update u$properties set value=:v where user_id=:id and prop_id=:pid');
		$s->bindParam(':id',  $user_id, PDO::PARAM_INT);
		$s->bindParam(':pid', $prop_id, PDO::PARAM_INT);
		$s->bindParam(':v',   $value,   PDO::PARAM_STR);
		try {
			$s->execute();
		} catch (Exception $e) {
			$this->logger->addWarning('Users::updateProperty('.$e->getMessage().')');
			return false;
		}
		return true;
	}

	private function isLastAdmin($user_id, $team_id) {
		$stmt = $this->db->prepare('select count(*) as cnt from p$users_admin where user_id=:id and team_id!=:tid');
		$stmt->bindParam(':id',  $user_id,  PDO::PARAM_INT);
		$stmt->bindParam(':tid', $team_id,  PDO::PARAM_INT);
		$stmt->execute();
		$r = $stmt->fetch(); // only one line
		return $r['cnt']==0;
	}

/////////////////////////////////////////////////////////////////////////////////////////////
// Controlers
	public function listAll($request, $response, $args) {
		$this->menu->breadcrumb = array(
			array('name' => 'admin', 'icon' => 'fa fa-lock', 'url' => $this->router->pathFor('admin')), 
			array('name' => 'users', 'icon' => 'fa fa-user', 'url' => $this->router->pathFor('admin.users.list')));
		$this->menu->activateAdmin('Users');
		return $this->view->render($response, 'admin/userList.twig', [ 
			'users'		=> $this->getList()
		]);
	}

	public function user($request, $response, $args) {
		$user_id = $request->getAttribute('id');
		$u = $this->getUser($user_id);
		$this->menu->breadcrumb = array(
			array('name' => 'admin', 'icon' => 'fa fa-lock', 'url' => $this->router->pathFor('admin')), 
			array('name' => 'users', 'icon' => 'fa fa-user', 'url' => $this->router->pathFor('admin.users.list')),
			array('name' => $u['username'], 'url' => $this->router->pathFor('admin.users.change', array('id' => $user_id))));
		$this->menu->activateAdmin('Users');
		return $this->view->render($response, 'admin/userChange.twig', [
				'user_id'   => $user_id,
				'teams'     => $this->getTeams($user_id),
				'properties'=> $this->getProperties($user_id),
				'username'  => $u['username'],
				'firstname' => $u['firstname'],
				'lastname'  => $u['lastname']
			]);
	}

	public function add($request, $response, $args) {
		$this->menu->breadcrumb = array(
			array('name' => 'admin', 'icon' => 'fa fa-lock', 'url' => $this->router->pathFor('admin')), 
			array('name' => 'users', 'icon' => 'fa fa-user', 'url' => $this->router->pathFor('admin.users.list')),
			array('name' => 'add', 'icon' => 'fa fa-plus-circle', 'url' => $this->router->pathFor('admin.users.add')));
		$this->menu->activateAdmin('Users');
		return $this->view->render($response, 'admin/userAdd.twig', $args);
	}

	public function addTeam($request, $response, $args) {
		$user_id = $request->getAttribute('id');
		$u = $this->getUser($user_id);
		$t = $this->getAllTeams($user_id);
		if (count($t)==0) {
			$this->flash->addMessage('error', 'No other team to add');
			return $response->withRedirect($this->router->pathFor('admin.users.change', array('id' => $user_id)));
		}
		$this->menu->breadcrumb = array(
			array('name' => 'admin', 'icon' => 'fa fa-lock', 'url' => $this->router->pathFor('admin')), 
			array('name' => 'users', 'icon' => 'fa fa-user', 'url' => $this->router->pathFor('admin.users.list')),
			array('name' => $u['username'], 'url' => $this->router->pathFor('admin.users.change', array('id' => $user_id))),
			array('name' => 'team', 'icon' => 'fa fa-plus-circle', 'url' => $this->router->pathFor('admin.users.addTeam', array('id' => $user_id))));
		$this->menu->activateAdmin('Users');
		return $this->view->render($response, 'admin/userAddTeam.twig', [
				'user'		=> $u,
				'teams'		=> $t
			]);
	}

	public function addProp($request, $response, $args) {
		$user_id = $request->getAttribute('id');
		$u = $this->getUser($user_id);
		$p = $this->getAllProp($user_id);
		if (count($p)==0) {
			$this->flash->addMessage('error', 'No other property to add');
			return $response->withRedirect($this->router->pathFor('admin.users.change', array('id' => $user_id)));
		}
		$this->menu->breadcrumb = array(
			array('name' => 'admin', 'icon' => 'fa fa-lock', 'url' => $this->router->pathFor('admin')), 
			array('name' => 'users', 'icon' => 'fa fa-user', 'url' => $this->router->pathFor('admin.users.list')),
			array('name' => $u['username'], 'url' => $this->router->pathFor('admin.users.change', array('id' => $user_id))),
			array('name' => 'property', 'icon' => 'fa fa-plus-circle', 'url' => $this->router->pathFor('admin.users.addProp', array('id' => $user_id))));
		$this->menu->activateAdmin('Users');
		return $this->view->render($response, 'admin/userAddProp.twig', [
				'user'		=> $u,
				'properties'	=> $p
			]);
	}

	public function changeProp($request, $response, $args) {
		$user_id = $request->getAttribute('id');
		$prop_id = $request->getAttribute('pid');
		$prop    = $this->getProp($prop_id);
		$value	 = $this->getPropValue($user_id, $prop_id);
		$u = $this->getUser($user_id);
		$this->menu->breadcrumb = array(
			array('name' => 'admin', 'icon' => 'fa fa-lock', 'url' => $this->router->pathFor('admin')), 
			array('name' => 'users', 'icon' => 'fa fa-user', 'url' => $this->router->pathFor('admin.users.list')),
			array('name' => $u['username'], 'url' => $this->router->pathFor('admin.users.change', array('id' => $user_id))),
			array('name' => $prop['name'], 'icon' => 'fa fa-link', 'url' => $this->router->pathFor('admin.users.changeProp', array('id' => $user_id, 'pid' => $prop_id))));
		$this->menu->activateAdmin('Users');
		return $this->view->render($response, 'admin/userChangeProp.twig', [
				'user'	=> $u,
				'p'	=> $prop,
				'value'	=> $value
			]);
	}

	public function addPost($request, $response, $args) {
		if ($this->adduser($request->getParam('username'), $request->getParam('firstname'), $request->getParam('lastname'), $request->getParam('password'))) {
			$this->flash->addMessage('info', 'User added successfully.');
			return $response->withRedirect($this->router->pathFor('admin.users.list'));
		} else {
			$this->flash->addMessageNow('warning', 'Failed to add user');
			return $this->add($request, $response, [
				'username'  => $request->getParam('username'),
				'firstname' => $request->getParam('firstname'),
				'lastname'  => $request->getParam('lastname'),
				'password'  => $request->getParam('password')
			]);
		}
	}

	public function change($request, $response, $args) {
		$user_id = $request->getAttribute('id');
		if ($this->changeuser($user_id,$request->getParam('username'), $request->getParam('firstname'), $request->getParam('lastname'), $request->getParam('password'))) {
			$this->flash->addMessage('info', 'User updated successfully.');
			return $response->withRedirect($this->router->pathFor('admin.users.list'));
		} else {
			$this->flash->addMessageNow('warning', 'Failed to update user');
			return $this->user($request, $response, []);
		}
	}

	public function del($request, $response, $args) {
		if ($this->delete($request->getAttribute('id'))) {
			$this->flash->addMessage('info', 'User deleted successfully.');
			return $response->withRedirect($this->router->pathFor('admin.users.list'));
		} else {
			$this->flash->addMessage('error', 'Failed to delete user');
			return $response->withRedirect($this->router->pathFor('admin.users.list'));
		}
	}

	public function postTeam($request, $response, $args) {
		$user_id = $request->getAttribute('id');
		if ($this->addTeamModel($user_id, $request->getParam('tid'))) {
			$this->flash->addMessage('success', 'Team added successfully.');
			return $response->withRedirect($this->router->pathFor('admin.users.change', array('id' => $user_id)));
		} else {
			$this->flash->addMessage('error', 'Failed to add team');
			return $response->withRedirect($this->router->pathFor('admin.users.change', array('id' => $user_id)));
		}
	}

	public function deleteTeam($request, $response, $args) {
		$user_id = $request->getAttribute('id');
		$team_id = $request->getAttribute('tid');
		if ($this->auth->getUserId() == $user_id && $this->isLastAdmin($user_id, $team_id)) {
			$this->flash->addMessage('error', 'Cannot remove your own last super admin team');
			return $response->withRedirect($this->router->pathFor('admin.users.change', array('id' => $user_id)));
		}
		if ($this->delTeamModel($user_id, $team_id)) {
			$this->flash->addMessage('success', 'Team removed successfully.');
			return $response->withRedirect($this->router->pathFor('admin.users.change', array('id' => $user_id)));
		} else {
			$this->flash->addMessage('error', 'Failed to remove team');
			return $response->withRedirect($this->router->pathFor('admin.users.change', array('id' => $user_id)));
		}
	}

	public function postProp($request, $response, $args) {
		$user_id = $request->getAttribute('id');
		if ($this->addProperty($user_id, $request->getParam('pid'), $request->getParam('value'))) {
			$this->flash->addMessage('success', 'Property added successfully.');
			return $response->withRedirect($this->router->pathFor('admin.users.change', array('id' => $user_id)));
		} else {
			$this->flash->addMessage('error', 'Failed to add property');
			return $response->withRedirect($this->router->pathFor('admin.users.change', array('id' => $user_id)));
		}
	}

	public function deleteProp($request, $response, $args) {
		$user_id = $request->getAttribute('id');
		$prop_id = $request->getAttribute('pid');
		if ($this->delProperty($user_id, $prop_id)) {
			$this->flash->addMessage('success', 'Property removed successfully.');
			return $response->withRedirect($this->router->pathFor('admin.users.change', array('id' => $user_id)));
		} else {
			$this->flash->addMessage('error', 'Failed to remove property');
			return $response->withRedirect($this->router->pathFor('admin.users.change', array('id' => $user_id)));
		}
	}

	public function updateProp($request, $response, $args) {
		$user_id = $request->getAttribute('id');
		$prop_id = $request->getAttribute('pid');
		$value   = $request->getParam('value');
		if ($this->updateProperty($user_id, $prop_id, $value)) {
			$this->flash->addMessage('success', 'Property updated successfully.');
			return $response->withRedirect($this->router->pathFor('admin.users.change', array('id' => $user_id)));
		} else {
			$this->flash->addMessage('error', 'Failed to update property');
			return $response->withRedirect($this->router->pathFor('admin.users.change', array('id' => $user_id)));
		}
	}

}

?>
