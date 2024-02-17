<?php
try {
  require($_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php');
} catch (\Throwable $th) {
  echo $th->getMessage();
  $APPLICATION->ShowHead();
}

use Bitrix\Main\Application;
// use Bitrix\Main\IO\Directory;

\Bitrix\Main\Loader::includeModule('crm');


function initReport($year, $quarter): void
{
  // получение записей истории изменений стадий
  $history = \Bitrix\Crm\History\Entity\DealStageHistoryTable::getList([
    'select' => [ 'ID', 'STAGE_ID', 'OWNER_ID', 'CREATED_TIME' ],
    'filter' => [
      '=TYPE_ID' => 2,
      '=CATEGORY_ID' => '22',
      '=PERIOD_YEAR' => $year,
      '=PERIOD_QUARTER' => $quarter,
    ],
    'order' => [ 'ID' => 'DESC' ],
  ])->fetchAll();

  // получение сделок
  $historyOwners = array_map(fn($h) => $h['OWNER_ID'], $history);
  $deals = \Bitrix\Crm\DealTable::getList([
    'filter' => [ 'ID' => $historyOwners ],
    'select' => [ 'ID', 'TITLE', 'STAGE_ID', 'ASSIGNED_BY_ID', 'DATE_CREATE', 'DATE_MODIFY' ],
  ])->fetchAll();

  $data = array_reduce($deals, function ($result, $item) {
    $item = (array) $item;
    $result[$item['ID']] = $item;
    return $result;
  }, []);

  // получение данных о пользователях
  $u = \Bitrix\Main\UserTable::getList([
    'select' => [ 'ID', 'NAME', 'LAST_NAME', 'UF_DEPARTMENT' ],
    'filter' => [ 'ID' => array_map(fn($d) => $d['ASSIGNED_BY_ID'], $deals)]
  ])->fetchAll();
  unset($deals);
  $users = array_reduce($u, function ($result, $item) {
    $item = (array) $item;
    $result[$item['ID']] = $item;
    return $result;
  }, []);
  unset($u);

  // получение данных о подразделениях
  $d = \Bitrix\Main\UserUtils::getDepartmentNames(
    array_map(fn($u) => $u['UF_DEPARTMENT'][0], $users)
  );

  // получение данных о стадиях воронки
  $stages = \Bitrix\Crm\Category\DealCategory::getStageList(22);

  // форматирование данных для визуализации
  foreach($history as $h)
  {
    $current = $data[$h['OWNER_ID']];
    $current['Название'] ??= $current['TITLE'];
    $current['Текущая стадия'] ??= $stages[$h['STAGE_ID']];
    $user = $users[$current['ASSIGNED_BY_ID']];
    $current['Ответственный'] ??= "{$user['NAME']} {$user['LAST_NAME']}";
    $department = selectDepartment($user['UF_DEPARTMENT'], $d);
    $current['Бизнес-юнит'] ??= $department;
    $current['Дата создания'] ??= $current['DATE_CREATE']->toString();
    $current['Дата последнего изменения'] ??= $current['DATE_MODIFY']->toString();
    $current[$stages[$h['STAGE_ID']]] ??= $h['CREATED_TIME']->toString();

    $data[$h['OWNER_ID']] = array_merge((array) $data[$h['OWNER_ID']], (array) $current);
  }

  // результат сконвертировать в .xlsx
  // print_r(json_encode(array_values($data)));
  createFile($data);
}

function selectDepartment($current, $all): string | null
{
	$filtered = array_values(array_filter($all, fn($a) => in_array($a['ID'], $current)));
	$filtered3plus = array_values(array_filter($filtered, fn($f) => 2 < $f['DEPTH_LEVEL']));
	$selected = $filtered3plus[0]['NAME'] ?? $filtered[0]['NAME'];
	return $selected;
}

function prepareData($data): array
{
  $headNames = array_keys(array_merge(...$data));
  $clearHeadNames = array_values(array_filter(
    $headNames,
    fn($h) => $h == 'ID' || !preg_match('/[A-z]+/', $h)
  ));

  $count = count($data);
  for ($i = 0; $i < $count; $i++) {
    $t = [];
    foreach ($clearHeadNames as $c) {
      $t[$c] = $data[$i][$c] ?? '';
    }
    $data[$i] = $t;
  }

  $arrayedData = array_values(array_map(fn($d) => array_values($d), $data));
  array_unshift($arrayedData, $clearHeadNames);
  return $arrayedData;
}

function createFile($data)
{
  $prepared = prepareData($data);
  $fileName = 'deals_report.xlsx';
  $xlsx = new \Kokoc\Crm\Reports\SimpleXLSXGen();

  $xlsx->addSheet($prepared, 'Общее');
  $xlsx->setColWidth(2, 30);

  $xlsx->saveAs($fileName);

  str_replace(Application::getDocumentRoot(), '', $fileName);

  header ("Location: ./{$fileName}");
  exit();
}


// инициализация входных данных
$year = 2023;
$quarter = 4;

initReport($year, $quarter);


require($_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php');
?>