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

    private function companyCondition($alias, $companyId)
    {
        $companyId = intval($companyId);
        return $companyId > 0 ? " AND {$alias}.company_id = {$companyId}" : "";
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
        $point = $this->escape($point);
        $startDate = $this->escape($range['start_date']);
        $endDate = $this->escape($range['end_date']);
        $companyFilter = $companyId > 0 ? " AND company_id = " . intval($companyId) : "";

        $sql = "SELECT COUNT(point) AS total FROM $tablename WHERE point = '$point' AND start_date >= '$startDate' AND start_date <= '$endDate'{$companyFilter}";
        $search_query = mysqli_query($this->conn, $sql);
        if($search_query && mysqli_num_rows($search_query) > 0){
            $search_fetch = mysqli_fetch_assoc($search_query);
            return [intval($search_fetch['total'] ?? 0)];
        }

        return [0];
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
        $startDate = $this->escape($range['start_date']);
        $endDate = $this->escape($range['end_date']);
        $companyFilter = $companyId > 0 ? " AND company_id = " . intval($companyId) : "";

		$sql = "SELECT COUNT(point) AS total FROM $tablename WHERE start_date >= '$startDate' AND start_date <= '$endDate'{$companyFilter}";
		$search_query = mysqli_query($this->conn, $sql);
		if($search_query && mysqli_num_rows($search_query) > 0){
			$search_fetch = mysqli_fetch_assoc($search_query);
			return [intval($search_fetch['total'] ?? 0)];
		}

		return [0];
	}

	public function averagescore($tablename, $startDate = null, $endDate = null, $companyId = 0)
	{
		$data = [];
		$id = 1;
        $range = (!$startDate || !$endDate) ? $this->resolveDateRange('today') : ['start_date' => $startDate, 'end_date' => $endDate];
        $startDate = $this->escape($range['start_date']);
        $endDate = $this->escape($range['end_date']);
        $agentCompanyFilter = $companyId > 0 ? " AND a.company_id = " . intval($companyId) : "";

        $query = "SELECT a.agent_ext,
                         COALESCE(a.agent_name, '') AS agent_name,
                         COALESCE(SUM(CAST(r.point AS UNSIGNED)), 0) AS total_point,
                         ROUND(COALESCE(AVG(CAST(r.point AS UNSIGNED)), 0), 2) AS avg_point,
                         COUNT(r.point) AS total_calls,
                         COUNT(r.agentno) AS total_records
                  FROM agent a
                  LEFT JOIN $tablename r
                    ON a.agent_ext = r.agentno
                   AND r.start_date >= '$startDate'
                   AND r.start_date <= '$endDate'
                  WHERE a.is_archived = 0{$agentCompanyFilter}
                  GROUP BY a.agent_id, a.agent_ext, a.agent_name
                  ORDER BY total_calls DESC, avg_point DESC, a.agent_ext ASC";

        if ($sql = $this->conn->query($query)) {
            while ($row = mysqli_fetch_assoc($sql)) {
                $row['cx_id'] = $id;
                $totalRecords = intval($row['total_records'] ?? 0);
                $ratedCalls = intval($row['total_calls'] ?? 0);
                $gradedPercent = $totalRecords > 0 ? ($ratedCalls * 100 / $totalRecords) : 0;
                $row['total'] = $totalRecords;
                $row['percent_grade'] = number_format($gradedPercent, 2) . '%';
                $row['percent_not_grade'] = number_format(max(0, 100 - $gradedPercent), 2) . '%';
                $id++;
                $data[] = $row;
            }
        }

        return $data;
	}

    public function getOutboundSummary($startDate, $endDate, $companyId = 0)
    {
        $startDate = $this->escape($startDate);
        $endDate = $this->escape($endDate);
        $sqlStart = $startDate . ' 00:00:00';
        $sqlEnd = $endDate . ' 23:59:59';
        $companyFilter = $this->companyCondition('d', $companyId);
        $agentCompanyFilter = $companyId > 0 ? " AND a.company_id = " . intval($companyId) : "";

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

        $query = "SELECT COUNT(*) AS total_calls,
                         COUNT(DISTINCT CASE WHEN d.campaignnumber_id > 0 THEN d.campaignnumber_id END) AS unique_numbers,
                         COUNT(DISTINCT CASE WHEN d.campaign_id > 0 THEN d.campaign_id END) AS active_campaigns,
                         SUM(CASE WHEN d.disposition IS NOT NULL AND d.disposition <> '' THEN 1 ELSE 0 END) AS dispositions_logged
                  FROM dialer_call_log d
                  WHERE d.started_at >= '$sqlStart' AND d.started_at <= '$sqlEnd'{$companyFilter}";

        if ($result = $this->conn->query($query)) {
            $row = mysqli_fetch_assoc($result);
            if ($row) {
                $summary['total_calls'] = intval($row['total_calls'] ?? 0);
                $summary['unique_numbers'] = intval($row['unique_numbers'] ?? 0);
                $summary['active_campaigns'] = intval($row['active_campaigns'] ?? 0);
                $summary['dispositions_logged'] = intval($row['dispositions_logged'] ?? 0);
            }
        }

        $connectQuery = "SELECT COUNT(*) AS connected_calls,
                                COUNT(DISTINCT c.r_ext) AS active_agents,
                                SEC_TO_TIME(COALESCE(SUM(TIME_TO_SEC(NULLIF(c.r_totaltime, ''))), 0)) AS total_talk_time,
                                TIME_FORMAT(SEC_TO_TIME(COALESCE(AVG(TIME_TO_SEC(NULLIF(c.r_totaltime, ''))), 0)), '%H:%i:%s') AS avg_talk_time
                         FROM custdata c
                         INNER JOIN agent a ON a.agent_ext = c.r_ext
                         WHERE c.r_externalno IS NOT NULL AND c.r_externalno <> ''
                           AND c.r_startdt >= '$startDate' AND c.r_startdt <= '$endDate'{$agentCompanyFilter}";

        if ($connectResult = $this->conn->query($connectQuery)) {
            $row = mysqli_fetch_assoc($connectResult);
            if ($row) {
                $summary['connected_calls'] = intval($row['connected_calls'] ?? 0);
                $summary['active_agents'] = intval($row['active_agents'] ?? 0);
                $summary['total_talk_time'] = $row['total_talk_time'] ?: '00:00:00';
                $summary['avg_talk_time'] = $row['avg_talk_time'] ?: '00:00:00';
            }
        }

        return $summary;
    }

    public function getCallStatusBreakdown($startDate, $endDate, $companyId = 0)
    {
        $startDate = $this->escape($startDate);
        $endDate = $this->escape($endDate);
        $companyFilter = $this->companyCondition('d', $companyId);
        $data = [];

        $query = "SELECT COALESCE(NULLIF(d.call_status, ''), 'UNKNOWN') AS status, COUNT(*) AS total
                  FROM dialer_call_log d
                  WHERE d.started_at >= '{$startDate} 00:00:00' AND d.started_at <= '{$endDate} 23:59:59'{$companyFilter}
                  GROUP BY COALESCE(NULLIF(d.call_status, ''), 'UNKNOWN')
                  ORDER BY total DESC, status ASC
                  LIMIT 6";

        if ($result = $this->conn->query($query)) {
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }
        }

        return $data;
    }

    public function getCampaignActivity($startDate, $endDate, $companyId = 0)
    {
        $startDate = $this->escape($startDate);
        $endDate = $this->escape($endDate);
        $companyFilter = $this->companyCondition('d', $companyId);
        $data = [];

        $query = "SELECT COALESCE(c.name, CONCAT('Campaign #', d.campaign_id)) AS campaign_name,
                         COUNT(*) AS total_calls,
                         COUNT(DISTINCT CASE WHEN d.campaignnumber_id > 0 THEN d.campaignnumber_id END) AS unique_numbers,
                         MAX(d.started_at) AS last_call_at
                  FROM dialer_call_log d
                  LEFT JOIN campaign c ON c.id = d.campaign_id
                  WHERE d.started_at >= '{$startDate} 00:00:00' AND d.started_at <= '{$endDate} 23:59:59'{$companyFilter}
                  GROUP BY d.campaign_id, c.name
                  ORDER BY total_calls DESC, unique_numbers DESC, campaign_name ASC
                  LIMIT 5";

        if ($result = $this->conn->query($query)) {
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }
        }

        return $data;
    }

    public function getAgentPickupAnalytics($startDate, $endDate, $companyId = 0)
    {
        $startDate = $this->escape($startDate);
        $endDate = $this->escape($endDate);
        $agentCompanyFilter = $companyId > 0 ? " AND a.company_id = " . intval($companyId) : "";
        $data = [];

        $query = "SELECT a.agent_ext,
                         COALESCE(a.agent_name, '') AS agent_name,
                         COUNT(c.r_callid) AS connected_calls,
                         COUNT(DISTINCT c.r_externalno) AS unique_numbers,
                         SEC_TO_TIME(COALESCE(SUM(TIME_TO_SEC(NULLIF(c.r_totaltime, ''))), 0)) AS total_talk_time,
                         TIME_FORMAT(SEC_TO_TIME(COALESCE(AVG(TIME_TO_SEC(NULLIF(c.r_totaltime, ''))), 0)), '%H:%i:%s') AS avg_talk_time
                  FROM agent a
                  LEFT JOIN custdata c
                    ON c.r_ext = a.agent_ext
                   AND c.r_externalno IS NOT NULL
                   AND c.r_externalno <> ''
                   AND c.r_startdt >= '$startDate'
                   AND c.r_startdt <= '$endDate'
                  WHERE a.is_archived = 0{$agentCompanyFilter}
                  GROUP BY a.agent_id, a.agent_ext, a.agent_name
                  HAVING connected_calls > 0 OR unique_numbers > 0
                  ORDER BY connected_calls DESC, unique_numbers DESC, a.agent_ext ASC";

        if ($result = $this->conn->query($query)) {
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }
        }

        return $data;
    }
}
?>