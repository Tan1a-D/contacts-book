<?php
declare(strict_types=1);
if (!defined('CONTACTS_APP')) { http_response_code(403); exit('Доступ только через index.php'); }
function viewer(string $sort, int $page): string {
    $db = db();
    $total = (int)$db->query('SELECT COUNT(*) FROM contacts')->fetchColumn();
    $pages = max(1, (int)ceil($total / 10)); $page = max(1, min($pages, $page));
    $orders = ['added'=>'id ASC', 'surname'=>nameOrder(), 'birth'=>'CASE WHEN birth_date IS NULL THEN 1 ELSE 0 END, birth_date ASC, id ASC'];
    $sort = isset($orders[$sort]) ? $sort : 'added';
    $query = $db->prepare('SELECT * FROM contacts ORDER BY ' . $orders[$sort] . ' LIMIT :limit OFFSET :offset');
    $query->bindValue(':limit', 10, PDO::PARAM_INT); $query->bindValue(':offset', ($page-1)*10, PDO::PARAM_INT); $query->execute();
    $rows = $query->fetchAll();
    $html = '<div class="section-heading"><h2>Все контакты</h2><span>' . $total . ' записей · страница ' . $page . ' из ' . $pages . '</span></div>';
    if (!$rows) return $html . '<div class="empty"><h3>Здесь будут ваши контакты</h3><p>Добавьте первую запись в записную книжку.</p><a class="button" href="index.php?action=add">Добавить контакт</a></div>';
    $html .= '<div class="table-scroll" tabindex="0" role="region" aria-label="Таблица контактов"><table><thead><tr>';
    foreach (['Фамилия','Имя','Отчество','Пол','Дата рождения','Телефон','Адрес','E-mail','Комментарий'] as $label) $html .= '<th scope="col">' . $label . '</th>';
    $html .= '</tr></thead><tbody>';
    foreach ($rows as $row) {
        $gender = ['male'=>'Мужской','female'=>'Женский','other'=>'Другой'][$row['gender']] ?? '—';
        $date = $row['birth_date'] ? date('d.m.Y', strtotime($row['birth_date'])) : '—';
        $html .= '<tr>';
        foreach ([$row['surname'],$row['first_name'],$row['patronymic'],$gender,$date,$row['phone'],$row['address'],$row['email'],$row['comment']] as $value) $html .= '<td>' . nl2br(h($value === '' ? '—' : $value)) . '</td>';
        $html .= '</tr>';
    }
    $html .= '</tbody></table></div>';
    if ($pages > 1) {
        $html .= '<nav class="pagination" aria-label="Страницы контактов">';
        for ($i=1; $i<=$pages; $i++) $html .= '<a' . ($i === $page ? ' class="current" aria-current="page"' : '') . ' href="index.php?action=view&amp;sort=' . $sort . '&amp;page=' . $i . '">' . $i . '</a>';
        $html .= '</nav>';
    }
    return $html;
}
