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
		$s = $this->db->prepare('select username, firstname, lastname from u$users where id=:id');
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

	public function addPost($request, $response, $args) {
		if ($this->adduser($request->getParam('username'), $request->getParam('firstname'), $request->getParam('lastname'), $request->getParam('password'))) {
			$this->flash->addMessage('info', 'User '.$request->getParam('username').' added successfully.');
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
			$this->flash->addMessage('info', 'User '.$request->getParam('username').' updated successfully.');
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
}

?>
