<?php
declare(strict_types=1);
if (!defined('CONTACTS_APP')) { http_response_code(403); exit('Доступ только через index.php'); }
function menu(): string {
    global $action, $sort;
    $items = ['view'=>'Просмотр', 'add'=>'Добавление записи', 'edit'=>'Редактирование записи', 'delete'=>'Удаление записи'];
    $html = '<nav aria-label="Основное меню" class="main-menu">';
    foreach ($items as $key => $label) {
        $active = $key === $action;
        $html .= '<a class="menu-button' . ($active ? ' active' : '') . '" href="index.php?action=' . $key . '"' . ($active ? ' aria-current="page"' : '') . '>' . $label . '</a>';
    }
    $html .= '</nav>';
    if ($action === 'view') {
        $html .= '<nav aria-label="Сортировка контактов" class="sort-menu"><span>Сортировать</span>';
        foreach (['added'=>'По добавлению', 'surname'=>'По фамилии', 'birth'=>'По дате рождения'] as $key => $label) {
            $active = $sort === $key;
            $html .= '<a class="menu-button small' . ($active ? ' active' : '') . '" href="index.php?action=view&amp;sort=' . $key . '"' . ($active ? ' aria-current="true"' : '') . '>' . $label . '</a>';
        }
        $html .= '</nav>';
    }
    return $html;
}
