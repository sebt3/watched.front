<?php

class MenuObject {

	public $domains;
	public $breadcrumb;

	public function __construct($app) { 
		$container = $app->getContainer();
		$db = $container->get('db');
		$this->domains = array();
		$stmt = $db->query("SELECT id, name from domains");
		while($row = $stmt->fetch()) {
			$res = array();
			$stm2 = $db->prepare("SELECT host as name, id from agents where domain_id = :id");
			$stm2->bindParam(':id', $row["id"], PDO::PARAM_INT);
			$stm2->execute();
			while($l = $stm2->fetch()) {
				array_push($res, $l);
			}
			if(count($res)>0)
				array_push($this->domains, array("name" => $row["name"], "hosts" => $res));
		}
		$lst=array();
		$stm3 = $db->query("SELECT host as name, id from agents where domain_id is null");
		while($r = $stm3->fetch()) {
			array_push($lst, $r);
		}
		if(count($lst)>0)
			array_unshift($this->domains, array("name" => "unset", "hosts" => $lst));
	}
}


?>
