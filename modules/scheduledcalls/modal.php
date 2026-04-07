<?php
class Scheduledcalls_modal {
    public $conn;

    public function __construct()
    {
        $this->conn = ConnectDB();
    }

    private function fetchRows($query)
    {
        $rows = [];
        $result = mysqli_query($this->conn, $query);
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    public function getCompanies($companyId = 0)
    {
        $companyId = intval($companyId);
        $where = $companyId > 0 ? "WHERE id = {$companyId}" : '';
        return $this->fetchRows("SELECT id, name FROM companies {$where} ORDER BY name ASC");
    }

    public function getCampaignsByCompany($companyId)
    {
        $companyId = intval($companyId);
        if ($companyId <= 0) {
            return [];
        }

        return $this->fetchRows("SELECT id, name FROM campaign WHERE company_id = {$companyId} AND is_deleted = 0 ORDER BY name ASC");
    }

    public function getRows($companyId, $campaignId = 0, $status = '', $routeType = '')
    {
        $companyId = intval($companyId);
        $campaignId = intval($campaignId);
        $status = strtolower(trim((string) $status));
        $routeType = strtolower(trim((string) $routeType));

        if ($companyId <= 0) {
            return [];
        }

        $whereClauses = ["sc.company_id = {$companyId}", "UPPER(COALESCE(sc.route_type, '')) = 'AGENT'"];
        if ($campaignId > 0) {
            $whereClauses[] = "sc.campaign_id = {$campaignId}";
        }
        if ($status !== '') {
            $safeStatus = mysqli_real_escape_string($this->conn, $status);
            $whereClauses[] = "LOWER(COALESCE(sc.status, '')) = '{$safeStatus}'";
        }
        if ($routeType !== '') {
            $safeRouteType = mysqli_real_escape_string($this->conn, $routeType);
            $whereClauses[] = "LOWER(COALESCE(sc.route_type, '')) = '{$safeRouteType}'";
        }

        $where = 'WHERE ' . implode(' AND ', $whereClauses);
        $query = "SELECT sc.id,
                         sc.company_id,
                         sc.campaign_id,
                         COALESCE(c.name, CONCAT('Campaign #', sc.campaign_id)) AS campaign_name,
                         COALESCE(NULLIF(TRIM(cn.phone_e164), ''), cn.phone_raw, '') AS contact_number,
                         TRIM(CONCAT(COALESCE(cn.first_name, ''), ' ', COALESCE(cn.last_name, ''))) AS contact_name,
                         COALESCE(sc.route_type, 'Queue') AS route_type,
                         COALESCE(sc.queue_dn, '') AS queue_dn,
                         COALESCE(sc.agent_id, 0) AS agent_id,
                         COALESCE(sc.agent_ext, '') AS agent_ext,
                         COALESCE(a.agent_name, '') AS agent_name,
                         sc.scheduled_for,
                         sc.status,
                         sc.disposition_label,
                         sc.note_text,
                         sc.last_attempt_at,
                         sc.started_at,
                         sc.completed_at,
                         sc.created_at
                  FROM scheduled_calls sc
                  LEFT JOIN campaign c
                    ON c.id = sc.campaign_id AND c.company_id = sc.company_id
                  LEFT JOIN campaignnumbers cn
                    ON cn.id = sc.campaignnumber_id AND cn.company_id = sc.company_id
                  LEFT JOIN agent a
                    ON a.company_id = sc.company_id AND a.agent_id = sc.agent_id
                  {$where}
                  ORDER BY sc.scheduled_for ASC, sc.id DESC
                  LIMIT 2000";

        $rows = $this->fetchRows($query);
        $response = [];

        foreach ($rows as $row) {
            $routeTypeLabel = trim((string) ($row['route_type'] ?? 'Queue'));
            $agentName = trim((string) ($row['agent_name'] ?? ''));
            $agentExt = trim((string) ($row['agent_ext'] ?? ''));
            $destinationLabel = trim((string) ($row['queue_dn'] ?? ''));

            if (strcasecmp($routeTypeLabel, 'Agent') === 0) {
                $destinationLabel = $agentName !== '' ? $agentName : 'Agent';
                if ($agentExt !== '') {
                    $destinationLabel .= ' (' . $agentExt . ')';
                }
            } elseif ($destinationLabel === '') {
                $destinationLabel = 'Campaign Queue';
            }

            $response[] = [
                'id' => $row['id'],
                'campaign_name' => $row['campaign_name'],
                'contact_number' => $row['contact_number'],
                'contact_name' => trim((string) ($row['contact_name'] ?? '')) ?: '-',
                'route_type' => $routeTypeLabel,
                'destination_label' => $destinationLabel,
                'scheduled_for' => $row['scheduled_for'],
                'status' => $row['status'] ?? '',
                'disposition_label' => $row['disposition_label'] ?? '',
                'note_text' => $row['note_text'] ?? '',
                'last_attempt_at' => $row['last_attempt_at'],
                'started_at' => $row['started_at'],
                'completed_at' => $row['completed_at'],
                'created_at' => $row['created_at'],
            ];
        }

        return $response;
    }
}
?>