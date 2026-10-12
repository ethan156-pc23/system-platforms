<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_admin();

$q = trim($_GET['q'] ?? '');
$sql = 'SELECT h.id, h.title, h.municipality, h.phone_number, h.availability, h.tag_label,
               c.slug AS category_slug, c.name AS category_name
        FROM hotlines h
        JOIN categories c ON c.id = h.category_id';
$params = [];
if ($q !== '') {
    $sql .= ' WHERE h.title LIKE ? OR h.municipality LIKE ?';
    $like = "%$q%";
    $params = [$like, $like];
}
$sql .= ' ORDER BY h.id DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$hotlines = $stmt->fetchAll();

$totalStmt = $pdo->query('SELECT COUNT(*) AS total FROM hotlines');
$total = (int) $totalStmt->fetch()['total'];

$flash = flash_get();
$pageTitle = 'Dashboard — Admin';
$pageScripts = ['/admin.js'];
include __DIR__ . '/admin-header.php';
?>
    <div class="admin-header-row">
        <div>
            <p class="admin-subtitle">Welcome, <?= e(current_user_display_name()) ?>.</p>
            <h1 class="admin-title">Hotline Directory</h1>
            <p class="admin-subtitle"><?= $total ?> hotline<?= $total === 1 ? '' : 's' ?> across Cebu Province</p>
        </div>
        <a href="<?= BASE_URL ?>/hotline-form.php" class="btn btn--primary">+ Add New Hotline</a>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert--<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>

    <form method="get" action="<?= BASE_URL ?>/dashboard.php" class="admin-search">
        <input type="search" name="q" placeholder="Search by title or city/municipality&hellip;" value="<?= e($q) ?>">
    </form>

    <?php if (!$hotlines): ?>
        <div class="admin-table-wrap">
            <p class="admin-empty">
                <?= $q !== '' ? 'No hotlines match "' . e($q) . '".' : 'No hotlines yet — add the first one above.' ?>
            </p>
        </div>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                <tr>
                    <th>Title</th>
                    <th>Category</th>
                    <th>City/Municipality</th>
                    <th>Phone Number</th>
                    <th>Availability</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($hotlines as $row): ?>
                    <tr>
                        <td class="admin-table__title"><?= e($row['title']) ?></td>
                        <td><span class="badge badge--<?= e($row['category_slug']) ?>"><?= e($row['tag_label']) ?></span></td>
                        <td><?= e($row['municipality']) ?></td>
                        <td><?= e($row['phone_number']) ?></td>
                        <td><?= e($row['availability']) ?></td>
                        <td>
                            <div class="admin-actions">
                                <a class="btn btn--ghost btn--small" href="<?= BASE_URL ?>/hotline-form.php?id=<?= (int) $row['id'] ?>">Edit</a>
                                <form method="post" action="<?= BASE_URL ?>/delete-hotline.php"
                                      class="js-delete-form" data-title="<?= e($row['title']) ?>">
                                    <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn--danger btn--small">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</main>
<?php include __DIR__ . '/footer.php'; ?>
