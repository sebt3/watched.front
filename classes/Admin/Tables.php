<?php
namespace Admin;
use \Interop\Container\ContainerInterface;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \PDO as PDO;

class Tables extends \CorePage {
	public function __construct(ContainerInterface $ci) { 
		parent::__construct($ci);
	}

/////////////////////////////////////////////////////////////////////////////////////////////
// Model
	private function getAllTables() {
		$results = [];
		$stmt = $this->db->query('select * from c$data_sizes');
		while($row = $stmt->fetch()) {
			$tmp = intval($row['res_card']);
			unset($row['res_card']);
			$row['res_card'] = $tmp;
			$results[] = $row;
		}
		return $results;
	}

	private function getSizes($data_type) {
		$results = [];
		$s = $this->db->prepare('select * from c$data_sizes where data_type=:dt');
		$s->bindParam(':dt', $data_type,  PDO::PARAM_STR);
		$s->execute();
		$row = $s->fetch();
		foreach ($row as $key => $value) {
			if ($value==null || $key=='data_type') continue;
			switch ($key) {
			case 'total_size':	$name='Total size (MB)';break;
			case 'data_size':	$name='Details size (MB)';break;
			case 'min_size':	$name='Minuts aggregate size (MB)';break;
			case 'hour_size':	$name='Hours aggregate size (MB)';break;
			case 'day_size':	$name='Days aggregate size (MB)';break;
			case 'host_card':	$name='Host cardinality';break;
			case 'serv_card':	$name='Service cardinality';break;
			case 'res_card':	$name='Ressource cardinality';break;
			case 'data_rows':	$name='Details rows count';break;
			case 'min_rows':	$name='Minuts aggregate rows count';break;
			case 'hour_rows':	$name='Hours aggregate rows count';break;
			case 'days_rows':	$name='Days aggregate rows count';break;
			case 'data_avg':	$name='Average details row size';break;
			case 'min_avg':		$name='Average minuts aggregate row size';break;
			case 'hour_avg':	$name='Average hours aggregate row size';break;
			case 'days_avg':	$name='Average days aggregate row size';break;
			default: $name=$key;break;
			}
			$results[] = array('name' => $name, 'key' => $key, 'value' => floatval($value));
		}
		return $results;
	}

	private function getConfig($data_type) {
		$s = $this->db->prepare('select * from c$data_configs where data_type=:dt');
		$s->bindParam(':dt', $data_type,  PDO::PARAM_STR);
		$s->execute();
		return $s->fetch();
	}

	private function isdefault($data_type) {
		$s = $this->db->prepare('select count(*) as cnt from c$aggregate_config where name=:dt');
		$s->bindParam(':dt', $data_type,  PDO::PARAM_STR);
		$s->execute();
		if( !($r = $s->fetch()) )
			return false;
		return $r['cnt']>0;
	}

	private function upsertConfig($data_type, $delay_am, $delay_ah, $delay_ad, $retention_d, $retention_am, $retention_ah, $retention_ad) {
		$s = $this->db->prepare('insert into c$aggregate_config(name, delay_am, delay_ah, delay_ad, retention_d, retention_am, retention_ah, retention_ad) values(:dt,:dam,:dah,:dad,:rd,:ram,:rah,:rad) on duplicate key update delay_am=:dam, delay_ah=:dah, delay_ad=:dad, retention_d=:rd, retention_am=:ram, retention_ah=:rah, retention_ad=:rad');
		$s->bindParam(':dt', $data_type,     PDO::PARAM_STR);
		$s->bindParam(':dam', $delay_am,     PDO::PARAM_INT);
		$s->bindParam(':dah', $delay_ah,     PDO::PARAM_INT);
		$s->bindParam(':dad', $delay_ad,     PDO::PARAM_INT);
		$s->bindParam(':rd',  $retention_d,  PDO::PARAM_INT);
		$s->bindParam(':ram', $retention_am, PDO::PARAM_INT);
		$s->bindParam(':rah', $retention_ah, PDO::PARAM_INT);
		$s->bindParam(':rad', $retention_ad, PDO::PARAM_INT);
		try {
			$s->execute();
		} catch (Exception $e) {
			$this->logger->addWarning('Tables::upsertConfig('.$e->getMessage().')');
			return false;
		}
		return true;
	}
	private function deleteConfig($data_type) {
		$s = $this->db->prepare('delete from c$aggregate_config where name=:dt');
		$s->bindParam(':dt', $data_type,     PDO::PARAM_STR);
		try {
			$s->execute();
		} catch (Exception $e) {
			$this->logger->addWarning('Tables::deleteConfig('.$e->getMessage().')');
			return false;
		}
		return true;
	}

/////////////////////////////////////////////////////////////////////////////////////////////
// Controlers
	public function postConfig($request, $response, $args) {
		$data_type = $request->getAttribute('name');
		if ($this->upsertConfig($data_type, $request->getParam('delay_am'), $request->getParam('delay_ah'), $request->getParam('delay_ad'), $request->getParam('retention_d'), $request->getParam('retention_am'), $request->getParam('retention_ah'), $request->getParam('retention_ad'))) {
			$this->flash->addMessage('success', 'Configuration updated successfully');
			return $response->withRedirect($this->router->pathFor('admin.tables.edit', array('name' => $data_type)));
		} else {
			$this->flash->addMessage('error', 'Failed to update configuration');
			return $response->withRedirect($this->router->pathFor('admin.tables.edit', array('name' => $data_type)));
		}
	}
	public function removeConfig($request, $response, $args) {
		$data_type = $request->getAttribute('name');
		if ($this->deleteConfig($data_type)) {
			$this->flash->addMessage('success', 'Configuration reverted to default successfully');
			return $response->withRedirect($this->router->pathFor('admin.tables.edit', array('name' => $data_type)));
		} else {
			$this->flash->addMessage('error', 'Failed to update configuration');
			return $response->withRedirect($this->router->pathFor('admin.tables.edit', array('name' => $data_type)));
		}
	}
	public function viewTable($request, $response, $args) {
		$data_type = $request->getAttribute('name');
		$this->menu->breadcrumb = array(
			array('name' => 'admin', 'icon' => 'fa fa-lock', 'url' => $this->router->pathFor('admin')),
			array('name' => 'tables', 'icon' => 'fa fa-table', 'url' => $this->router->pathFor('admin.tables')),
			array('name' => $data_type, 'url' => $this->router->pathFor('admin.tables.edit', array('name' => $data_type))),
			); 
		$this->menu->activateAdmin('Tables');
		return $this->view->render($response, 'admin/tablesView.twig', [ 
			'name'		=> $data_type,
			'sizes'		=> $this->getSizes($data_type),
			'config'	=> $this->getConfig($data_type),
			'isDefault'	=> $this->isDefault($data_type)
		]);
	}

	public function listAll($request, $response, $args) {
		$this->menu->breadcrumb = array(
			array('name' => 'admin', 'icon' => 'fa fa-lock', 'url' => $this->router->pathFor('admin')),
			array('name' => 'tables', 'icon' => 'fa fa-table', 'url' => $this->router->pathFor('admin.tables'))); 
		$this->menu->activateAdmin('Tables');
		return $this->view->render($response, 'admin/tablesList.twig', [ 
			'tables'	=> $this->getAllTables()
		]);
	}
}

?>
