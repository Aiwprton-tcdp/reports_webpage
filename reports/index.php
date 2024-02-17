<?
try {
  require($_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php');
} catch (\Throwable $th) {
  if ($USER->getID() == 4033) {
    echo $th->getMessage();
  }
  $APPLICATION->ShowHead();
}

\Bitrix\Main\UI\Extension::load('ui.buttons');
require_once('./constants.php');

$APPLICATION->includeComponent('bitrix:main.ui.filter', '', [
  'FILTER_ID' => 'AGONTSOV_REPORT_FILTER',
  'GRID_ID' => 'AGONTSOV_REPORT_GRID',
  'FILTER' => FILTERS,
  'ENABLE_LABEL' => true,
  'ENABLE_LIVE_SEARCH' => true,
  'FILTER_PRESETS' => FILTER_PRESETS
]);

$filterOptions = new \Bitrix\Main\UI\Filter\Options('AGONTSOV_REPORT_FILTER');
$filterFields = $filterOptions->getFilter(FILTERS);
$logicFilter = \Bitrix\Main\UI\Filter\Type::getLogicFilter($filterFields, FILTERS);

$reportTypeId = $logicFilter['REPORT_TYPE_ID'];
unset($logicFilter['REPORT_TYPE_ID']);
if ($USER->getID() == 4033) {
  print_r($filterFields);
}
$users_ids = implode(',', $filterFields['ASSIGNED_BY_ID'] ?? []);
$date_from = $filterFields['DATE_MODIFY_from'];
$date_to = $filterFields['DATE_MODIFY_to'];
?>

<div style="margin: 1em">
  <a href="/agontsov/reports/generator/<? echo($reportTypeId == 1 ? 'deals/' : 'products/'); echo "?users_ids={$users_ids}&date_from={$date_from}&date_to={$date_to}"; ?>"
    target="_blank" class="ui-btn ui-btn-success ui-btn-lg ui-btn-icon-download">
    Скачать отчёт по <? echo($reportTypeId == 1 ? 'сделкам' : 'продуктам') ?>
  </a>
</div>

<?

// if ($reportTypeId == 1) {
  $data = \Bitrix\Crm\DealTable::getList([
    'select' => [ 'ID', 'TITLE', 'STAGE_ID', 'ASSIGNED_BY_ID', 'DATE_CREATE', 'DATE_MODIFY' ],
    'filter' => $logicFilter,
    'limit' => 11,
  ])->fetchAll();

  $rows = array_map(fn($k, $d) => [
    'id' => $k++,
    'columns' => $d,
  ], [1], $data);

  // $nav = new \Bitrix\Main\UI\PageNavigation("nav-less-news");
  // $nav->allowAllRecords(true)
  // 	->setPageSize(5)
  // 	->initFromUri();
    
  $APPLICATION->includeComponent('bitrix:main.ui.grid', '', [
    'GRID_ID' => 'AGONTSOV_REPORT_GRID',
    'ROWS' => empty($reportTypeId) ? [] : $rows,
    'COLUMNS' => COLUMNS,
    'AJAX_MODE' => 'Y',
    'CURRENT_PAGE' => 1,
    // 'STUB' => 'Не удалось получить данные',
    'SHOW_ROW_CHECKBOXES' => false,
    'SHOW_CHECK_ALL_CHECKBOXES' => false,
    'SHOW_PAGINATION' => true,
    'SHOW_MORE_BUTTON' => true,
    'ALLOW_COLUMNS_SORT' => false,
    'ALLOW_ROWS_SORT' => false,
  ]);

  // $APPLICATION->IncludeComponent(
  // 	"bitrix:main.pagenavigation",
  // 	"",
  // 	array(
  // 		"NAV_OBJECT" => $nav,
  // 		"SEF_MODE" => "Y",
  // 		"SHOW_COUNT" => "N",
  // 	),
  // 	false
  // );
// }

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php');
?>