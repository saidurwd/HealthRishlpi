{{-- Per-line files (Yii's _file modal): the list and upload form load from purchaseReceive/upload/{line} --}}
<div class="modal fade" id="files-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-body"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default btn-sm" data-bs-dismiss="modal"><i class="fa fa-times"></i> Close</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('click', async (event) => {
            const button = event.target.closest('[data-files-url]');
            if (!button) {
                return;
            }
            const modal = document.getElementById('files-modal');
            const response = await fetch(button.dataset.filesUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            modal.querySelector('.modal-body').innerHTML = await response.text();
            window.bootstrap.Modal.getOrCreateInstance(modal).show();
        });
        // The line grid shows file counts: refresh it when the modal closes
        document.getElementById('files-modal').addEventListener('hidden.bs.modal', () => window.Grid.update('purchase-receive-grid'));
    </script>
@endpush
