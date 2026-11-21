<?php
declare(strict_types=1);
if (!defined('CONTACTS_APP')) { http_response_code(403); exit('Доступ только через index.php'); }
// Deletion by a link follows the assignment. The session token rejects foreign requests.
if (param($_GET, 'id') !== '') {
    if (!hash_equals($_SESSION['csrf'], param($_GET, 'token'))) { flash('Не удалось удалить запись: недействительная ссылка.', false); redirectTo('action=delete'); }
    $id = filter_var(param($_GET, 'id'), FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
    if ($id === false) { flash('Некорректный номер записи.', false); redirectTo('action=delete'); }
    try {
        db()->beginTransaction();
        $query = db()->prepare('SELECT surname FROM contacts WHERE id=:id'); $query->execute(['id'=>$id]); $surname = $query->fetchColumn();
        if ($surname !== false) {
            $query = db()->prepare('DELETE FROM contacts WHERE id=:id'); $query->execute(['id'=>$id]);
            db()->commit(); flash('Запись с фамилией ' . $surname . ' удалена');
        } else { db()->rollBack(); flash('Запись уже удалена или не найдена.', false); }
    } catch (PDOException $e) { if (db()->inTransaction()) db()->rollBack(); logFailure($e); flash('Не удалось удалить запись.', false); }
    redirectTo('action=delete');
}
$rows = db()->query('SELECT id,surname,first_name,patronymic FROM contacts ORDER BY ' . nameOrder())->fetchAll();
?>
<h2>Удаление записи</h2><p class="description">Нажатие на имя удаляет контакт из записной книжки.</p>
<?php if (!$rows): ?><div class="empty">Нет записей для удаления.</div><?php else: ?><div class="delete-list"><?php foreach ($rows as $row): $initials = mb_substr($row['first_name'],0,1) . '.' . ($row['patronymic'] !== '' ? ' ' . mb_substr($row['patronymic'],0,1) . '.' : ''); ?><a href="index.php?action=delete&amp;id=<?= (int)$row['id'] ?>&amp;token=<?= h($_SESSION['csrf']) ?>"><?= h($row['surname'] . ' ' . $initials) ?></a><?php endforeach; ?></div><?php endif; ?>
