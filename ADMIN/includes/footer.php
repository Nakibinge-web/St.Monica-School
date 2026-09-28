            </div><!-- /.max-w-7xl -->
        </main>
    </div><!-- /.flex-1 -->

    <!-- Global Delete Confirmation Modal -->
    <div id="deleteConfirmModal" class="cms-modal-backdrop">
        <div class="cms-modal p-6 text-center">
            <div class="w-14 h-14 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4">
                <span class="material-symbols-outlined text-[32px]">warning</span>
            </div>
            <h3 class="text-lg font-bold text-slate-900 mb-2">Confirm Delete Action</h3>
            <p class="text-sm text-slate-600 mb-6 leading-relaxed">
                Are you sure you want to delete <strong id="deleteItemTitle" class="text-slate-900">this record</strong>? This operation is permanent and cannot be undone.
            </p>
            <form id="deleteConfirmForm" method="POST" action="" class="flex items-center justify-center gap-3">
                <?= csrf_field() ?>
                <button type="button" id="deleteCancelBtn" class="cms-btn cms-btn-outline w-1/2">
                    Cancel
                </button>
                <button type="submit" class="cms-btn cms-btn-danger w-1/2">
                    Delete
                </button>
            </form>
        </div>
    </div>

    <!-- Admin Script -->
    <script src="<?= admin_url('assets/js/admin.js') ?>"></script>
    <script src="<?= admin_url('assets/js/media-picker.js') ?>"></script>
</body>
</html>
