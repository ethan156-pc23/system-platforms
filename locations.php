<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_admin();

$stmt = $pdo->query(
    'SELECT lr.*, h.title, h.phone_number, u.mobile_number, u.email
     FROM location_requests lr
     JOIN hotlines h ON h.id = lr.hotline_id
     LEFT JOIN users u ON u.id = lr.user_id
     ORDER BY lr.created_at DESC
     LIMIT 500'
);
$requests = $stmt->fetchAll();
$includeMap = true;
$pageTitle = 'Location Requests — Admin';
include __DIR__ . '/admin-header.php';
?>
    <div class="admin-header-row">
        <div>
            <h1 class="admin-title">Location Requests</h1>
            <p class="admin-subtitle">Locations shared by people requesting emergency help.</p>
        </div>
        <a href="<?= BASE_URL ?>/dashboard.php" class="btn btn--ghost">Back to dashboard</a>
    </div>

    <div id="locationMap" class="location-map"></div>
    <div class="admin-table-wrap">
        <?php if (!$requests): ?>
            <p class="admin-empty">No locations have been shared yet.</p>
        <?php else: ?>
            <table class="admin-table locations-table">
                <thead><tr><th>Status</th><th>Hotline</th><th>Caller</th><th>Coordinates</th><th>Details</th><th>Shared</th></tr></thead>
                <tbody>
                <?php foreach ($requests as $request): ?>
                    <tr>
                        <td><span class="role-badge"><?= e(ucfirst($request['status'])) ?></span></td>
                        <td class="admin-table__title"><?= e($request['title']) ?><br><small><?= e($request['phone_number']) ?></small></td>
                        <td><?= e($request['mobile_number'] ?: ($request['email'] ?: 'Guest')) ?></td>
                        <td><?= e($request['latitude']) ?>, <?= e($request['longitude']) ?></td>
                        <td><?= e($request['help_description'] ?: '—') ?></td>
                        <td><?= e(date('M j, Y g:i A', strtotime($request['created_at']))) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <script>
        var locationRequests = <?= json_encode($requests, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        var map = L.map('locationMap').setView([10.3157, 123.8854], 9);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap contributors' }).addTo(map);
        locationRequests.forEach(function (request) {
            L.marker([request.latitude, request.longitude]).addTo(map).bindPopup('<strong>' + request.title + '</strong><br>' + (request.help_description || 'No details') + '<br>' + request.created_at);
        });
    </script>
</main>
<?php include __DIR__ . '/footer.php'; ?>