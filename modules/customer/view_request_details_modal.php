<!-- View Modal for Request Details -->
<div class="modal fade" id="viewModal<?= $request['id'] ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-file-text me-2"></i>
                    Request Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="mb-3">
                    <label class="text-muted small">Subject</label>
                    <p class="fw-semibold"><?= htmlspecialchars($request['subject']) ?></p>
                </div>

                <div class="row mb-3">
                    <div class="col-6">
                        <label class="text-muted small">Type</label>
                        <p><?= ucfirst($request['type']) ?></p>
                    </div>
                    <div class="col-6">
                        <label class="text-muted small">Priority</label>
                        <p>
                            <span class="badge bg-<?= getPriorityBadgeClass($request['priority']) ?>">
                                <?= ucfirst($request['priority']) ?>
                            </span>
                        </p>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-6">
                        <label class="text-muted small">Status</label>
                        <p>
                            <span class="badge bg-<?= getStatusBadgeClass($request['status']) ?>">
                                <?= ucfirst($request['status']) ?>
                            </span>
                        </p>
                    </div>
                    <div class="col-6">
                        <label class="text-muted small">Date Created</label>
                        <p><?= formatDate($request['created_at']) ?></p>
                    </div>
                </div>

                <hr>

                <div class="mb-3">
                    <label class="text-muted small">Message</label>
                    <div class="bg-light p-3 rounded">
                        <?= nl2br(htmlspecialchars($request['message'])) ?>
                    </div>
                </div>

                <?php if($request['status'] == 'resolved' && !empty($request['resolution_notes'])): ?>
                    <div class="mb-3">
                        <label class="text-muted small">Resolution Notes</label>
                        <div class="bg-success bg-opacity-10 p-3 rounded">
                            <?= nl2br(htmlspecialchars($request['resolution_notes'])) ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x me-2"></i>
                    Close
                </button>
            </div>
        </div>
    </div>
</div>