<main class="content">
  <div class="container-fluid p-0">
    <h1 class="h3 mb-3">DID Rotation Setup</h1>
    <div class="row">
      <div class="col-12 col-xl-10">
        <div class="card">
          <div class="card-header">
            <h5 class="card-title">Sync DIDs And Map Campaign</h5>
            <h6 class="card-subtitle text-muted">Sync inbound DIDs from PBX, then assign multiple DIDs and one outbound rule per campaign.</h6>
          </div>
          <div class="card-body">
            <?php if (isset($_SESSION['prole']) && $_SESSION['prole'] == 'super_admin'): ?>
            <div class="form-group">
              <label for="didCompanySelect">Company</label>
              <select class="form-control" id="didCompanySelect">
                <option value="">Select Company</option>
                <?php foreach ($companies as $company): ?>
                  <option value="<?php echo intval($company['id']); ?>"><?php echo htmlspecialchars($company['name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <?php endif; ?>

            <div class="mb-3">
              <button type="button" class="btn btn-primary" id="syncDidBtn">Sync DID From PBX</button>
              <span class="ml-2 text-muted" id="syncDidStatus"></span>
            </div>

            <hr>

            <div class="form-group">
              <label for="didCampaignSelect">Campaign</label>
              <select class="form-control" id="didCampaignSelect">
                <option value="">Select Campaign</option>
              </select>
            </div>

            <div class="form-group">
              <label for="outboundRuleSelect">Outbound Rule</label>
              <select class="form-control" id="outboundRuleSelect">
                <option value="">Select Outbound Rule</option>
              </select>
            </div>

            <div class="form-group">
              <label>DID Assignment</label>
              <div class="row align-items-center did-transfer-wrap">
                <div class="col-md-5">
                  <label for="availableDidSelect" class="small text-muted">Available DID</label>
                  <select class="form-control" id="availableDidSelect" multiple size="12"></select>
                </div>
                <div class="col-md-2 text-center my-3 my-md-0 did-transfer-actions">
                  <button type="button" class="btn btn-outline-primary mb-2" id="moveDidRightBtn" title="Move selected to campaign">&gt;&gt;</button>
                  <button type="button" class="btn btn-outline-secondary" id="moveDidLeftBtn" title="Remove selected from campaign">&lt;&lt;</button>
                </div>
                <div class="col-md-5">
                  <label for="selectedDidSelect" class="small text-muted">Selected For Campaign</label>
                  <select class="form-control" id="selectedDidSelect" multiple size="12"></select>
                </div>
              </div>
              <small class="form-text text-muted">Select DID(s) and use arrows to move between lists.</small>
            </div>

            <button type="button" class="btn btn-success" id="saveDidMappingBtn">Save Mapping</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<style>
.did-transfer-wrap select {
  min-height: 260px;
}

.did-transfer-actions .btn {
  min-width: 72px;
}
</style>

<script>
$(document).ready(function() {
  const isSuperAdmin = <?php echo (isset($_SESSION['prole']) && $_SESSION['prole'] == 'super_admin') ? 'true' : 'false'; ?>;
  let allDidRows = [];

  function didOptionHtml(row) {
    const trunk = row.trunk ? ` | Trunk: ${row.trunk}` : '';
    const name = row.rule_name ? ` | Rule: ${row.rule_name}` : '';
    return `<option value="${row.id}">${row.did}${trunk}${name}</option>`;
  }

  function renderDidLists(selectedIds) {
    const selectedSet = new Set((selectedIds || []).map(String));
    let availableHtml = '';
    let selectedHtml = '';

    (allDidRows || []).forEach(function(row) {
      const html = didOptionHtml(row);
      if (selectedSet.has(String(row.id))) {
        selectedHtml += html;
      } else {
        availableHtml += html;
      }
    });

    $('#availableDidSelect').html(availableHtml);
    $('#selectedDidSelect').html(selectedHtml);
  }

  function moveSelected(fromSelector, toSelector) {
    const options = $(fromSelector + ' option:selected').detach();
    $(toSelector).append(options);
  }

  function selectedCompanyId() {
    if (!isSuperAdmin) return '';
    return $('#didCompanySelect').val() || '';
  }

  function withCompany(payload) {
    if (isSuperAdmin) {
      payload.company_id = selectedCompanyId();
    }
    return payload;
  }

  function ensureCompanyForSuperAdmin() {
    if (!isSuperAdmin) return true;
    if (!selectedCompanyId()) {
      alert('Please select a company first.');
      return false;
    }
    return true;
  }

  function loadCampaigns() {
    if (!ensureCompanyForSuperAdmin()) {
      $('#didCampaignSelect').html('<option value="">Select Campaign</option>');
      return;
    }

    $.getJSON('did/get_campaigns', withCompany({}), function(rows) {
      let html = '<option value="">Select Campaign</option>';
      (rows || []).forEach(function(row) {
        html += `<option value="${row.id}">${row.name}</option>`;
      });
      $('#didCampaignSelect').html(html);
    });
  }

  function loadOutboundRules() {
    if (!ensureCompanyForSuperAdmin()) {
      $('#outboundRuleSelect').html('<option value="">Select Outbound Rule</option>');
      return;
    }

    $.getJSON('did/get_outbound_rules', withCompany({}), function(resp) {
      let html = '<option value="">Select Outbound Rule</option>';
      if (resp && resp.success && Array.isArray(resp.data)) {
        resp.data.forEach(function(rule) {
          const label = `${rule.Id} - ${rule.Name || 'Unnamed'} (Priority: ${rule.Priority !== null ? rule.Priority : '-'})`;
          html += `<option value="${rule.Id}">${label}</option>`;
        });
      }
      $('#outboundRuleSelect').html(html);
    }).fail(function(xhr) {
      const msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Failed to load outbound rules';
      alert(msg);
    });
  }

  function loadDids() {
    if (!ensureCompanyForSuperAdmin()) {
      allDidRows = [];
      renderDidLists([]);
      return;
    }

    $.getJSON('did/get_synced_dids', withCompany({}), function(rows) {
      allDidRows = Array.isArray(rows) ? rows : [];
      renderDidLists([]);

      if ($('#didCampaignSelect').val()) {
        loadCampaignMapping();
      }
    });
  }

  function loadCampaignMapping() {
    const campaignId = $('#didCampaignSelect').val();
    if (!campaignId) return;

    $.getJSON('did/get_campaign_mapping', withCompany({ campaign_id: campaignId }), function(resp) {
      if (!resp || !resp.success || !resp.data) return;

      const data = resp.data;
      if (data.outbound_rule_id) {
        $('#outboundRuleSelect').val(String(data.outbound_rule_id));
      } else {
        $('#outboundRuleSelect').val('');
      }

      renderDidLists(data.did_ids || []);
    });
  }

  $('#syncDidBtn').on('click', function() {
    if (!ensureCompanyForSuperAdmin()) return;

    $('#syncDidStatus').text('Syncing...');
    const btn = $(this).prop('disabled', true);

    $.ajax({
      url: 'did/sync_dids',
      method: 'POST',
      dataType: 'json',
      data: withCompany({}),
      success: function(resp) {
        if (resp && resp.success) {
          $('#syncDidStatus').text(`Synced ${resp.synced_count || 0} DID(s)`);
          loadDids();
        } else {
          $('#syncDidStatus').text('Sync failed');
          alert(resp && resp.message ? resp.message : 'DID sync failed');
        }
      },
      error: function(xhr) {
        $('#syncDidStatus').text('Sync failed');
        const msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'DID sync failed';
        alert(msg);
      },
      complete: function() {
        btn.prop('disabled', false);
      }
    });
  });

  $('#saveDidMappingBtn').on('click', function() {
    if (!ensureCompanyForSuperAdmin()) return;

    const campaignId = $('#didCampaignSelect').val();
    const outboundRuleId = $('#outboundRuleSelect').val();
    const didIds = $('#selectedDidSelect option').map(function() { return $(this).val(); }).get();

    if (!campaignId) {
      alert('Please select campaign');
      return;
    }
    if (!outboundRuleId) {
      alert('Please select outbound rule');
      return;
    }

    $.ajax({
      url: 'did/save_campaign_mapping',
      method: 'POST',
      dataType: 'json',
      data: withCompany({
        campaign_id: campaignId,
        outbound_rule_id: outboundRuleId,
        did_ids: didIds
      }),
      success: function(resp) {
        if (resp && resp.success) {
          alert(resp.message || 'Saved');
        } else {
          alert(resp && resp.message ? resp.message : 'Save failed');
        }
      },
      error: function() {
        alert('Server error while saving');
      }
    });
  });

  $('#didCampaignSelect').on('change', function() {
    renderDidLists([]);
    loadCampaignMapping();
  });

  $('#moveDidRightBtn').on('click', function() {
    moveSelected('#availableDidSelect', '#selectedDidSelect');
  });

  $('#moveDidLeftBtn').on('click', function() {
    moveSelected('#selectedDidSelect', '#availableDidSelect');
  });

  $('#availableDidSelect').on('dblclick', 'option', function() {
    $(this).prop('selected', true);
    moveSelected('#availableDidSelect', '#selectedDidSelect');
  });

  $('#selectedDidSelect').on('dblclick', 'option', function() {
    $(this).prop('selected', true);
    moveSelected('#selectedDidSelect', '#availableDidSelect');
  });

  if (isSuperAdmin) {
    $('#didCompanySelect').on('change', function() {
      $('#syncDidStatus').text('');
      loadCampaigns();
      loadOutboundRules();
      loadDids();
      $('#didCampaignSelect').val('');
    });
  } else {
    loadCampaigns();
    loadOutboundRules();
    loadDids();
  }
});
</script>
