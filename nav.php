<li><a href="vehicles.php">Vehicles</a></li>
<li><a href="reports.php">Reports</a></li>

<?php if($_SESSION['role'] == 'admin'): ?>
<li><a href="activity_log.php">Activity Log</a></li>
<?php endif; ?>
