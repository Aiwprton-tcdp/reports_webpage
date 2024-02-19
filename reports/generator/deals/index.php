<?php
try {
  require($_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php');
} catch (\Throwable $th) {
  echo $th->getMessage();
  $APPLICATION->ShowHead();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/agontsov/reports/classes/deal_trait.php';

use Bitrix\Main\Application;

\Bitrix\Main\Loader::includeModule('crm');


function initReport($cl): void
{
  $heads = [
    'Удалённые стадии',
    'Новых Лидов',
    'Новых сделок',
    'CR из Лида в "Новый Клиент", %',
    'CR из Лида в "Договор подписан", %',
    'CR из "Брифинг" в "Договор подписан", %'
  ];
  $stages = array_map(fn($s) => "Сделок в стадии \"{$s}\"", $cl->stages);
  array_unshift($stages, 'Ответственный', 'Бизнес-юнит');
  array_push($stages, ...$heads);

  $results = $cl->results;
  $sums = countSums($results);
  array_unshift($results, array_values($stages));
  array_push($results, $sums);
  
  createFile($results);
}

function countSums($data)
{
  $sums = ['Суммарно'];
  foreach ($data as $rows) {
    foreach ($rows as $key => $value) {
      $sums[$key] ??= null;
      if (gettype($value) == 'integer') {
        $sums[$key] ??= 0;
        $sums[$key] += $value;
      }
    }
  }
  return $sums;
}

function createFile($data)
{
  $fileName = 'deals_report.xlsx';
  $xlsx = new \Kokoc\Crm\Reports\SimpleXLSXGen();

  $xlsx->addSheet($data, 'Общее');
  $xlsx->setColWidth(2, 30);
  $xlsx->saveAs($fileName);

  str_replace(Application::getDocumentRoot(), '', $fileName);

  header ("Location: ./{$fileName}");
  exit();
}


$users_ids = explode(',', $_GET['users_ids']);
$date_from = $_GET['date_from'];
$date_to = $_GET['date_to'];
$cl = new ReportTrait($date_from, $date_to, $users_ids);

// $stages = $cl->stages;
// print_r(json_encode($stages));
// $deals_real_stages = array_map(fn($s) => $s['STAGE_ID'], $cl->deals);
// print_r(json_encode(array_values(array_unique($deals_real_stages))));
// return;
initReport($cl);


require($_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php');
?>