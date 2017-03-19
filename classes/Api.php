<?php
use Interop\Container\ContainerInterface;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

function haveTable($db, $table) {
	$stmt = $db->prepare('SELECT count(data_type) as cnt from c$data_tables where data_type = :tbid');
	$stmt->bindParam(':tbid', $table);
        $stmt->execute();
        $row = $stmt->fetch();
	return $row["cnt"]+0 >0;
}

class Api extends CorePage {
	public function __construct(ContainerInterface $ci) { 
		parent::__construct($ci);
	}

/////////////////////////////////////////////////////////////////////////////////////////////
// Model
	private function getRessourcesDetailHistory($src, $mint, $maxt) {
		$ret  = [];
		$cols = [];
		$sql  = "";
		$ret['data'] = [];
		$ret['cols'] = [];
		$ret['src']  = $src;

		$ac = "a.timestamp";
		$dc = "d.timestamp";
		$sc = $this->db->prepare('select col.column_name from information_schema.columns col 
 where col.table_schema=database()
   and col.table_name=:tname
   and col.data_type in ("int", "double") 
   and column_name not in (:drive, "res_id", "timestamp")');
		$sc->bindParam(':tname', $src['data_table'], PDO::PARAM_STR);
		$sc->bindParam(':drive', $src['drive'], PDO::PARAM_STR);
		$sc->execute();
		while ($rc = $sc->fetch()) {
			$c	= $rc['column_name'];
			$ret['cols'][] = $c;
			$ac.= ", a.avg_$c, a.min_$c, a.max_$c";
			$dc.= ", d.$c as avg_$c, d.$c as min_$c, d.$c as max_$c";
		}

		if ($src['aggregate_day'] != null && $src['aggregate_hour'] != null)
			$sql .= 'select '.$ac.'
from '.$src['aggregate_day'].' a, (select min(timestamp) as mint from '.$src['aggregate_hour'].' where '.$src['drive'].'=:obj_id and res_id=:res_id) ah
where a.'.$src['drive'].'=:obj_id and a.res_id=:res_id and a.timestamp>ah.mint 
union all ';
		if ($src['aggregate_hour'] != null && $src['aggregate_min'] != null)
			$sql .= 'select '.$ac.'
from '.$src['aggregate_hour'].' a, (select min(timestamp) as mint from '.$src['aggregate_min'].' where '.$src['drive'].'=:obj_id and res_id=:res_id) ah 
where a.'.$src['drive'].'=:obj_id and a.res_id=:res_id and a.timestamp>ah.mint 
union all ';
		if ($src['aggregate_min'] != null)
			$sql .= 'select '.$ac.'
from '.$src['aggregate_min'].' a, (select min(timestamp) as mint from '.$src['data_table'].' where '.$src['drive'].'=:obj_id and res_id=:res_id) ah 
where a.'.$src['drive'].'=:obj_id and a.res_id=:res_id and a.timestamp>ah.mint 
union all ';
		$sql.='select '.$dc.'
from '.$src['data_table'].' d where d.'.$src['drive'].'=:obj_id and d.res_id=:res_id';
		$sd = $this->db->prepare($sql);
		$sd->bindParam(':obj_id', $src['obj_id'], PDO::PARAM_STR);
		$sd->bindParam(':res_id', $src['res_id'], PDO::PARAM_STR);

		// ugly hack here
		ini_set('memory_limit', '512M');
		$sd->execute();
		while ($rd = $sd->fetch()) {
			foreach($rd as $i => $k)
				$rd[$i] = floatval($k);
			$ret['data'][] = $rd;
		}
		return $ret;
	}

	private function getRessourcesMinHistory($src) {
		$ret  = [];
		$cols = [];
		$sql  = "";
		$ret['data'] = [];
		$ret['cols'] = [];
		$ret['src']  = $src;

		$ac = "a.timestamp";
		$dc = "d.timestamp";
		$sc = $this->db->prepare('select col.column_name from information_schema.columns col 
 where col.table_schema=database()
   and col.table_name=:tname
   and col.data_type in ("int", "double") 
   and column_name not in (:drive, "res_id", "timestamp")');
		$sc->bindParam(':tname', $src['data_table'], PDO::PARAM_STR);
		$sc->bindParam(':drive', $src['drive'], PDO::PARAM_STR);
		$sc->execute();
		while ($rc = $sc->fetch()) {
			$c	= $rc['column_name'];
			$ret['cols'][] = $c;
			$ac.= ", a.avg_$c, a.min_$c, a.max_$c";
			$dc.= ", d.$c as avg_$c, d.$c as min_$c, d.$c as max_$c";
		}

		if ($src['aggregate_day'] != null && $src['aggregate_hour'] != null)
			$sql .= 'select '.$ac.'
from '.$src['aggregate_day'].' a, (select min(timestamp) as mint from '.$src['aggregate_hour'].' where '.$src['drive'].'=:obj_id and res_id=:res_id) ah
where a.'.$src['drive'].'=:obj_id and a.res_id=:res_id and a.timestamp<ah.mint 
union all ';
		if ($src['aggregate_hour'] != null && $src['aggregate_min'] != null)
			$sql .= 'select '.$ac.'
from '.$src['aggregate_hour'].' a, (select min(timestamp) as mint from '.$src['aggregate_min'].' where '.$src['drive'].'=:obj_id and res_id=:res_id) ah
where a.'.$src['drive'].'=:obj_id and a.res_id=:res_id and a.timestamp<ah.mint 
union all ';
		if ($src['aggregate_min'] != null)
			$sql .= 'select '.$ac.' from '.$src['aggregate_min'].' a
where a.'.$src['drive'].'=:obj_id and a.res_id=:res_id
union all select '.$dc.'
from '.$src['data_table'].' d, (select max(timestamp) as maxt from '.$src['aggregate_min'].' where '.$src['drive'].'=:obj_id and res_id=:res_id) ah 
where d.'.$src['drive'].'=:obj_id and d.res_id=:res_id and d.timestamp>ah.maxt';
		else
			$sql.='select '.$dc.'
from '.$src['data_table'].' d where d.'.$src['drive'].'=:obj_id and d.res_id=:res_id';
		$sd = $this->db->prepare($sql);
		$sd->bindParam(':obj_id', $src['obj_id'], PDO::PARAM_STR);
		$sd->bindParam(':res_id', $src['res_id'], PDO::PARAM_STR);

		// ugly hack here
		ini_set('memory_limit', '512M');
		$sd->execute();
		while ($rd = $sd->fetch()) {
			foreach($rd as $i => $k)
				$rd[$i] = floatval($k);
			$ret['data'][] = $rd;
		}
		return $ret;
	}

	private function getRessourcesHourHistory($src) {
		$ret  = [];
		$cols = [];
		$sql  = "";
		$ret['data'] = [];
		$ret['cols'] = [];
		$ret['src']  = $src;

		$ac = "a.timestamp";
		$dc = "d.timestamp";
		$sc = $this->db->prepare('select col.column_name from information_schema.columns col 
 where col.table_schema=database()
   and col.table_name=:tname
   and col.data_type in ("int", "double") 
   and column_name not in (:drive, "res_id", "timestamp")');
		$sc->bindParam(':tname', $src['data_table'], PDO::PARAM_STR);
		$sc->bindParam(':drive', $src['drive'], PDO::PARAM_STR);
		$sc->execute();
		while ($rc = $sc->fetch()) {
			$c	= $rc['column_name'];
			$ret['cols'][] = $c;
			$ac.= ", a.avg_$c, a.min_$c, a.max_$c";
			$dc.= ", d.$c as avg_$c, d.$c as min_$c, d.$c as max_$c";
		}

		if ($src['aggregate_day'] != null && $src['aggregate_hour'] != null)
			$sql .= 'select '.$ac.'
from '.$src['aggregate_day'].' a, (select min(timestamp) as mint from '.$src['aggregate_hour'].' where '.$src['drive'].'=:obj_id and res_id=:res_id) ah
where a.'.$src['drive'].'=:obj_id and a.res_id=:res_id and a.timestamp<ah.mint 
union all ';
		if ($src['aggregate_hour'] != null)
			$sql .= 'select '.$ac.' from '.$src['aggregate_hour'].' a
where a.'.$src['drive'].'=:obj_id and a.res_id=:res_id
union all ';
		if ($src['aggregate_min'] != null && $src['aggregate_hour'] != null)
			$sql .= 'select '.$ac.'
from '.$src['aggregate_min'].' a, (select max(timestamp) as maxt from '.$src['aggregate_hour'].' where '.$src['drive'].'=:obj_id and res_id=:res_id) ah 
where a.'.$src['drive'].'=:obj_id and a.res_id=:res_id and a.timestamp>ah.maxt 
union all ';
		if ($src['aggregate_min'] != null)
			$sql.='select '.$dc.'
from '.$src['data_table'].' d, (select max(timestamp) as maxt from '.$src['aggregate_min'].' where '.$src['drive'].'=:obj_id and res_id=:res_id) ah 
where d.'.$src['drive'].'=:obj_id and d.res_id=:res_id and d.timestamp>ah.maxt';
		else
			$sql.='select '.$dc.'
from '.$src['data_table'].' d where d.'.$src['drive'].'=:obj_id and d.res_id=:res_id';
		$sd = $this->db->prepare($sql);
		$sd->bindParam(':obj_id', $src['obj_id'], PDO::PARAM_STR);
		$sd->bindParam(':res_id', $src['res_id'], PDO::PARAM_STR);

		// ugly hack here
		ini_set('memory_limit', '256M');
		$sd->execute();
		while ($rd = $sd->fetch()) {
			foreach($rd as $i => $k)
				$rd[$i] = floatval($k);
			$ret['data'][] = $rd;
		}
		return $ret;
	}

/////////////////////////////////////////////////////////////////////////////////////////////
// Controlers
	public function serv_res($request, $response, $args) {
		$serv_id = $request->getAttribute('serv_id');
		$res_id  = $request->getAttribute('res_id');
		$stmt = $this->db->prepare('select s.id as obj_id, s.name as obj_name, r.id as res_id, r.name as res_name, d.drive, r.data_type, d.data_table, d.aggregate_min, d.aggregate_hour, d.aggregate_day
  from s$ressources sr, s$services s, c$ressources r, c$data_tables d
 where sr.serv_id=s.id and s.id=:sid
   and sr.res_id=r.id  and r.id=:rid
   and d.data_type=r.data_type');
		$stmt->bindParam(':rid', $res_id, PDO::PARAM_INT);
		$stmt->bindParam(':sid', $serv_id, PDO::PARAM_INT);
		$stmt->execute();
		$row = $stmt->fetch();
		if ($row == false || !haveTable($this->db,$row['data_type']))
			throw new Slim\Exception\NotFoundException($request, $response);
		$this->auth->assertService($serv_id, $request, $response);
		$row['res_name'] = urldecode($row['res_name']);
		$ret = $this->getRessourcesHourHistory($row);
		$response->getBody()->write(json_encode($ret));
		return $response->withHeader('Content-type', 'application/json');
	}

	public function host_res($request, $response, $args) {
		$host_id = $request->getAttribute('host_id');
		$res_id  = $request->getAttribute('res_id');
		$stmt = $this->db->prepare('select h.id as obj_id, h.name as obj_name, r.id as res_id, r.name as res_name, d.drive, r.data_type, d.data_table, d.aggregate_min, d.aggregate_hour, d.aggregate_day
  from h$ressources hr, h$hosts h, c$ressources r, c$data_tables d
 where hr.host_id=h.id and h.id=:hid
   and hr.res_id=r.id and r.id=:rid
   and d.data_type=r.data_type');
		$stmt->bindParam(':rid', $res_id, PDO::PARAM_INT);
		$stmt->bindParam(':hid', $host_id, PDO::PARAM_INT);
		$stmt->execute();
		$row = $stmt->fetch();
		if ($row == false || !haveTable($this->db,$row['data_type']))
			throw new Slim\Exception\NotFoundException($request, $response);
		$this->auth->assertHost($host_id, $request, $response);
		$row['res_name'] = urldecode($row['res_name']);
		$ret = $this->getRessourcesHourHistory($row);
		$response->getBody()->write(json_encode($ret));
		return $response->withHeader('Content-type', 'application/json');
	}

/////////////////////////////////////////////////////////////////////////////////////////////
// To deprecate
	public function services($request, $response, $args) {
		$serv_id = $request->getAttribute('id');
		$params  = explode('/', $request->getAttribute('params'));
		$ret     = [];

		$this->auth->assertService($serv_id, $request, $response);
		$sql = "";
		if (isset($params[1]))
			$sql = ' and timestamp >= :mint and timestamp <= :maxt';
		else if (isset($params[0]))
			$sql = ' and timestamp >= :mint';

		$stmt = $this->db->prepare('select timestamp,failed,missing,ok from s$history where serv_id=:id'.$sql);
		$stmt->bindParam(':id', $serv_id, PDO::PARAM_INT);
		if (isset($params[1])) {
			$stmt->bindParam(':mint', $params[0], PDO::PARAM_INT);
			$stmt->bindParam(':maxt', $params[1], PDO::PARAM_INT);
		}
		else if (isset($params[0]))
			$stmt->bindParam(':mint', $params[0], PDO::PARAM_INT);
		$stmt->execute();
		while($row = $stmt->fetch()) {
			$row['timestamp']	= floatval($row['timestamp']);
			$row['failed']		= floatval($row['failed']);
			$row['missing']		= floatval($row['missing']);
			$row['ok']		= floatval($row['ok']);
			$ret[] = $row;
		}


		$response->getBody()->write(json_encode($ret));
		return $response->withHeader('Content-type', 'application/json');
	}

	public function ressources($request, $response, $args) {
		$name    = $request->getAttribute('name');
		$host_id = $request->getAttribute('aid');
		$res_id  = $request->getAttribute('rid');
		$params  = explode('/', $request->getAttribute('params'));
		if (!haveTable($this->ci->db,$name))
			throw new Slim\Exception\NotFoundException($request, $response);
		$sql = "";
		if (isset($params[1]))
			$sql = ' and timestamp >= :mint and timestamp <= :maxt';
		else if (isset($params[0]))
			$sql = ' and timestamp >= :mint';

		$stmt = $this->db->prepare('SELECT count(timestamp) as cnt from ah$'.$name.' where host_id = :aid and res_id = :rid'.$sql);
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
		$prefix = 'd$';
		if ($row['cnt']+0>5)
			$prefix = 'ah$';
		 else if ($row["cnt"]+0>1)
			$prefix = 'am$';

		
		$stmt = $this->db->prepare('SELECT * from '.$prefix.$name." where host_id = :aid and res_id = :rid ".$sql);
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
			unset($row['host_id']);
			unset($row['res_id']);
			foreach($row as $i => $k)
				$row[$i] = floatval($k);
			$ret[] = $row;
		}

		$response->getBody()->write(json_encode($ret));
		return $response->withHeader('Content-type', 'application/json');
	}
}

?>
