<?php

class connecti
{
	function __construct()
	{
		$this->link = @mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD) or die ("Could not connect");
		if ($this->link) {
			mysqli_select_db($this->link, DB_NAME);
			mysqli_set_charset($this->link, "utf8");
		}
		$this->log_file = LOG_PATH . DB_LOG_FILE_NAME;
	}

	function z_log($qry = '', $res = '', $err = '')
	{
		$del = "\r\n";
		$data = "";
		if (!$res && !$err || is_numeric($res)) {
			$data .= date("d.m.Y H:i:s") . $del;
			$data .= $qry . $del;
		}
		if ($err) {
			$data .= "Error: " . $err . $del;
		} else {
			if ($res) {
				$res = (is_numeric($res)) ? 'ID: ' . $res : $res;
				$data .= $res . $del;
			}
		}
		$date = date("Ymd");
		$log_folder = LOG_PATH . $date;
		if (!file_exists($log_folder)) {
			mkdir($log_folder, 0775, true);
		}
		$log_file = $log_folder . "/" . DB_LOG_FILE_NAME;
		file_put_contents($log_file, $data, FILE_APPEND);
	}

	function mz($sql, $xdb = 1)
	{
		if (!$sql or !$sql > "") return;
		$res = '';
		$error = '';
		$x = mysqli_multi_query($this->link, $sql);

		$iid = self::iid();
		if ($iid) {
			$res = $iid;
		}

		if (!$x) {
			$error = mysqli_error($this->link);
		}
		self::z_log($sql, $res, $error);
		return $x;
	}

	function z($sql, $xdb = 1)
	{
		if (!$sql or !$sql > "") return;
		$res = '';
		$error = '';
		$x = mysqli_query($this->link, $sql);

		$iid = self::iid();
		if ($iid) {
			$res = $iid;
		}

		if (!$x) {
			$error = mysqli_error($this->link);
		}
		self::z_log($sql, $res, $error);
//		print(mysqli_error($this->link) . " in " . $sql);
		return ($res) ? $res : $x;
	}

	function frow(&$res)
	{
		if (!$res) return;
		return mysqli_fetch_row($res);
	}

	function nrows(&$res)
	{
		if (!$res) return;
		return mysqli_num_rows($res);
	}

	function fas(&$res)
	{
		if (!$res) return;
		$fa = mysqli_fetch_assoc($res);
		self::z_log('', "Result: ('" . implode("','", $fa) . "')", '');
		return $fa;
	}

	function fas_all(&$res)
	{
		if (!$res) return;
		$x = array();
		while ($y = mysqli_fetch_assoc($res)) {
			$x[] = $y;
		}
		self::z_log('', 'Result: (' . count($x) . ') rows', '');
		return $x;
	}

	function fas_cell($qry)
	{
		if (!$qry) return;
		$val = '';
		$pat = '/SELECT (.*?) FROM(.*?)/six';
		if (preg_match($pat, $qry, $m)) {
			$sql = self::z($qry);
			$nrows = self::nrows($sql);
			if ($nrows) {
				$res = self::fas($sql);
				$vals = array_values($res);
				$val = $vals[0];
			} else {
				self::z_log('', 'Result: (' . $nrows . ') rows', '');
			}
		}
		return $val;
	}

	function fas_col($qry)
	{
		if (!$qry) return;
		$x = [];
		$pat = '/SELECT (.*?) FROM(.*?)/six';
		if (preg_match($pat, $qry, $m)) {
			$sql = self::z($qry);
			if (self::nrows($sql)) {
				$res = self::fas_all($sql);
				foreach ($res as $v) {
					$k = array_keys($v);
					$x[] = $v[$k[0]];
				}
			}
		}
		return $x;
	}

	function z_arr($qry, $arr, $where = '')
	{
		if (!$qry) return;
		$where = ($where != '') ? " WHERE " . $where : "";
		$q = [];
		foreach ($arr as $k => $v) {
			$q[] = "`$k` = '$v'";
		}
		$q = implode(',', $q);
		$query = $qry . " " . $q . $where;
		self::z($query);
		return self::iid();
	}

	function iid()
	{
		return mysqli_insert_id($this->link);
	}

	function esc($str)
	{
		return mysqli_real_escape_string($this->link, $str);
	}

	function fr()
	{
		$sql = self::z("SELECT FOUND_ROWS() as cnt");
		$res = self::fas($sql);
		return $res["cnt"];
	}

	function get_res($query)
	{
		$sql = self::z($query);
		if (self::nrows($sql)) {
			$res = self::fas_all($sql);
			return $res;
		}
	}

	function get_res_row($query)
	{
		$sql = self::z($query);
		if (self::nrows($sql)) {
			$res = self::fas($sql);
			return $res;
		}
	}
}

?>