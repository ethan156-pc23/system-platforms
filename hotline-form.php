<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_admin();

$categories = $pdo->query('SELECT id, name, slug FROM categories ORDER BY id ASC')->fetchAll();
$categories = array_map(static function (array $category): array {
    $category['id'] = (int) $category['id'];
    return $category;
}, $categories);

$id = isset($_GET['id']) ? (int) $_GET['id'] : (isset($_POST['id']) ? (int) $_POST['id'] : null);
$isEdit = $id !== null && $id > 0;

$hotline = [
    'title'         => '',
    'category_id'   => $categories[0]['id'] ?? '',
    'municipality'  => '',
    'description'   => '',
    'phone_number'  => '',
    'availability'  => '24/7',
];
$errors = [];

if (!$categories) {
    $errors[] = 'No categories found. Please import schema.sql or add categories in phpMyAdmin.';
}

if ($isEdit && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $stmt = $pdo->prepare('SELECT * FROM hotlines WHERE id = ?');
    $stmt->execute([$id]);
    $existing = $stmt->fetch();
    if (!$existing) {
        flash_set('error', 'That hotline no longer exists.');
        header('Location: ' . BASE_URL . '/dashboard.php');
        exit;
    }
    $hotline = $existing;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Your session expired. Please try again.';
    }

    $hotline = [
        'title'        => trim($_POST['title'] ?? ''),
        'category_id'  => (int) ($_POST['category_id'] ?? 0),
        'municipality' => trim($_POST['municipality'] ?? ''),
        'description'  => trim($_POST['description'] ?? ''),
        'phone_number' => trim($_POST['phone_number'] ?? ''),
        'availability' => trim($_POST['availability'] ?? '') ?: '24/7',
    ];

    $categoryIds = array_column($categories, 'id');
    if ($hotline['title'] === '' || $hotline['municipality'] === '' || $hotline['phone_number'] === '') {
        $errors[] = 'Title, city/municipality, and phone number are required.';
    }
    if (!in_array($hotline['category_id'], $categoryIds, true)) {
        $errors[] = 'Please choose a valid category.';
    }

    if (!$errors) {
        $category = array_values(array_filter($categories, fn($c) => $c['id'] === $hotline['category_id']))[0] ?? null;
        if (!$category) {
            $errors[] = 'Please choose a valid category.';
        }
    }

    if (!$errors) {
        $tagLabel = strtoupper($category['name']);

        if ($isEdit) {
            $stmt = $pdo->prepare(
                'UPDATE hotlines SET title = ?, category_id = ?, municipality = ?, description = ?,
                 phone_number = ?, availability = ?, tag_label = ? WHERE id = ?'
            );
            $stmt->execute([
                $hotline['title'], $hotline['category_id'], $hotline['municipality'], $hotline['description'],
                $hotline['phone_number'], $hotline['availability'], $tagLabel, $id,
            ]);
            flash_set('success', 'Hotline updated.');
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO hotlines (title, category_id, municipality, description, phone_number, availability, tag_label)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $hotline['title'], $hotline['category_id'], $hotline['municipality'], $hotline['description'],
                $hotline['phone_number'], $hotline['availability'], $tagLabel,
            ]);
            flash_set('success', 'Hotline added.');
        }

        header('Location: ' . BASE_URL . '/dashboard.php');
        exit;
    }
}

$pageTitle = ($isEdit ? 'Edit' : 'Add') . ' Hotline — Admin';
include __DIR__ . '/admin-header.php';
?>
    <div class="admin-header-row">
        <div>
            <h1 class="admin-title"><?= $isEdit ? 'Edit Hotline' : 'Add New Hotline' ?></h1>
            <p class="admin-subtitle">The category badge shown on the public site is generated from the category you pick below.</p>
        </div>
        <a href="<?= BASE_URL ?>/dashboard.php" class="btn btn--ghost">&larr; Back to dashboard</a>
    </div>

    <?php if ($errors): ?>
        <div class="alert alert--error"><?= e($errors[0]) ?></div>
    <?php endif; ?>

    <div class="admin-form-card">
        <form method="post" action="<?= BASE_URL ?>/hotline-form.php">
            <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int) $id ?>"><?php endif; ?>
            <?= csrf_field() ?>

            <div class="field">
                <label for="title">Title</label>
                <input type="text" id="title" name="title" placeholder="e.g. Cebu City Police (CCPO)" value="<?= e($hotline['title']) ?>" required>
            </div>

            <div class="admin-form-row">
                <div class="field">
                    <label for="category_id">Category</label>
                    <select id="category_id" name="category_id" required>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= (int) $cat['id'] ?>" <?= (int) $hotline['category_id'] === (int) $cat['id'] ? 'selected' : '' ?>>
                                <?= e(category_display_name($cat)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="municipality">City/Municipality</label>
                    <input type="text" id="municipality" name="municipality" placeholder="e.g. Cordova" value="<?= e($hotline['municipality']) ?>" required>
                </div>
            </div>

            <div class="field">
                <label for="description">Description</label>
                <textarea id="description" name="description" placeholder="Short description shown on the hotline card"><?= e($hotline['description']) ?></textarea>
            </div>

            <div class="admin-form-row">
                <div class="field">
                    <label for="phone_number">Phone Number</label>
                    <input type="text" id="phone_number" name="phone_number" placeholder="e.g. (032) 231-3232 / 166" value="<?= e($hotline['phone_number']) ?>" required>
                </div>
                <div class="field">
                    <label for="availability">Availability</label>
                    <input type="text" id="availability" name="availability" placeholder="24/7" value="<?= e($hotline['availability']) ?>">
                </div>
            </div>

            <div class="admin-form-actions">
                <button type="submit" class="btn btn--primary"><?= $isEdit ? 'Save Changes' : 'Add Hotline' ?></button>
                <a href="<?= BASE_URL ?>/dashboard.php" class="btn btn--ghost">Cancel</a>
            </div>
        </form>
    </div>
</main>
<?php include __DIR__ . '/footer.php'; ?>
