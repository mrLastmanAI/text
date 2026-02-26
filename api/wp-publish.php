<?php
/**
 * POST /api/wp-publish.php
 *
 * Публикация статьи в WordPress через WP REST API.
 *
 * Действия:
 *   { "action": "publish",  "title": "...", "content": "...", "status": "draft|publish", "categories": [1] }
 *   { "action": "categories" }  — получить список категорий
 *
 * Output (publish):
 *   { "id": 123, "link": "https://site.com/...", "status": "draft" }
 *
 * Output (categories):
 *   { "categories": [ { "id": 1, "name": "..." }, ... ] }
 */

require_once __DIR__ . '/helpers.php';

$input = getJsonInput();
$action = $input['action'] ?? 'publish';

if ($action === 'categories') {
    // ── Получить список категорий ────────────────────────────────
    $cats = wpGetCategories();

    $result = array_map(function ($cat) {
        return [
            'id'   => $cat['id'],
            'name' => $cat['name'],
            'slug' => $cat['slug'],
        ];
    }, $cats);

    sendJson(['categories' => $result]);

} elseif ($action === 'publish') {
    // ── Публикация / создание поста ──────────────────────────────

    requireFields($input, ['title', 'content']);

    $title      = $input['title'];
    $content    = $input['content'];
    $status     = in_array($input['status'] ?? '', ['draft', 'publish', 'pending']) ? $input['status'] : 'draft';
    $categories = $input['categories'] ?? [];
    $tags       = $input['tags'] ?? [];
    $postId     = isset($input['post_id']) ? (int) $input['post_id'] : null;

    $postData = [
        'title'   => $title,
        'content' => $content,
        'status'  => $status,
    ];

    if (!empty($categories)) {
        $postData['categories'] = array_map('intval', $categories);
    }
    if (!empty($tags)) {
        $postData['tags'] = array_map('intval', $tags);
    }

    $wpPost = wpPublish($postData, $postId);

    sendJson([
        'id'     => $wpPost['id'] ?? null,
        'link'   => $wpPost['link'] ?? '',
        'status' => $wpPost['status'] ?? $status,
        'title'  => $wpPost['title']['rendered'] ?? $title,
    ]);

} else {
    sendError('Неизвестное действие: ' . $action, 400);
}
