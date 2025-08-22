    </main>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-chart-line me-2"></i>Smart Skill Progress Tracker</h5>
                    <p class="mb-0">Track your skills, monitor progress, and achieve your learning goals.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">
                        <small>
                            Contact Us: <br>
                            Mail: skilltraker@gmail.com<br>
                            Phone: 01234567897
                        </small>
                    </p>
                </div>
            </div>
            <hr class="my-3">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <small>&copy; <?php echo date('Y'); ?> Smart Skill Progress Tracker. All rights reserved.</small>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script src="<?php echo BASE_URL; ?>/assets/js/app.js"></script>
    
    <!-- Page-specific JavaScript -->
    <?php if (isset($pageScript)): ?>
        <script src="<?php echo BASE_URL; ?>/assets/js/<?php echo $pageScript; ?>"></script>
    <?php endif; ?>
    
    <!-- Inline JavaScript -->
    <?php if (isset($inlineScript)): ?>
        <script><?php echo $inlineScript; ?></script>
    <?php endif; ?>
</body>
</html>

