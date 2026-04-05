<?php
/* Modulename_modal */
Class Dashboard_modal{
	public function __construct()
	{
		$this->conn = ConnectDB();
	}

	public function htmlvalidation($form_data){
		$form_data = trim(stripslashes(htmlspecialchars($form_data)));
		$form_data = mysqli_real_escape_string($this->conn, trim(strip_tags($form_data)));
		return $form_data;
	}

    private function escape($value)
    {
        return mysqli_real_escape_string($this->conn, (string) $value);
    }

    private function companyCondition($alias, $companyId, $tableName = 'dialer_call_log', $includeLegacyRows = false)
    {
        $companyId = intval($companyId);
        if ($companyId <= 0 || !$this->hasColumn($tableName, 'company_id')) {
            return '';
        }

        $prefix = $alias !== '' ? rtrim($alias, '.') . '.' : '';
        if ($includeLegacyRows) {
            return " AND ({$prefix}company_id = {$companyId} OR COALESCE({$prefix}company_id, 0) = 0)";
        }

        return " AND {$prefix}company_id = {$companyId}";
    }

    private function hasColumn($tableName, $columnName)
    {
        $tableName = preg_replace('/[^a-zA-Z0-9_]/', '', $tableName);
        $columnName = preg_replace('/[^a-zA-Z0-9_]/', '', $columnName);
        $query = $this->conn->query("SHOW COLUMNS FROM `{$tableName}` LIKE '{$columnName}'");
        return $query && mysqli_num_rows($query) > 0;
    }

    private function fetchAssoc($sql)
    {
        $result = $this->conn->query($sql);
        if (!$result) {
            error_log('Dashboard SQL error: ' . mysqli_error($this->conn) . ' | Query: ' . $sql);
            return [];
        }

        return mysqli_fetch_assoc($result) ?: [];
    }

    private function fetchAll($sql)
    {
        $result = $this->conn->query($sql);
        if (!$result) {
            error_log('Dashboard SQL error: ' . mysqli_error($this->conn) . ' | Query: ' . $sql);
            return [];
        }

        return mysqli_fetch_all($result, MYSQLI_ASSOC) ?: [];
    }

    private function buildDateExpression($tableName, $primaryColumn, $fallbackColumn = null, $alias = '')
    {
        $prefix = $alias !== '' ? rtrim($alias, '.') . '.' : '';
        $hasPrimary = $this->hasColumn($tableName, $primaryColumn);
        $hasFallback = $fallbackColumn !== null && $this->hasColumn($tableName, $fallbackColumn);

        if ($hasPrimary && $hasFallback) {
            return "COALESCE({$prefix}{$primaryColumn}, {$prefix}{$fallbackColumn})";
        }
        if ($hasPrimary) {
            return $prefix . $primaryColumn;
        }
        if ($hasFallback) {
            return $prefix . $fallbackColumn;
        }

        return $prefix . $primaryColumn;
    }

    private function buildAnsweredCondition($alias = 'd', $tableName = 'dialer_call_log')
    {
        $prefix = $alias !== '' ? rtrim($alias, '.') . '.' : '';
        $statusCondition = $this->hasColumn($tableName, 'call_status')
            ? "UPPER(COALESCE({$prefix}call_status, '')) = 'ANSWERED'"
            : '0 = 1';
        $agentCondition = $this->hasColumn($tableName, 'agent_id')
            ? "NULLIF(TRIM(COALESCE({$prefix}agent_id, '')), '') IS NOT NULL"
            : '0 = 1';

        return "({$statusCondition} OR {$agentCondition})";
    }

    private function getUniqueNumberExpression($alias = 'd')
    {
        $prefix = $alias !== '' ? rtrim($alias, '.') . '.' : '';

        if ($this->hasColumn('dialer_call_log', 'campaignnumber_id')) {
            return "CASE WHEN {$prefix}campaignnumber_id > 0 THEN {$prefix}campaignnumber_id END";
        }
        if ($this->hasColumn('dialer_call_log', 'caller_id')) {
            return "NULLIF({$prefix}caller_id, '')";
        }
        if ($this->hasColumn('dialer_call_log', 'call_id')) {
            return "NULLIF({$prefix}call_id, '')";
        }

        return 'NULL';
    }

    private function formatSeconds($seconds)
    {
        $seconds = max(0, (int) round($seconds));
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $remainingSeconds = $seconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $remainingSeconds);
    }

    private function tableExists($tableName)
    {
        $tableName = $this->escape($tableName);
        $query = $this->conn->query("SHOW TABLES LIKE '{$tableName}'");
        return $query && mysqli_num_rows($query) > 0;
    }

    private function getRateRows($startDate, $endDate, $companyId = 0)
    {
        if (!$this->tableExists('rate')) {
            return [];
        }

        $startDate = $this->escape($startDate);
        $endDate = $this->escape($endDate);
        $companyFilter = $this->companyCondition('r', $companyId, 'rate', true);
        $dateExpression = $this->buildDateExpression('rate', 'created_at', 'start_date', 'r');
        $rows = [];

        $sql = "SELECT r.agentno,
                       {$dateExpression} AS rating_date,
                       r.ratings_json,
                       COALESCE(a.agent_ext, r.agentno, 'Unassigned') AS agent_ext,
                       COALESCE(a.agent_name, '') AS agent_name
                FROM rate r
                LEFT JOIN agent a ON a.agent_ext = r.agentno OR CAST(a.agent_id AS CHAR) = r.agentno
                WHERE {$dateExpression} >= '{$startDate} 00:00:00'
                  AND {$dateExpression} <= '{$endDate} 23:59:59'{$companyFilter}
                ORDER BY {$dateExpression} DESC";

        if ($result = $this->conn->query($sql)) {
            while ($row = mysqli_fetch_assoc($result)) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    private function extractRatingValues($ratingsJson)
    {
        $decoded = json_decode((string) $ratingsJson, true);
        $values = [];

        if (!is_array($decoded)) {
            return $values;
        }

        foreach ($decoded as $value) {
            if (!is_numeric($value)) {
                continue;
            }

            $score = (float) $value;
            if ($score > 0) {
                $values[] = $score;
            }
        }

        return $values;
    }

    public function resolveDateRange($rangeKey = 'today')
    {
        $allowedRanges = ['today', 'this_week', 'last_week', 'this_month', 'last_month'];
        if (!in_array($rangeKey, $allowedRanges, true)) {
            $rangeKey = 'today';
        }

        $today = new DateTimeImmutable('today');

        switch ($rangeKey) {
            case 'this_week':
                $label = 'This Week';
                $start = $today->modify('monday this week');
                $end = $today->modify('sunday this week');
                break;
            case 'last_week':
                $label = 'Last Week';
                $start = $today->modify('monday last week');
                $end = $today->modify('sunday last week');
                break;
            case 'this_month':
                $label = 'This Month';
                $start = $today->modify('first day of this month');
                $end = $today->modify('last day of this month');
                break;
            case 'last_month':
                $label = 'Last Month';
                $start = $today->modify('first day of last month');
                $end = $today->modify('last day of last month');
                break;
            case 'today':
            default:
                $label = 'Today';
                $start = $today;
                $end = $today;
                break;
        }

        return [
            'key' => $rangeKey,
            'label' => $label,
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'sql_start' => $start->format('Y-m-d 00:00:00'),
            'sql_end' => $end->format('Y-m-d 23:59:59'),
            'display' => $start->format('M d, Y') . ($start != $end ? ' - ' . $end->format('M d, Y') : ''),
        ];
    }
	
	public function insert($tblname, $filed_data){

		$query_data = "";

		foreach ($filed_data as $q_key => $q_value) {
			$query_data = $query_data."$q_key='$q_value',";
		}
		$query_data = rtrim($query_data,",");

		$query = "INSERT INTO $tblname SET $query_data";
		$insert_fire = mysqli_query($this->conn, $query);
		if($insert_fire){
			return $query; 
		}
		else{
			return false;
		}

	}

	public function agentoutcall($tblname,$column, $startDate = null, $endDate = null, $companyId = 0)
	{
        $range = (!$startDate || !$endDate) ? $this->resolveDateRange('today') : ['start_date' => $startDate, 'end_date' => $endDate];
        $startDate = $this->escape($range['start_date']);
        $endDate = $this->escape($range['end_date']);
        $companyFilter = $this->companyCondition('d', $companyId);

		$sql = "SELECT COALESCE(NULLIF(d.agent_id, ''), 'Unassigned') AS `$column`,
                       COUNT(*) AS `Total`,
                       COUNT(CASE WHEN d.call_status = 'ANSWERED' THEN 1 END) AS `Outbound`,
                       COUNT(CASE WHEN d.call_status <> 'ANSWERED' OR d.call_status IS NULL THEN 1 END) AS `Notanswered`
                FROM dialer_call_log d
                WHERE d.started_at >= '{$startDate} 00:00:00' AND d.started_at <= '{$endDate} 23:59:59'{$companyFilter}
                GROUP BY d.agent_id
                ORDER BY COUNT(*) DESC";

		$total_query = mysqli_query($this->conn, $sql);
		if($total_query && mysqli_num_rows($total_query) > 0){
			return mysqli_fetch_all($total_query, MYSQLI_ASSOC);
		}

		return [];
	}
	
	public function calltotal($tblname,$countattr1,$countattr2, $startDate = null, $endDate = null, $companyId = 0){
        $range = (!$startDate || !$endDate) ? $this->resolveDateRange('today') : ['start_date' => $startDate, 'end_date' => $endDate];
        $startDate = $this->escape($range['start_date']);
        $endDate = $this->escape($range['end_date']);
        $countattr1 = $this->escape($countattr1);
        $countattr2 = $this->escape($countattr2);
        $companyFilter = $this->companyCondition('d', $companyId);

		$search = "SELECT COUNT(call_status) AS total
                   FROM dialer_call_log d
                   WHERE (d.call_status='$countattr1' OR d.call_status='$countattr2')
                     AND d.started_at >= '{$startDate} 00:00:00'
                     AND d.started_at <= '{$endDate} 23:59:59'{$companyFilter}";
		$search_query = mysqli_query($this->conn, $search);
		if($search_query && mysqli_num_rows($search_query) > 0){
			$search_fetch = mysqli_fetch_array($search_query);
			return $search_fetch;
		}

		return [0];
	}

    private function pointCount($tablename, $point, $startDate = null, $endDate = null, $companyId = 0)
    {
        $range = (!$startDate || !$endDate) ? $this->resolveDateRange('today') : ['start_date' => $startDate, 'end_date' => $endDate];
        $targetPoint = (int) $point;
        $count = 0;

        foreach ($this->getRateRows($range['start_date'], $range['end_date'], $companyId) as $row) {
            $values = $this->extractRatingValues($row['ratings_json'] ?? '');
            if (empty($values)) {
                continue;
            }

            $averagePoint = (int) round(array_sum($values) / count($values));
            if ($averagePoint === $targetPoint) {
                $count++;
            }
        }

        return [$count];
    }

	public function pointone($tablename,$point, $startDate = null, $endDate = null, $companyId = 0)
	{
		return $this->pointCount($tablename, $point, $startDate, $endDate, $companyId);
	}

	public function pointthree($tablename,$point, $startDate = null, $endDate = null, $companyId = 0)
	{
		return $this->pointCount($tablename, $point, $startDate, $endDate, $companyId);
	}

	public function pointfive($tablename,$point, $startDate = null, $endDate = null, $companyId = 0)
	{
		return $this->pointCount($tablename, $point, $startDate, $endDate, $companyId);
	}

	public function totalcallpoint($tablename, $startDate = null, $endDate = null, $companyId = 0)
	{
        $range = (!$startDate || !$endDate) ? $this->resolveDateRange('today') : ['start_date' => $startDate, 'end_date' => $endDate];
        $total = 0;

        foreach ($this->getRateRows($range['start_date'], $range['end_date'], $companyId) as $row) {
            if (!empty($this->extractRatingValues($row['ratings_json'] ?? ''))) {
                $total++;
            }
        }

		return [$total];
	}

	public function averagescore($tablename, $startDate = null, $endDate = null, $companyId = 0)
	{
		$data = [];
		$id = 1;
        $range = (!$startDate || !$endDate) ? $this->resolveDateRange('today') : ['start_date' => $startDate, 'end_date' => $endDate];
        $agentStats = [];

        foreach ($this->getRateRows($range['start_date'], $range['end_date'], $companyId) as $row) {
            $agentKey = $row['agent_ext'] ?: ($row['agentno'] ?: 'Unassigned');

            if (!isset($agentStats[$agentKey])) {
                $agentStats[$agentKey] = [
                    'agent_ext' => $agentKey,
                    'agent_name' => $row['agent_name'] ?? '',
                    'total_point' => 0,
                    'score_count' => 0,
                    'total_calls' => 0,
                    'total' => 0,
                ];
            }

            $agentStats[$agentKey]['total']++;
            $values = $this->extractRatingValues($row['ratings_json'] ?? '');

            if (!empty($values)) {
                $agentStats[$agentKey]['total_point'] += array_sum($values);
                $agentStats[$agentKey]['score_count'] += count($values);
                $agentStats[$agentKey]['total_calls']++;
            }
        }

        foreach ($agentStats as $row) {
            $totalRecords = intval($row['total'] ?? 0);
            $ratedCalls = intval($row['total_calls'] ?? 0);
            $gradedPercent = $totalRecords > 0 ? ($ratedCalls * 100 / $totalRecords) : 0;
            $row['cx_id'] = $id;
            $row['avg_point'] = ($row['score_count'] ?? 0) > 0
                ? number_format($row['total_point'] / $row['score_count'], 2)
                : '0.00';
            $row['percent_grade'] = number_format($gradedPercent, 2) . '%';
            $row['percent_not_grade'] = number_format(max(0, 100 - $gradedPercent), 2) . '%';
            unset($row['score_count']);
            $id++;
            $data[] = $row;
        }

        usort($data, function ($left, $right) {
            if ($left['total_calls'] === $right['total_calls']) {
                return strcmp((string) $left['agent_ext'], (string) $right['agent_ext']);
            }
            return $right['total_calls'] <=> $left['total_calls'];
        });

        return $data;
	}

    public function getOutboundSummary($startDate, $endDate, $companyId = 0)
    {
        $summary = [
            'total_calls' => 0,
            'unique_numbers' => 0,
            'connected_calls' => 0,
            'active_agents' => 0,
            'active_campaigns' => 0,
            'dispositions_logged' => 0,
            'total_talk_time' => '00:00:00',
            'avg_talk_time' => '00:00:00',
        ];

        if (!$this->tableExists('dialer_call_log')) {
            return $summary;
        }

        $startDate = $this->escape($startDate);
        $endDate = $this->escape($endDate);
        $sqlStart = $startDate . ' 00:00:00';
        $sqlEnd = $endDate . ' 23:59:59';
        $companyFilter = $this->companyCondition('d', $companyId, 'dialer_call_log', true);
        $dateExpression = $this->buildDateExpression('dialer_call_log', 'started_at', 'created_at', 'd');
        $uniqueExpression = $this->getUniqueNumberExpression('d');
        $answeredCondition = $this->buildAnsweredCondition('d', 'dialer_call_log');
        $campaignExpression = $this->hasColumn('dialer_call_log', 'campaign_id')
            ? "COUNT(DISTINCT CASE WHEN d.campaign_id > 0 THEN d.campaign_id END)"
            : '0';
        $activeAgentExpression = $this->hasColumn('dialer_call_log', 'agent_id')
            ? "COUNT(DISTINCT CASE WHEN {$answeredCondition} THEN NULLIF(TRIM(COALESCE(d.agent_id, '')), '') END)"
            : '0';
        $dispositionExpression = $this->hasColumn('dialer_call_log', 'disposition')
            ? "SUM(CASE WHEN d.disposition IS NOT NULL AND d.disposition <> '' THEN 1 ELSE 0 END)"
            : '0';
        $durationSumExpression = $this->hasColumn('dialer_call_log', 'duration_sec')
            ? "COALESCE(SUM(CASE WHEN d.duration_sec > 0 THEN d.duration_sec ELSE 0 END), 0)"
            : '0';
        $durationAvgExpression = $this->hasColumn('dialer_call_log', 'duration_sec')
            ? "COALESCE(AVG(CASE WHEN d.duration_sec > 0 THEN d.duration_sec END), 0)"
            : '0';

        $query = "SELECT COUNT(*) AS total_calls,
                         COUNT(DISTINCT {$uniqueExpression}) AS unique_numbers,
                         SUM(CASE WHEN {$answeredCondition} THEN 1 ELSE 0 END) AS connected_calls,
                         {$activeAgentExpression} AS active_agents,
                         {$campaignExpression} AS active_campaigns,
                         {$dispositionExpression} AS dispositions_logged,
                         {$durationSumExpression} AS total_talk_seconds,
                         {$durationAvgExpression} AS avg_talk_seconds
                  FROM dialer_call_log d
                  WHERE {$dateExpression} >= '$sqlStart' AND {$dateExpression} <= '$sqlEnd'{$companyFilter}";

        $row = $this->fetchAssoc($query);
        if (!empty($row)) {
            $summary['total_calls'] = intval($row['total_calls'] ?? 0);
            $summary['unique_numbers'] = intval($row['unique_numbers'] ?? 0);
            $summary['connected_calls'] = intval($row['connected_calls'] ?? 0);
            $summary['active_agents'] = intval($row['active_agents'] ?? 0);
            $summary['active_campaigns'] = intval($row['active_campaigns'] ?? 0);
            $summary['dispositions_logged'] = intval($row['dispositions_logged'] ?? 0);
            $summary['total_talk_time'] = $this->formatSeconds($row['total_talk_seconds'] ?? 0);
            $summary['avg_talk_time'] = $this->formatSeconds($row['avg_talk_seconds'] ?? 0);
        }

        return $summary;
    }

    public function getCallStatusBreakdown($startDate, $endDate, $companyId = 0)
    {
        if (!$this->tableExists('dialer_call_log')) {
            return [];
        }

        $startDate = $this->escape($startDate);
        $endDate = $this->escape($endDate);
        $companyFilter = $this->companyCondition('d', $companyId, 'dialer_call_log', true);
        $dateExpression = $this->buildDateExpression('dialer_call_log', 'started_at', 'created_at', 'd');
        $statusExpression = $this->hasColumn('dialer_call_log', 'call_status')
            ? "COALESCE(NULLIF(d.call_status, ''), 'UNKNOWN')"
            : "'UNKNOWN'";

        $query = "SELECT {$statusExpression} AS status, COUNT(*) AS total
                  FROM dialer_call_log d
                  WHERE {$dateExpression} >= '{$startDate} 00:00:00' AND {$dateExpression} <= '{$endDate} 23:59:59'{$companyFilter}
                  GROUP BY {$statusExpression}
                  ORDER BY total DESC, status ASC
                  LIMIT 6";

        return $this->fetchAll($query);
    }

    public function getCampaignActivity($startDate, $endDate, $companyId = 0)
    {
        if (!$this->tableExists('dialer_call_log')) {
            return [];
        }

        $startDate = $this->escape($startDate);
        $endDate = $this->escape($endDate);
        $companyFilter = $this->companyCondition('d', $companyId, 'dialer_call_log', true);
        $dateExpression = $this->buildDateExpression('dialer_call_log', 'started_at', 'created_at', 'd');
        $uniqueExpression = $this->getUniqueNumberExpression('d');
        $hasCampaignTable = $this->tableExists('campaign') && $this->hasColumn('dialer_call_log', 'campaign_id');
        $campaignNameExpression = $hasCampaignTable
            ? "COALESCE(c.name, CONCAT('Campaign #', d.campaign_id))"
            : "'General Activity'";
        $campaignJoin = $hasCampaignTable ? " LEFT JOIN campaign c ON c.id = d.campaign_id" : '';
        $groupBy = $hasCampaignTable ? 'd.campaign_id, c.name' : $campaignNameExpression;

        $query = "SELECT {$campaignNameExpression} AS campaign_name,
                         COUNT(*) AS total_calls,
                         COUNT(DISTINCT {$uniqueExpression}) AS unique_numbers,
                         MAX({$dateExpression}) AS last_call_at
                  FROM dialer_call_log d{$campaignJoin}
                  WHERE {$dateExpression} >= '{$startDate} 00:00:00' AND {$dateExpression} <= '{$endDate} 23:59:59'{$companyFilter}
                  GROUP BY {$groupBy}
                  ORDER BY total_calls DESC, unique_numbers DESC, campaign_name ASC
                  LIMIT 5";

        return $this->fetchAll($query);
    }

    public function getAgentPickupAnalytics($startDate, $endDate, $companyId = 0)
    {
        if (!$this->tableExists('dialer_call_log')) {
            return [];
        }

        $startDate = $this->escape($startDate);
        $endDate = $this->escape($endDate);
        $companyFilter = $this->companyCondition('d', $companyId, 'dialer_call_log', true);
        $dateExpression = $this->buildDateExpression('dialer_call_log', 'started_at', 'created_at', 'd');
        $uniqueExpression = $this->getUniqueNumberExpression('d');
        $answeredCondition = $this->buildAnsweredCondition('d', 'dialer_call_log');
        $durationSumExpression = $this->hasColumn('dialer_call_log', 'duration_sec')
            ? "COALESCE(SUM(CASE WHEN d.duration_sec > 0 THEN d.duration_sec ELSE 0 END), 0)"
            : '0';
        $durationAvgExpression = $this->hasColumn('dialer_call_log', 'duration_sec')
            ? "COALESCE(AVG(CASE WHEN d.duration_sec > 0 THEN d.duration_sec END), 0)"
            : '0';
        $hasAgentId = $this->hasColumn('dialer_call_log', 'agent_id');
        $agentExtExpression = $hasAgentId
            ? "COALESCE(a.agent_ext, NULLIF(TRIM(d.agent_id), ''), 'Unassigned')"
            : "'Unassigned'";
        $agentNameExpression = $this->tableExists('agent')
            ? "COALESCE(a.agent_name, '')"
            : "''";
        $agentJoin = ($this->tableExists('agent') && $hasAgentId)
            ? " LEFT JOIN agent a ON a.agent_ext = d.agent_id OR CAST(a.agent_id AS CHAR) = d.agent_id"
            : '';

        $query = "SELECT {$agentExtExpression} AS agent_ext,
                         {$agentNameExpression} AS agent_name,
                         SUM(CASE WHEN {$answeredCondition} THEN 1 ELSE 0 END) AS connected_calls,
                         COUNT(DISTINCT {$uniqueExpression}) AS unique_numbers,
                         {$durationSumExpression} AS total_talk_seconds,
                         {$durationAvgExpression} AS avg_talk_seconds
                  FROM dialer_call_log d{$agentJoin}
                  WHERE {$dateExpression} >= '{$startDate} 00:00:00'
                    AND {$dateExpression} <= '{$endDate} 23:59:59'{$companyFilter}
                  GROUP BY {$agentExtExpression}, {$agentNameExpression}
                  HAVING connected_calls > 0 OR unique_numbers > 0
                  ORDER BY connected_calls DESC, unique_numbers DESC, agent_ext ASC";

        $data = $this->fetchAll($query);
        foreach ($data as &$row) {
            $row['total_talk_time'] = $this->formatSeconds($row['total_talk_seconds'] ?? 0);
            $row['avg_talk_time'] = $this->formatSeconds($row['avg_talk_seconds'] ?? 0);
            unset($row['total_talk_seconds'], $row['avg_talk_seconds']);
        }
        unset($row);

        return $data;
    }

    public function getLatestOutboundActivity($companyId = 0)
    {
        if (!$this->tableExists('dialer_call_log')) {
            return null;
        }

        $companyFilter = $this->companyCondition('d', $companyId, 'dialer_call_log', true);
        $dateExpression = $this->buildDateExpression('dialer_call_log', 'started_at', 'created_at', 'd');
        $query = "SELECT MAX({$dateExpression}) AS last_activity_at FROM dialer_call_log d WHERE 1=1{$companyFilter}";
        $row = $this->fetchAssoc($query);

        return $row['last_activity_at'] ?? null;
    }
}
?>