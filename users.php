<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_admin();

$q = trim($_GET['q'] ?? '');
$sql = 'SELECT id, mobile_number, email, role, last_seen_at, created_at FROM users';
$params = [];
if ($q !== '') {
    $sql .= ' WHERE mobile_number LIKE ? OR email LIKE ? OR role LIKE ?';
    $like = "%$q%";
    $params = [$like, $like, $like];
}
$sql .= ' ORDER BY last_seen_at DESC, created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();
$onlineCutoff = time() - 300;
$onlineCount = 0;
foreach ($users as $row) {
    if ($row['last_seen_at'] && strtotime($row['last_seen_at']) >= $onlineCutoff) {
        $onlineCount++;
    }
}

$pageTitle = 'Users — Admin';
include __DIR__ . '/admin-header.php';
?>
    <div class="admin-header-row">
        <div>
            <h1 class="admin-title">System Users</h1>
            <p class="admin-subtitle"><?= count($users) ?> registered user<?= count($users) === 1 ? '' : 's' ?> · <?= $onlineCount ?> active in the last 5 minutes</p>
        </div>
        <a href="<?= BASE_URL ?>/dashboard.php" class="btn btn--ghost">Back to dashboard</a>
    </div>

    <form method="get" action="<?= BASE_URL ?>/users.php" class="admin-search">
        <input type="search" name="q" placeholder="Search mobile, email, or role" value="<?= e($q) ?>">
    </form>

    <div class="admin-table-wrap">
        <?php if (!$users): ?>
            <p class="admin-empty">No users match your search.</p>
        <?php else: ?>
            <table class="admin-table users-table">
                <thead>
                <tr>
                    <th>Status</th>
                    <th>Mobile Number</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Last Seen</th>
                    <th>Registered</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $row):
                    $isOnline = $row['last_seen_at'] && strtotime($row['last_seen_at']) >= $onlineCutoff;
                ?>
                    <tr>
                        <td><span class="user-status <?= $isOnline ? 'user-status--online' : 'user-status--offline' ?>"><span></span><?= $isOnline ? 'Online' : 'Offline' ?></span></td>
                        <td><?= e($row['mobile_number'] ?: '—') ?></td>
                        <td><?= e($row['email'] ?: '—') ?></td>
                        <td><span class="role-badge"><?= e(ucfirst($row['role'])) ?></span></td>
                        <td><?= $row['last_seen_at'] ? e(date('M j, Y g:i A', strtotime($row['last_seen_at']))) : 'Never' ?></td>
                        <td><?= e(date('M j, Y', strtotime($row['created_at']))) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</main>
<?php include __DIR__ . '/footer.php'; ?>