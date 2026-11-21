<?php
declare(strict_types=1);
if (!defined('CONTACTS_APP')) { http_response_code(403); exit('Доступ только через index.php'); }
$rows = db()->query('SELECT id,surname,first_name,patronymic FROM contacts ORDER BY ' . nameOrder())->fetchAll();
if (!$rows) { echo '<h2>Редактирование записи</h2><p class="empty">Нет записей для редактирования. Сначала добавьте контакт.</p>'; return; }
$requestedId = filter_var(param($_GET, 'id'), FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
$selectedId = $requestedId === false ? 0 : $requestedId;
$ids = array_column($rows, 'id');
if (!in_array($selectedId, array_map('intval', $ids), true) || (param($_GET, 'id') === '' && $_SERVER['REQUEST_METHOD'] === 'POST')) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') { echo '<p class="notice error">Запись не найдена. Изменения не сохранены.</p>'; return; }
    $selectedId = (int)$rows[0]['id'];
}
$query = db()->prepare('SELECT * FROM contacts WHERE id=:id'); $query->execute(['id'=>$selectedId]);
$values = $query->fetch();
if (!$values) { echo '<p class="notice error">Запись больше не существует. Обновите страницу.</p>'; return; }
$values['birth_date'] ??= ''; $errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    [$values, $errors] = validateContact($_POST);
    if (!checkToken()) $errors[] = 'Сессия формы устарела. Обновите страницу.';
    if (!$errors) {
        try {
            $query = db()->prepare('UPDATE contacts SET surname=:surname,first_name=:first_name,patronymic=:patronymic,gender=:gender,birth_date=:birth_date,phone=:phone,address=:address,email=:email,comment=:comment WHERE id=:id');
            $query->execute([...saveParams($values), 'id'=>$selectedId]); flash('Изменения сохранены'); redirectTo('action=edit&id=' . $selectedId);
        } catch (PDOException $e) { logFailure($e); $errors[] = 'Не удалось сохранить запись.'; }
    }
}
?>
<h2>Редактирование записи</h2><p class="description">Выберите контакт, внесите изменения и сохраните их.</p>
<nav class="contact-list" aria-label="Контакты для редактирования"><?php foreach ($rows as $row): ?><a href="index.php?action=edit&amp;id=<?= (int)$row['id'] ?>" <?= (int)$row['id'] === $selectedId ? 'class="selected" aria-current="true"' : '' ?>><?= h($row['surname'] . ' ' . $row['first_name']) ?></a><?php endforeach; ?></nav>
<?php if ($errors): ?><div class="notice error" role="alert"><?php foreach ($errors as $error): ?><p><?= h($error) ?></p><?php endforeach; ?></div><?php endif; ?>
<form method="post" action="index.php?action=edit<?php if ($selectedId ?? false) echo '&amp;id=' . $selectedId; ?>" class="contact-form">
<input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
<div class="form-grid">
<label>Фамилия <span>*</span><input name="surname" value="<?= h($values['surname']) ?>" required maxlength="100" autocomplete="family-name"></label>
<label>Имя <span>*</span><input name="first_name" value="<?= h($values['first_name']) ?>" required maxlength="100" autocomplete="given-name"></label>
<label>Отчество<input name="patronymic" value="<?= h($values['patronymic']) ?>" maxlength="100" autocomplete="additional-name"></label>
<label>Пол<select name="gender"><option value="">Не указан</option><?php foreach (['male'=>'Мужской','female'=>'Женский','other'=>'Другой'] as $key=>$label): ?><option value="<?= $key ?>" <?= $values['gender'] === $key ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></label>
<label>Дата рождения<input type="date" name="birth_date" value="<?= h($values['birth_date']) ?>" min="1900-01-01" max="<?= date('Y-m-d') ?>" autocomplete="bday"></label>
<label>Телефон<input type="tel" name="phone" value="<?= h($values['phone']) ?>" maxlength="40" autocomplete="tel"></label>
<label class="wide">Адрес<input name="address" value="<?= h($values['address']) ?>" maxlength="300" autocomplete="street-address"></label>
<label>E-mail<input type="email" name="email" value="<?= h($values['email']) ?>" maxlength="254" autocomplete="email"></label>
<label class="full">Комментарий<textarea name="comment" rows="4" maxlength="5000"><?= h($values['comment']) ?></textarea></label>
</div><p class="hint">* Обязательные поля</p><button class="button" type="submit">Сохранить изменения</button></form>