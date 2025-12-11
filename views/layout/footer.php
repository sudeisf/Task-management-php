            </main>
            <?php if (isset($currentUser) && $currentUser): ?>
                </div> <!-- Close col-md-10 -->
            <?php endif; ?>
        </div> <!-- Close row -->
    </div> <!-- Close container-fluid -->

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JavaScript (if needed in future) -->
    <script>
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            var alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                var bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

        // Confirm delete actions
        document.addEventListener('DOMContentLoaded', function() {
            var deleteButtons = document.querySelectorAll('[data-confirm-delete]');
            deleteButtons.forEach(function(button) {
                button.addEventListener('click', function(e) {
                    var message = this.getAttribute('data-confirm-delete') || 'Are you sure you want to delete this item?';
                    if (!confirm(message)) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>
</body>
</html>
