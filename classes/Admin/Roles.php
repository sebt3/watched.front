<?php
use Interop\Container\ContainerInterface;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

class Roles extends CorePage {
	public function __construct(ContainerInterface $ci) { 
		parent::__construct($ci);
	}

/////////////////////////////////////////////////////////////////////////////////////////////
// Model
	private function getList() {
		$results = [];
		$stmt = $this->db->query('select id, name from p$roles
order by name asc');
		while($row = $stmt->fetch()) {
			$results[] = $row;
		}
		return $results;
	}

	private function getRole($roleid) {
		$s = $this->db->prepare('select name from p$roles where id=:id');
		$s->bindParam(':id', $roleid,  PDO::PARAM_INT);
		$s->execute();
		return $s->fetch(); // only one line
	}

	private function addRole($rolename) {
		$s = $this->db->prepare('insert into p$roles(name) values(:uname)');
		$s->bindParam(':uname', $rolename,  PDO::PARAM_STR);
		try {
			$s->execute();
		} catch (Exception $e) {
			$this->logger->addWarning('addRole('.$e->getMessage().')');
			return false;
		}
		return true;
	}

	private function changeRole($role_id, $rolename) {
		$s = $this->db->prepare('update p$roles set name=:uname where id=:id');
		$s->bindParam(':id', $role_id,  PDO::PARAM_INT);
		$s->bindParam(':uname', $rolename,  PDO::PARAM_STR);
		try {
			$s->execute();
		} catch (Exception $e) {
			$this->logger->addWarning('changeRole('.$e->getMessage().')');
			return false;
		}
		return true;
	}
	
	private function delete($roleid) {
		$s = $this->db->prepare('delete from p$roles where id=:id');
		$s->bindParam(':id', $roleid,  PDO::PARAM_INT);
		try {
			$s->execute();
		} catch (Exception $e) {
			$this->logger->addWarning('Roles::delete('.$e->getMessage().')');
			return false;
		}
		return true;
	}

/////////////////////////////////////////////////////////////////////////////////////////////
// Controlers
	public function listAll($request, $response, $args) {
		$this->menu->breadcrumb = array(
			array('name' => 'admin', 'icon' => 'fa fa-lock', 'url' => $this->router->pathFor('admin')), 
			array('name' => 'roles', 'icon' => 'icon ion-ios-body-outline', 'url' => $this->router->pathFor('admin.roles.list')));
		$this->menu->activateAdmin('Roles');
		return $this->view->render($response, 'admin/roleList.twig', [ 
			'roles'		=> $this->getList()
		]);
	}

	public function role($request, $response, $args) {
		$role_id = $request->getAttribute('id');
		$u = $this->getRole($role_id);
		$this->menu->breadcrumb = array(
			array('name' => 'admin', 'icon' => 'fa fa-lock', 'url' => $this->router->pathFor('admin')), 
			array('name' => 'roles', 'icon' => 'icon ion-ios-body-outline', 'url' => $this->router->pathFor('admin.roles.list')),
			array('name' => $u['name'], 'url' => $this->router->pathFor('admin.roles.change', array('id' => $role_id))));
		$this->menu->activateAdmin('Roles');
		return $this->view->render($response, 'admin/roleChange.twig', [
				'role_id' => $role_id,
				'name'    => $u['name'],
			]);
	}

	public function add($request, $response, $args) {
		$this->menu->breadcrumb = array(
			array('name' => 'admin', 'icon' => 'fa fa-lock', 'url' => $this->router->pathFor('admin')), 
			array('name' => 'roles', 'icon' => 'icon ion-ios-body-outline', 'url' => $this->router->pathFor('admin.roles.list')),
			array('name' => 'add', 'icon' => 'fa fa-plus-circle', 'url' => $this->router->pathFor('admin.roles.add')));
		$this->menu->activateAdmin('Roles');
		return $this->view->render($response, 'admin/roleAdd.twig', $args);
	}

	public function addPost($request, $response, $args) {
		if ($this->addRole($request->getParam('name'))) {
			$this->flash->addMessage('success', 'Role '.$request->getParam('name').' added successfully.');
			return $response->withRedirect($this->router->pathFor('admin.roles.list'));
		} else {
			$this->flash->addMessageNow('warning', 'Failed to add role');
			return $this->add($request, $response, [
				'name'  => $request->getParam('name')
			]);
		}
	}

	public function change($request, $response, $args) {
		$role_id = $request->getAttribute('id');
		if ($this->changeRole($role_id,$request->getParam('name'))) {
			$this->flash->addMessage('success', 'Role '.$request->getParam('name').' updated successfully.');
			return $response->withRedirect($this->router->pathFor('admin.roles.list'));
		} else {
			$this->flash->addMessageNow('warning', 'Failed to update role');
			return $this->role($request, $response, []);
		}
	}

	public function del($request, $response, $args) {
		if ($this->delete($request->getAttribute('id'))) {
			$this->flash->addMessage('success', 'Role deleted successfully.');
			return $response->withRedirect($this->router->pathFor('admin.roles.list'));
		} else {
			$this->flash->addMessage('error', 'Failed to delete role');
			return $response->withRedirect($this->router->pathFor('admin.roles.list'));
		}
	}
}

?>
