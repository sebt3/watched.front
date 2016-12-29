<?php
use Interop\Container\ContainerInterface;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

class Api extends CorePage {
	public function __construct(ContainerInterface $ci) { 
		parent::__construct($ci);
	}
	
	public function services($request, $response, $args) {
		$serv_id = $request->getAttribute('id');
		$params  = explode('/', $request->getAttribute('params'));
		$ret     = [];

		$sql = "";
		if (isset($params[1]))
			$sql = " and timestamp >= :mint and timestamp <= :maxt";
		else if (isset($params[0]))
			$sql = " and timestamp >= :mint";

		$stmt = $this->ci->db->prepare("select timestamp,failed,missing,ok from s\$history where serv_id=:id".$sql);
		$stmt->bindParam(':id', $serv_id, PDO::PARAM_INT);
		if (isset($params[1])) {
			$stmt->bindParam(':mint', $params[0], PDO::PARAM_INT);
			$stmt->bindParam(':maxt', $params[1], PDO::PARAM_INT);
		}
		else if (isset($params[0]))
			$stmt->bindParam(':mint', $params[0], PDO::PARAM_INT);
		$stmt->execute();
		while($row = $stmt->fetch()) {
			$row["timestamp"]	= floatval($row["timestamp"]);
			$row["failed"]		= floatval($row["failed"]);
			$row["missing"]		= floatval($row["missing"]);
			$row["ok"]		= floatval($row["ok"]);
			$ret[] = $row;
		}


		$response->getBody()->write(json_encode($ret));
		return $response;
	}

	public function ressources($request, $response, $args) {
		$name    = $request->getAttribute('name');
		$host_id = $request->getAttribute('aid');
		$res_id  = $request->getAttribute('rid');
		$params  = explode('/', $request->getAttribute('params'));
		if (!haveTable($this->ci->db,$name)) {
			return $response->withStatus(404);
		}
		$sql = "";
		if (isset($params[1]))
			$sql = " and timestamp >= :mint and timestamp <= :maxt";
		else if (isset($params[0]))
			$sql = " and timestamp >= :mint";

		$stmt = $this->ci->db->prepare("SELECT count(timestamp) as cnt from ah\$$name where host_id = :aid and res_id = :rid".$sql);
		$stmt->bindParam(':rid', $res_id, PDO::PARAM_INT);
		$stmt->bindParam(':aid', $host_id, PDO::PARAM_INT);
		if (isset($params[1])) {
			$stmt->bindParam(':mint', $params[0], PDO::PARAM_INT);
			$stmt->bindParam(':maxt', $params[1], PDO::PARAM_INT);
		}
		else if (isset($params[0]))
			$stmt->bindParam(':mint', $params[0], PDO::PARAM_INT);
		$stmt->execute();
		$row = $stmt->fetch();
		$prefix = "d\$";
		/*if ($row["cnt"]+0 <= 0) {
			return $response->withStatus(404);
		} else*/ if ($row["cnt"]+0>5)
			$prefix = "ah\$";
		 else if ($row["cnt"]+0>1)
			$prefix = "am\$";

		
		$stmt = $this->ci->db->prepare("SELECT * from $prefix$name where host_id = :aid and res_id = :rid".$sql);
		$stmt->bindParam(':rid', $res_id, PDO::PARAM_INT);
		$stmt->bindParam(':aid', $host_id, PDO::PARAM_INT);
		if (isset($params[1])) {
			$stmt->bindParam(':mint', $params[0], PDO::PARAM_INT);
			$stmt->bindParam(':maxt', $params[1], PDO::PARAM_INT);
		}
		else if (isset($params[0]))
			$stmt->bindParam(':mint', $params[0], PDO::PARAM_INT);
		$stmt->execute();
		$ret = [];
		while($row = $stmt->fetch()) {
			unset($row["host_id"]);
			unset($row["res_id"]);
			foreach($row as $i => $k)
				$row[$i] = floatval($k);
			$ret[] = $row;
		}

		$response->getBody()->write(json_encode($ret));
		return $response;
	}
}

?>
