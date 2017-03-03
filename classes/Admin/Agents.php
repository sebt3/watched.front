<?php
namespace Admin;
use \Interop\Container\ContainerInterface;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \PDO as PDO;

class Agents extends \CorePage {
	public function __construct(ContainerInterface $ci) { 
		parent::__construct($ci);
	}

/////////////////////////////////////////////////////////////////////////////////////////////
// Model
	private function getList() {
		$results = [];
		$stmt = $this->db->query('select id, host, port, pool_freq, central_id, use_ssl from c$agents
order by central_id desc, host asc, port asc');
		while($row = $stmt->fetch()) {
			if ($row['use_ssl']==1)
				$row['ssl'] = 'yes';
			else
				$row['ssl'] = 'no';
			$results[] = $row;
		}
		return $results;
	}

	private function getAgent($agentid) {
		$s = $this->db->prepare('select host, port, pool_freq, central_id, use_ssl from c$agents where id=:id');
		$s->bindParam(':id', $agentid,  PDO::PARAM_INT);
		$s->execute();
		return $s->fetch(); // only one line
	}

	private function addAgent($agentname, $port, $use_ssl, $freq, $central) {
		$s = $this->db->prepare('insert into c$agents(host, port, use_ssl, pool_freq, central_id) values(:uname, :port, :ssl, :freq, :central)');
		$s->bindParam(':uname', $agentname,	PDO::PARAM_STR);
		$s->bindParam(':port',  $port,		PDO::PARAM_INT);
		$s->bindParam(':ssl',   $use_ssl,	PDO::PARAM_INT);
		$s->bindParam(':freq',  $freq,		PDO::PARAM_INT);
		$s->bindParam(':central',$central,	PDO::PARAM_INT);
		try {
			$s->execute();
		} catch (Exception $e) {
			$this->logger->addWarning('addAgent('.$e->getMessage().')');
			return false;
		}
		return true;
	}

	private function changeAgent($agent_id, $host, $port, $freq, $central, $ssl) {
		$s = $this->db->prepare('update c$agents set host=:uname, port=:port, use_ssl=:ssl, pool_freq=:freq, central_id=:central where id=:id');
		$s->bindParam(':uname',	$host,		PDO::PARAM_STR);
		$s->bindParam(':port',	$port,		PDO::PARAM_INT);
		$s->bindParam(':ssl',	$ssl,		PDO::PARAM_INT);
		$s->bindParam(':freq',	$freq,		PDO::PARAM_INT);
		$s->bindParam(':central', $central,	PDO::PARAM_INT);
		$s->bindParam(':id',	$agent_id,	PDO::PARAM_INT);
		try {
			$s->execute();
		} catch (Exception $e) {
			$this->logger->addWarning('changeAgent('.$e->getMessage().')');
			return false;
		}
		return true;
	}
	
	private function delete($agentid) {
		$s = $this->db->prepare('delete from c$agents where id=:id');
		$s->bindParam(':id', $agentid,  PDO::PARAM_INT);
		try {
			$s->execute();
		} catch (Exception $e) {
			$this->logger->addWarning('Agents::delete('.$e->getMessage().')');
			return false;
		}
		return true;
	}

/////////////////////////////////////////////////////////////////////////////////////////////
// Controlers
	public function listAll($request, $response, $args) {
		$this->menu->breadcrumb = array(
			array('name' => 'admin', 'icon' => 'fa fa-lock', 'url' => $this->router->pathFor('admin')), 
			array('name' => 'agents', 'icon' => 'fa fa-id-badge', 'url' => $this->router->pathFor('admin.agents.list')));
		$this->menu->activateAdmin('Agents');
		return $this->view->render($response, 'admin/agentList.twig', [ 
			'agents'		=> $this->getList()
		]);
	}

	public function agent($request, $response, $args) {
		$agent_id = $request->getAttribute('id');
		$u = $this->getAgent($agent_id);
		$this->menu->breadcrumb = array(
			array('name' => 'admin', 'icon' => 'fa fa-lock', 'url' => $this->router->pathFor('admin')), 
			array('name' => 'agents', 'icon' => 'fa fa-id-badge', 'url' => $this->router->pathFor('admin.agents.list')),
			array('name' => $u['host'].':'.$u['port'], 'url' => $this->router->pathFor('admin.agents.change', array('id'=> $agent_id))));
		$this->menu->activateAdmin('Agents');
		return $this->view->render($response, 'admin/agentChange.twig', [
				'agent_id'=> $agent_id,
				'host'    => $u['host'],
				'port'    => $u['port'],
				'ssl'     => $u['use_ssl'],
				'freq'    => $u['pool_freq'],
				'central' => $u['central_id'],
			]);
	}

	public function add($request, $response, $args) {
		$this->menu->breadcrumb = array(
			array('name' => 'admin', 'icon' => 'fa fa-lock', 'url' => $this->router->pathFor('admin')), 
			array('name' => 'agents', 'icon' => 'fa fa-id-badge', 'url' => $this->router->pathFor('admin.agents.list')),
			array('name' => 'add', 'icon' => 'fa fa-plus-circle', 'url' => $this->router->pathFor('admin.agents.add')));
		$this->menu->activateAdmin('Agents');
		return $this->view->render($response, 'admin/agentAdd.twig', $args);
	}

	public function addPost($request, $response, $args) {
		if ($this->addAgent($request->getParam('host'),$request->getParam('port'),$request->getParam('ssl'), $request->getParam('freq'), $request->getParam('central'))) {
			$this->flash->addMessage('success', 'Agent added successfully.');
			return $response->withRedirect($this->router->pathFor('admin.agents.list'));
		} else {
			$this->flash->addMessageNow('warning', 'Failed to add agent');
			return $this->add($request, $response, [
				'name'  => $request->getParam('name')
			]);
		}
	}

	public function change($request, $response, $args) {
		$agent_id = $request->getAttribute('id');
		if ($this->changeAgent($agent_id,$request->getParam('host'), $request->getParam('port'), $request->getParam('freq'), $request->getParam('central'), $request->getParam('ssl'))) {
			$this->flash->addMessage('success', 'Agent updated successfully.');
			return $response->withRedirect($this->router->pathFor('admin.agents.list'));
		} else {
			$this->flash->addMessageNow('warning', 'Failed to update agent');
			return $this->agent($request, $response, []);
		}
	}

	public function del($request, $response, $args) {
		if ($this->delete($request->getAttribute('id'))) {
			$this->flash->addMessage('success', 'Agent deleted successfully.');
			return $response->withRedirect($this->router->pathFor('admin.agents.list'));
		} else {
			$this->flash->addMessage('error', 'Failed to delete agent');
			return $response->withRedirect($this->router->pathFor('admin.agents.list'));
		}
	}
}

?>
