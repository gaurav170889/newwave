
<div class="container-fluid p-0">

    <div class="row mb-2 mb-xl-3">
        <div class="col-auto d-none d-sm-block">
            <h3><strong>Disposition</strong> Dashboard</h3>
        </div>

        <div class="col-auto ml-auto text-right mt-n1">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb bg-transparent p-0 mt-1 mb-0">
                    <li class="breadcrumb-item"><a href="#">3cx Addons</a></li>
                    <li class="breadcrumb-item"><a href="#">Disposition</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Disposition List</h5>
                    <h6 class="card-subtitle text-muted">Manage your call dispositions here.</h6>
                    <button class="btn btn-primary float-right" data-toggle="modal" data-target="#addDispositionModal">Add Disposition</button>
                </div>
                <div class="card-body">
                    <table id="dispositionTable" class="table table-striped" style="width:100%">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Action Type</th>
                                <th>Color</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($data as $row): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><?php echo $row['code']; ?></td>
                                <td><?php echo $row['label']; ?></td>
                                <td><?php echo $row['action_type']; ?></td>
                                <td>
                                    <span class="badge badge-pill" style="background-color: <?php echo $row['color_code'] ?? '#808080'; ?>; color: #fff;">
                                        <?php echo $row['color_code'] ?? '#808080'; ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-info edit-btn" 
                                            data-id="<?php echo $row['id']; ?>" 
                                            data-code="<?php echo $row['code']; ?>" 
                                            data-name="<?php echo $row['label']; ?>" 
                                            data-action="<?php echo $row['action_type']; ?>"
                                            data-color="<?php echo $row['color_code'] ?? '#808080'; ?>">Edit</button>
                                    <button class="btn btn-sm btn-danger delete-btn" data-id="<?php echo $row['id']; ?>">Delete</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Add Modal -->
<div class="modal fade" id="addDispositionModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Disposition</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="addDispositionForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Code</label>
                        <input type="text" class="form-control" name="code" required>
                    </div>
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="form-group">
                        <label>Action Type</label>
                        <select class="form-control" name="action_type" required>
                            <option value="CLOSE">CLOSE</option>
                            <option value="CALLBACK">CALLBACK</option>
                            <option value="RETRY">RETRY</option>
                            <option value="DROP_VM">DROP_VM</option>
                            <option value="INVALID">INVALID</option>
                            <option value="GLOBAL_DNC">GLOBAL_DNC</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Color Code</label>
                        <input type="color" class="form-control" name="color_code" value="#808080">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editDispositionModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Disposition</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="editDispositionForm">
                <div class="modal-body">
                    <input type="hidden" name="dataval" id="edit_id">
                    <div class="form-group">
                        <label>Code</label>
                        <input type="text" class="form-control" name="code" id="edit_code" required>
                    </div>
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" class="form-control" name="name" id="edit_name" required>
                    </div>
                    <div class="form-group">
                        <label>Action Type</label>
                        <select class="form-control" name="action_type" id="edit_action" required>
                            <option value="CLOSE">CLOSE</option>
                            <option value="CALLBACK">CALLBACK</option>
                            <option value="RETRY">RETRY</option>
                            <option value="DROP_VM">DROP_VM</option>
                            <option value="INVALID">INVALID</option>
                            <option value="GLOBAL_DNC">GLOBAL_DNC</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Color Code</label>
                        <input type="color" class="form-control" name="color_code" id="edit_color" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
