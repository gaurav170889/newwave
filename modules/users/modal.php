<?php
/* Modulename_modal */
Class Users_modal{
	public function __construct()
	{
		$this->conn = ConnectDB();
	}

	public function htmlvalidation($form_data){
		$form_data = trim(stripslashes(htmlspecialchars($form_data)));
		$form_data = mysqli_real_escape_string($this->conn, trim(strip_tags($form_data)));
		return $form_data;
	}

	private function hasColumn($table, $column)
	{
		$table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
		$column = preg_replace('/[^a-zA-Z0-9_]/', '', $column);
		$result = mysqli_query($this->conn, "SHOW COLUMNS FROM `$table` LIKE '$column'");
		return ($result && mysqli_num_rows($result) > 0);
	}

	private function resolveUserColumn($primary, $fallback = null)
	{
		if ($this->hasColumn('users', $primary)) {
			return $primary;
		}
		if ($fallback !== null && $this->hasColumn('users', $fallback)) {
			return $fallback;
		}
		return null;
	}

	private function mapUserField($fieldName)
	{
		$mapping = [
			'email' => $this->resolveUserColumn('user_email', 'email'),
			'role' => $this->resolveUserColumn('user_type', 'role'),
			'password' => $this->resolveUserColumn('password_hash', 'password'),
			'agentid' => $this->resolveUserColumn('agentid', null),
		];

		return $mapping[$fieldName] ?? $fieldName;
	}

	private function buildUserSelectSql($companyId = null, $isSuperAdmin = false, $currentUserRole = '')
	{
		$emailColumn = $this->resolveUserColumn('user_email', 'email');
		$roleColumn = $this->resolveUserColumn('user_type', 'role');
		$passwordColumn = $this->resolveUserColumn('password_hash', 'password');
		$agentIdColumn = $this->resolveUserColumn('agentid', null);
		$companyColumn = $this->resolveUserColumn('company_id', null);

		$emailExpr = $emailColumn ? "u.`{$emailColumn}`" : "''";
		$roleExpr = $roleColumn ? "u.`{$roleColumn}`" : "''";
		$passwordExpr = $passwordColumn ? "u.`{$passwordColumn}`" : "''";
		$agentExpr = $agentIdColumn ? "u.`{$agentIdColumn}`" : "NULL";
		$companyExpr = $companyColumn ? "u.`{$companyColumn}`" : "NULL";

		$sql = "SELECT u.id, {$emailExpr} AS email, {$roleExpr} AS role, {$passwordExpr} AS password, {$agentExpr} AS agentid, {$companyExpr} AS company_id FROM `users` u";
		$where = [];

		if (!$isSuperAdmin && $companyColumn && $companyId !== null && intval($companyId) > 0) {
			$where[] = "u.`{$companyColumn}` = " . intval($companyId);
		}

		if ($roleColumn && $currentUserRole !== 'super_admin') {
			$where[] = "LOWER(COALESCE(u.`{$roleColumn}`, '')) <> 'super_admin'";
		}

		if (!empty($where)) {
			$sql .= " WHERE " . implode(' AND ', $where);
		}

		$sql .= " ORDER BY u.id DESC";
		return $sql;
	}

	private function buildConditionSql($condition, $op = 'AND', $tableName = 'users')
	{
		$parts = [];
		foreach ($condition as $key => $value) {
			$column = ($tableName === 'users') ? $this->mapUserField($key) : $key;
			if ($column === null) {
				continue;
			}
			$safeColumn = preg_replace('/[^a-zA-Z0-9_]/', '', $column);
			$safeValue = mysqli_real_escape_string($this->conn, (string) $value);
			$parts[] = "`{$safeColumn}`='{$safeValue}'";
		}
		return implode(" {$op} ", $parts);
	}

	public function agentassoc($tblname, $companyId = null, $isSuperAdmin = false){
		$select = "SELECT * FROM `$tblname`";
		$where = [];
		if (!$isSuperAdmin && $companyId !== null && $this->hasColumn($tblname, 'company_id')) {
			$where[] = "company_id = " . intval($companyId);
		}
		if (!empty($where)) {
			$select .= " WHERE " . implode(' AND ', $where);
		}
		if ($this->hasColumn($tblname, 'agent_name')) {
			$select .= " ORDER BY agent_name ASC";
		}

		$select_fire = mysqli_query($this->conn, $select);
		if($select_fire && mysqli_num_rows($select_fire) > 0){
			return mysqli_fetch_all($select_fire, MYSQLI_ASSOC);
		}
		return false;
	}

	public function insert($tblname, $filed_data){
		$queryParts = [];

		if ($tblname === 'users') {
			if ($this->hasColumn('users', 'company_id') && !isset($filed_data['company_id']) && isset($_SESSION['company_id'])) {
				$filed_data['company_id'] = intval($_SESSION['company_id']);
			}
		}

		foreach ($filed_data as $q_key => $q_value) {
			$column = ($tblname === 'users') ? $this->mapUserField($q_key) : $q_key;
			if ($column === null || !$this->hasColumn($tblname, $column)) {
				continue;
			}
			$safeValue = mysqli_real_escape_string($this->conn, (string) $q_value);
			$queryParts[] = "`{$column}`='{$safeValue}'";
		}

		if (empty($queryParts)) {
			return false;
		}

		$query = "INSERT INTO `$tblname` SET " . implode(',', $queryParts);
		$insert_fire = mysqli_query($this->conn, $query);
		return $insert_fire ? $query : false;
	}

	public function select_assoc($tblname, $condition, $op='AND'){
		$field_op = $this->buildConditionSql($condition, $op, $tblname);
		if ($field_op === '') {
			return false;
		}

		if ($tblname === 'users') {
			$sql = $this->buildUserSelectSql(null, true) . " LIMIT 999999";
			$select_assoc = "SELECT * FROM (" . $sql . ") AS user_list WHERE {$field_op} LIMIT 1";
		} else {
			$select_assoc = "SELECT * FROM `$tblname` WHERE $field_op LIMIT 1";
		}

		$select_assoc_query = mysqli_query($this->conn, $select_assoc);
		if($select_assoc_query && mysqli_num_rows($select_assoc_query) === 1){
			$select_assoc_fire = mysqli_fetch_assoc($select_assoc_query);
			if($select_assoc_fire){
				if(!empty($select_assoc_fire['agentid'])){
					$select_assoc_fire['agentid'] = $this->selectagentno($select_assoc_fire['agentid']);
				}
				return $select_assoc_fire;
			}
		}
		return false;
	}

	public function selectagentno($agentid)
	{
		$agentid = intval($agentid);
		if ($agentid <= 0) {
			return '';
		}
		$sql= "SELECT `agent_ext` FROM `agent` WHERE `agent_id`= $agentid LIMIT 1";
		$query = mysqli_query($this->conn, $sql);
		if ($query && ($select = mysqli_fetch_array($query, MYSQLI_NUM))) {
			return $select[0] ?? '';
		}
		return '';
	}

	public function select($tblname, $companyId = null, $isSuperAdmin = false, $currentUserRole = ''){
		if ($tblname === 'users') {
			$select = $this->buildUserSelectSql($companyId, $isSuperAdmin, $currentUserRole);
		} else {
			$select = "SELECT * FROM `$tblname`";
		}

		$select_fire = mysqli_query($this->conn, $select);
		if($select_fire && mysqli_num_rows($select_fire) > 0){
			return mysqli_fetch_all($select_fire, MYSQLI_ASSOC);
		}
		return false;
	}

	public function update($tblname, $field_data, $condition, $op='AND'){
		$fieldRowParts = [];
		foreach ($field_data as $q_key => $q_value) {
			$column = ($tblname === 'users') ? $this->mapUserField($q_key) : $q_key;
			if ($column === null || !$this->hasColumn($tblname, $column)) {
				continue;
			}
			$safeValue = mysqli_real_escape_string($this->conn, (string) $q_value);
			$fieldRowParts[] = "`{$column}`='{$safeValue}'";
		}
		$field_op = $this->buildConditionSql($condition, $op, $tblname);

		if (empty($fieldRowParts) || $field_op === '') {
			return false;
		}

		$update = "UPDATE `$tblname` SET " . implode(',', $fieldRowParts) . " WHERE $field_op";
		$update_fire = mysqli_query($this->conn, $update);
		return $update_fire ? $update_fire : false;
	}

	public function delete($tblname, $condition, $op='AND'){
		$delete_data = $this->buildConditionSql($condition, $op, $tblname);
		if ($delete_data === '') {
			return false;
		}
		$delete = "DELETE FROM `$tblname` WHERE $delete_data";
		$delete_fire = mysqli_query($this->conn, $delete);
		return $delete_fire ? $delete_fire : false;
	}

	public function search($tblname, $search_val, $op="AND", $companyId = null, $isSuperAdmin = false, $currentUserRole = ''){
		if ($tblname === 'users') {
			$emailColumn = $this->resolveUserColumn('user_email', 'email');
			$roleColumn = $this->resolveUserColumn('user_type', 'role');
			$conditions = [];
			foreach($search_val as $s_key => $s_value){
				$safeValue = mysqli_real_escape_string($this->conn, (string) $s_value);
				if (in_array($s_key, ['email', 'user_email'], true) && $emailColumn) {
					$conditions[] = "u.`{$emailColumn}` LIKE '%{$safeValue}%'";
				} elseif (in_array($s_key, ['role', 'user_type'], true) && $roleColumn) {
					$conditions[] = "u.`{$roleColumn}` LIKE '%{$safeValue}%'";
				}
			}

			$sql = $this->buildUserSelectSql($companyId, $isSuperAdmin, $currentUserRole);
			if (!empty($conditions)) {
				$sql = preg_replace('/ ORDER BY u\.id DESC$/', '', $sql);
				$sql .= (stripos($sql, ' WHERE ') !== false ? ' AND ' : ' WHERE ') . '(' . implode(" {$op} ", $conditions) . ') ORDER BY u.id DESC';
			}
			$search_query = mysqli_query($this->conn, $sql);
		} else {
			$search = "";
			foreach($search_val as $s_key => $s_value){
				$safeValue = mysqli_real_escape_string($this->conn, (string) $s_value);
				$search .= "$s_key LIKE '%$safeValue%' $op ";
			}
			$search = rtrim($search, "$op ");
			$search_query = mysqli_query($this->conn, "SELECT * FROM `$tblname` WHERE $search");
		}

		if($search_query && mysqli_num_rows($search_query) > 0){
			return mysqli_fetch_all($search_query, MYSQLI_ASSOC);
		}
		return false;
	}

	public function checkkeyword()
	{
		if(isset($_POST['keyword']) && !empty(trim($_POST['keyword'])))
		{
			$keyword = $this->htmlvalidation($_POST['keyword']);
			$match_field['agent_ext'] = $keyword;
			$match_field['agent_name'] = $keyword;
			$select = $this->search("agent", $match_field, "OR");
		}
		else
		{
			$select = $this->select("agent");
		}
	}
}
?>