<?php
declare(strict_types=1);
if (!defined('CONTACTS_APP')) { http_response_code(403); exit('Доступ только через index.php'); }
$values = blankContact(); $errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    [$values, $errors] = validateContact($_POST);
    if (!checkToken()) $errors[] = 'Сессия формы устарела. Обновите страницу.';
    if (!$errors) {
        try {
            $query = db()->prepare('INSERT INTO contacts (surname,first_name,patronymic,gender,birth_date,phone,address,email,comment) VALUES (:surname,:first_name,:patronymic,:gender,:birth_date,:phone,:address,:email,:comment)');
            $query->execute(saveParams($values)); flash('Запись добавлена'); redirectTo('action=add');
        } catch (PDOException $e) { logFailure($e); $errors[] = 'Попробуйте ещё раз.'; }
    }
}
?>
<h2>Добавление записи</h2><p class="description">Сохраните контакт и удобный способ связи.</p>
<?php if ($errors): ?><div class="notice error" role="alert"><strong>Ошибка: запись не добавлена</strong><?php foreach ($errors as $error): ?><p><?= h($error) ?></p><?php endforeach; ?></div><?php endif; ?>
<form method="post" action="index.php?action=add<?php if ($selectedId ?? false) echo '&amp;id=' . $selectedId; ?>" class="contact-form">
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
</div><p class="hint">* Обязательные поля</p><button class="button" type="submit">Добавить запись</button></form>