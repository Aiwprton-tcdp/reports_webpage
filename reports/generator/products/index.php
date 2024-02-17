<?php
try {
  require($_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php');
} catch (\Throwable $th) {
  echo $th->getMessage();
  $APPLICATION->ShowHead();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/agontsov/reports/classes/product_trait.php';

use Bitrix\Main\Application;

\Bitrix\Main\Loader::includeModule('crm');


function initReport($pt): void
{
  // $deals = $pt->deals['PRODUCTS'];
  // print_r($deals);
  // print_r(json_encode($deals));
  // getProducts();
  createFile($pt);
}



function createFile($pt): void
{
  $fileName = 'products_report.xlsx';
  $xlsx = new \Kokoc\Crm\Reports\SimpleXLSXGen();

  $xlsx->addSheet(createDataTemplateMain($pt), 'Единый отчёт');
  $xlsx->setColWidth(2, 30);

  foreach ($pt->deals['PRODUCTS'] as $PRODUCT) {
    if (empty($pt->deals['DEALS'][$PRODUCT['ID']])) continue;

    $name = mb_substr(
      \Cutil::translit(
        (string)$PRODUCT['NAME'],
        'ru',
        [ 'replace_space' => ' ', 'replace_other' => '-', 'change_case' => false]
      ), 0, 31);
    $xlsx->addSheet(createDataTemplatePage($pt, $PRODUCT['ID']), $name);
    $xlsx->setColWidth(2, 30);
  }

  $xlsx->saveAs($fileName);
  str_replace(Application::getDocumentRoot(), '', $fileName);

  header ("Location: ./{$fileName}");
  exit();
}

function createDataTemplateMain($pt): array
{
  $listData = [[
    'Название продукта',
    'БЮ Продукта',
    'Кол-во сделок в стадии "Обработка"',
    'Кол-во сделок в стадии "Выявление потребности"',
    'Кол-во сделок в стадии "Брифинг"',
    'Кол-во сделок в стадии "Подготовка стратегии / КП"',
    'Кол-во сделок в стадии "Стратегия / КП - отправлена"',
    'Кол-во сделок в стадии "Переговоры по КП"',
    'Кол-во сделок в стадии "Ожидание решения"',
    'Кол-во сделок в стадии "Заключение договора"',
    'Кол-во сделок в стадии "Договор подписан"',
    'Кол-во сделок в стадии "Передача на аккаунтинг"',
    'Кол-во сделок в стадиях Архив и Завершение сотрудничества',
    'ВСЕГО',
  ]];

  foreach ($pt->deals['PRODUCTS'] as $PRODUCT) {
    $totalDealsCount = 0;
    $productID = $PRODUCT['ID'];
    if (empty($pt->deals['DEALS'][$productID])) {
      continue;
    }
    $finalStatusCount = 0;
    $finalStatusStages = [
      'C22:APOLOGY',
      'C22:LOSE',
    ];
    foreach ($finalStatusStages as $stageId) {
      if (is_array($pt->deals['DEALS_BY_STAGES'][$stageId][$productID])) {
        $finalStatusCount += count($pt->deals['DEALS_BY_STAGES'][$stageId][$productID]);
      }
    }
    $allStatusStages = [
      'C22:UC_GR0G8F',
      'C22:UC_44PJ08',
      'C22:NEW',
      'C22:PREPARATION',
      'C22:PREPAYMENT_INVOIC',
      'C22:EXECUTING',
      'C22:FINAL_INVOICE',
      'C22:UC_QP7BZQ',
      'C22:UC_XJPR5R',
      'C22:UC_4Z82CF',
      'C22:APOLOGY',
      'C22:LOSE',
    ];
    foreach ($allStatusStages as $stageId) {
      if (is_array($pt->deals['DEALS_BY_STAGES'][$stageId][$productID])) {
        $totalDealsCount += count($pt->deals['DEALS_BY_STAGES'][$stageId][$productID]);
      }
    }
    $listString = [
      $PRODUCT['NAME'],
      $PRODUCT['UNIT_SECTION']['NAME'],
      (isset($pt->deals['DEALS_BY_STAGES']['C22:UC_GR0G8F'][$productID]) ? count($pt->deals['DEALS_BY_STAGES']['C22:UC_GR0G8F'][$productID]) : 0),
      (isset($pt->deals['DEALS_BY_STAGES']['C22:UC_44PJ08'][$productID]) ? count($pt->deals['DEALS_BY_STAGES']['C22:UC_44PJ08'][$productID]) : 0),
      (isset($pt->deals['DEALS_BY_STAGES']['C22:NEW'][$productID]) ? count($pt->deals['DEALS_BY_STAGES']['C22:NEW'][$productID]) : 0),
      (isset($pt->deals['DEALS_BY_STAGES']['C22:PREPARATION'][$productID]) ? count($pt->deals['DEALS_BY_STAGES']['C22:PREPARATION'][$productID]) : 0),
      (isset($pt->deals['DEALS_BY_STAGES']['C22:PREPAYMENT_INVOIC'][$productID]) ? count($pt->deals['DEALS_BY_STAGES']['C22:PREPAYMENT_INVOIC'][$productID]) : 0),
      (isset($pt->deals['DEALS_BY_STAGES']['C22:EXECUTING'][$productID]) ? count($pt->deals['DEALS_BY_STAGES']['C22:EXECUTING'][$productID]) : 0),
      (isset($pt->deals['DEALS_BY_STAGES']['C22:FINAL_INVOICE'][$productID]) ? count($pt->deals['DEALS_BY_STAGES']['C22:FINAL_INVOICE'][$productID]) : 0),
      (isset($pt->deals['DEALS_BY_STAGES']['C22:UC_QP7BZQ'][$productID]) ? count($pt->deals['DEALS_BY_STAGES']['C22:UC_QP7BZQ'][$productID]) : 0),
      (isset($pt->deals['DEALS_BY_STAGES']['C22:UC_XJPR5R'][$productID]) ? count($pt->deals['DEALS_BY_STAGES']['C22:UC_XJPR5R'][$productID]) : 0),
      (isset($pt->deals['DEALS_BY_STAGES']['C22:UC_4Z82CF'][$productID]) ? count($pt->deals['DEALS_BY_STAGES']['C22:UC_4Z82CF'][$productID]) : 0),
      $finalStatusCount,
      $totalDealsCount,
    ];
    $listData[] = $listString;
  }

  return $listData;
}

function createDataTemplatePage($pt, $productID): array
{
  $arHeadersAll = [
    $pt->deals['PRODUCTS'][$productID]['NAME'],
    'БЮ ответственного',
    'Кол-во сделок в стадии "Обработка"',
    'Кол-во сделок в стадии "Выявление потребности"',
    'Кол-во сделок в стадии "Брифинг"',
    'Кол-во сделок в стадии "Подготовка стратегии / КП"',
    'Кол-во сделок в стадии "Стратегия / КП - отправлена"',
    'Кол-во сделок в стадии "Переговоры по КП"',
    'Кол-во сделок в стадии "Ожидание решения"',
    'Кол-во сделок в стадии "Заключение договора"',
    'Кол-во сделок в стадии "Договор подписан"',
    'Кол-во сделок в стадии "Передача на аккаунтинг"',
    'Кол-во сделок в стадиях Архив и Завершение сотрудничества',
    'ВСЕГО',
  ];
  $listData = [
    $arHeadersAll
  ];

  foreach ($pt->deals['DEALS_BY_RESPONSIBLE'][$productID] as $userID => $stagesArr) {

    $totalDealsCount = 0;
    $finalStatusCount = 0;
    $finalStatusStages = [
      'C22:APOLOGY',
      'C22:LOSE',
    ];
    foreach ($finalStatusStages as $stageId) {
      if (is_array($stagesArr[$stageId])) {
        $finalStatusCount += count($stagesArr[$stageId]);
      }
    }
    $allStatusStages = [
      'C22:UC_GR0G8F',
      'C22:UC_44PJ08',
      'C22:NEW',
      'C22:PREPARATION',
      'C22:PREPAYMENT_INVOIC',
      'C22:EXECUTING',
      'C22:FINAL_INVOICE',
      'C22:UC_QP7BZQ',
      'C22:UC_XJPR5R',
      'C22:UC_4Z82CF',
      'C22:APOLOGY',
      'C22:LOSE',
    ];
    foreach ($allStatusStages as $stageId) {
      if (isset($stagesArr[$stageId])) {
        $totalDealsCount += count($stagesArr[$stageId]);
      }
    }
    $listString = [
      $pt->users[$userID]['LAST_NAME'].' '.$pt->users[$userID]['NAME'].' '.$pt->users[$userID]['SECOND_NAME'],
      $pt->users[$userID]['UNIT_SECTION']['NAME'],
      (isset($stagesArr['C22:UC_GR0G8F']) ? count($stagesArr['C22:UC_GR0G8F']) : 0),
      (isset($stagesArr['C22:UC_44PJ08']) ? count($stagesArr['C22:UC_44PJ08']) : 0),
      (isset($stagesArr['C22:NEW']) ? count($stagesArr['C22:NEW']) : 0),
      (isset($stagesArr['C22:PREPARATION']) ? count($stagesArr['C22:PREPARATION']) : 0),
      (isset($stagesArr['C22:PREPAYMENT_INVOIC']) ? count($stagesArr['C22:PREPAYMENT_INVOIC']) : 0),
      (isset($stagesArr['C22:EXECUTING']) ? count($stagesArr['C22:EXECUTING']) : 0),
      (isset($stagesArr['C22:FINAL_INVOICE']) ? count($stagesArr['C22:FINAL_INVOICE']) : 0),
      (isset($stagesArr['C22:UC_QP7BZQ']) ? count($stagesArr['C22:UC_QP7BZQ']) : 0),
      (isset($stagesArr['C22:UC_XJPR5R']) ? count($stagesArr['C22:UC_XJPR5R']) : 0),
      (isset($stagesArr['C22:UC_4Z82CF']) ? count($stagesArr['C22:UC_4Z82CF']) : 0),
      $finalStatusCount,
      $totalDealsCount,
    ];
    $listData[] = $listString;
  }

  return $listData;
}



$users_ids = explode(',', $_GET['users_ids']);
$date_from = $_GET['date_from'];
$date_to = $_GET['date_to'];
$pt = new ProductTrait($date_from, $date_to, $users_ids);

initReport($pt);


require($_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php');
?>