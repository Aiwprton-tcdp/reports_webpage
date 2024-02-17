<?php

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php');
use Bitrix\Main\Application;
\Bitrix\Main\Loader::includeModule('crm');

class ProductTrait
{
  private $date_from = null;
  private $date_to = null;
  private $category_id = null;
  private $users_ids = [];
  public $deals = [];
  public $stages = [];
  private $deals_by_users_counts = [];
  public $users = [];
  private $history = [];

  public $results = [];


  public function __construct($date_from, $date_to, $users_ids, $category_id = '22')
  {
    $this->date_from = new \Bitrix\Main\Type\DateTime($date_from ?? 0);
    $this->date_to = new \Bitrix\Main\Type\DateTime($date_to ?? null);
    $this->users_ids = $users_ids;
    $this->category_id = $category_id;

    $this->getDeals();
    $this->getProducts();
    // $this->createFile();
  }


  private function getDeals(): void
  {
    $filter = [
      // '>=DATE_CREATE' => $date_from,
      // '<=DATE_CREATE' => $date_to,
      '!UF_KIT_PRODUCTS' => 'false',
    ];

    $deals = \Bitrix\Crm\DealTable::getList([
      'filter' => $filter,
      'select' => [
        'ID', 'TITLE', 'ASSIGNED_BY_ID', 'STAGE_ID',
        'STAGE_SEMANTIC_ID', 'UF_KIT_PRODUCTS'
      ],
    ])->fetchAll();


    $this->deals = [
      'DEALS_DATA' => [],
      'DEALS' => [],
      'DEALS_BY_STAGES' => [],
      'DEALS_BY_RESPONSIBLE' => [],
    ];
  
    foreach ($deals as $d) {
      $kitProduct = current($d['UF_KIT_PRODUCTS']);
      $this->deals['DEALS_DATA'][$d['ID']] = $d;
      $this->deals['DEALS'][$kitProduct][] = $d['ID'];
      $this->deals['DEALS_BY_STAGES'][$d['STAGE_ID']][$kitProduct][] = $d['ID'];
      $this->deals['DEALS_BY_RESPONSIBLE'][$kitProduct][$d['ASSIGNED_BY_ID']][$d['STAGE_ID']][] = $d['ID'];
    }
  }

  function getProducts(): void
  {
    $this->deals['PRODUCTS'] = [];
    $arFilter = [
      'ACTIVE' => 'Y',
      'IBLOCK_ID' => PRODUCT_IB_ID,
    ];
    $arSelect = [
      'ID',
      'NAME',
      'PROPERTY_UF_PRODUCT_DEPARTMENT',
    ];
  
    $arProductsRes = \CIBlockElement::GetList([], $arFilter, false, false, $arSelect);
    while ($product = $arProductsRes->GetNext()) {
      $structureIblockID = \COption::GetOptionInt('intranet', 'iblock_structure', false);
      if(!empty($product['PROPERTY_UF_PRODUCT_DEPARTMENT_VALUE'])) {
        $res = \CIBlockSection::GetByID($product['PROPERTY_UF_PRODUCT_DEPARTMENT_VALUE']);
        if ($arSection = $res->Fetch()) {
            $product['UNIT_SECTION'] = $arSection;
        }
      }
      $this->deals['PRODUCTS'][$product['ID']] = $product;
    }
  }

  // function createFile(): mixed
  // {
  //   $fileName = 'products_report.xlsx';
  //   $xlsx = new \Kokoc\Crm\Reports\SimpleXLSXGen();

  //   $xlsx->addSheet($this->createDataTemplateMain(), 'Единый отчёт');
  //   $xlsx->setColWidth(2, 30);

  //   foreach ($this->deals['PRODUCTS'] as $PRODUCT) {
  //     if (empty($this->deals['DEALS'][$PRODUCT['ID']])) continue;

  //     $name = mb_substr(
  //       \Cutil::translit(
  //         (string)$PRODUCT['NAME'],
  //         'ru',
  //         [ 'replace_space' => ' ', 'replace_other' => '-', 'change_case' => false]
  //       ), 0, 31);
  //     $xlsx->addSheet($this->createDataTemplatePage($PRODUCT['ID']), $name);
  //     $xlsx->setColWidth(2, 30);
  //   }

  //   $xlsx->saveAs($fileName);

  //   return str_replace(Application::getDocumentRoot(), '', $fileName);
  // }

  // function createDataTemplateMain(): array
  // {
  //   $listData = [[
  //     'Название продукта',
  //     'БЮ Продукта',
  //     'Кол-во сделок в стадии "Обработка"',
  //     'Кол-во сделок в стадии "Выявление потребности"',
  //     'Кол-во сделок в стадии "Брифинг"',
  //     'Кол-во сделок в стадии "Подготовка стратегии / КП"',
  //     'Кол-во сделок в стадии "Стратегия / КП - отправлена"',
  //     'Кол-во сделок в стадии "Переговоры по КП"',
  //     'Кол-во сделок в стадии "Ожидание решения"',
  //     'Кол-во сделок в стадии "Заключение договора"',
  //     'Кол-во сделок в стадии "Договор подписан"',
  //     'Кол-во сделок в стадии "Передача на аккаунтинг"',
  //     'Кол-во сделок в стадиях Архив и Завершение сотрудничества',
  //     'ВСЕГО',
  //   ]];

  //   foreach ($this->deals['PRODUCTS'] as $PRODUCT) {
  //     $totalDealsCount = 0;
  //     $productID = $PRODUCT['ID'];
  //     if (empty($this->deals['DEALS'][$productID])) {
  //       continue;
  //     }
  //     $finalStatusCount = 0;
  //     $finalStatusStages = [
  //       'C22:APOLOGY',
  //       'C22:LOSE',
  //     ];
  //     foreach ($finalStatusStages as $stageId) {
  //       if (is_array($this->deals['DEALS_BY_STAGES'][$stageId][$productID])) {
  //         $finalStatusCount += count($this->deals['DEALS_BY_STAGES'][$stageId][$productID]);
  //       }
  //     }
  //     $allStatusStages = [
  //       'C22:UC_GR0G8F',
  //       'C22:UC_44PJ08',
  //       'C22:NEW',
  //       'C22:PREPARATION',
  //       'C22:PREPAYMENT_INVOIC',
  //       'C22:EXECUTING',
  //       'C22:FINAL_INVOICE',
  //       'C22:UC_QP7BZQ',
  //       'C22:UC_XJPR5R',
  //       'C22:UC_4Z82CF',
  //       'C22:APOLOGY',
  //       'C22:LOSE',
  //     ];
  //     foreach ($allStatusStages as $stageId) {
  //       if (is_array($this->deals['DEALS_BY_STAGES'][$stageId][$productID])) {
  //         $totalDealsCount += count($this->deals['DEALS_BY_STAGES'][$stageId][$productID]);
  //       }
  //     }
  //     $listString = [
  //       $PRODUCT['NAME'],
  //       $PRODUCT['UNIT_SECTION']['NAME'],
  //       (isset($this->deals['DEALS_BY_STAGES']['C22:UC_GR0G8F'][$productID]) ? count($this->deals['DEALS_BY_STAGES']['C22:UC_GR0G8F'][$productID]) : 0),
  //       (isset($this->deals['DEALS_BY_STAGES']['C22:UC_44PJ08'][$productID]) ? count($this->deals['DEALS_BY_STAGES']['C22:UC_44PJ08'][$productID]) : 0),
  //       (isset($this->deals['DEALS_BY_STAGES']['C22:NEW'][$productID]) ? count($this->deals['DEALS_BY_STAGES']['C22:NEW'][$productID]) : 0),
  //       (isset($this->deals['DEALS_BY_STAGES']['C22:PREPARATION'][$productID]) ? count($this->deals['DEALS_BY_STAGES']['C22:PREPARATION'][$productID]) : 0),
  //       (isset($this->deals['DEALS_BY_STAGES']['C22:PREPAYMENT_INVOIC'][$productID]) ? count($this->deals['DEALS_BY_STAGES']['C22:PREPAYMENT_INVOIC'][$productID]) : 0),
  //       (isset($this->deals['DEALS_BY_STAGES']['C22:EXECUTING'][$productID]) ? count($this->deals['DEALS_BY_STAGES']['C22:EXECUTING'][$productID]) : 0),
  //       (isset($this->deals['DEALS_BY_STAGES']['C22:FINAL_INVOICE'][$productID]) ? count($this->deals['DEALS_BY_STAGES']['C22:FINAL_INVOICE'][$productID]) : 0),
  //       (isset($this->deals['DEALS_BY_STAGES']['C22:UC_QP7BZQ'][$productID]) ? count($this->deals['DEALS_BY_STAGES']['C22:UC_QP7BZQ'][$productID]) : 0),
  //       (isset($this->deals['DEALS_BY_STAGES']['C22:UC_XJPR5R'][$productID]) ? count($this->deals['DEALS_BY_STAGES']['C22:UC_XJPR5R'][$productID]) : 0),
  //       (isset($this->deals['DEALS_BY_STAGES']['C22:UC_4Z82CF'][$productID]) ? count($this->deals['DEALS_BY_STAGES']['C22:UC_4Z82CF'][$productID]) : 0),
  //       $finalStatusCount,
  //       $totalDealsCount,
  //     ];
  //     $listData[] = $listString;
  //   }

  //   return $listData;
  // }

  // function createDataTemplatePage($productID): array
  // {
  //   $arHeadersAll = [
  //     $this->deals['PRODUCTS'][$productID]['NAME'],
  //     'БЮ ответственного',
  //     'Кол-во сделок в стадии "Обработка"',
  //     'Кол-во сделок в стадии "Выявление потребности"',
  //     'Кол-во сделок в стадии "Брифинг"',
  //     'Кол-во сделок в стадии "Подготовка стратегии / КП"',
  //     'Кол-во сделок в стадии "Стратегия / КП - отправлена"',
  //     'Кол-во сделок в стадии "Переговоры по КП"',
  //     'Кол-во сделок в стадии "Ожидание решения"',
  //     'Кол-во сделок в стадии "Заключение договора"',
  //     'Кол-во сделок в стадии "Договор подписан"',
  //     'Кол-во сделок в стадии "Передача на аккаунтинг"',
  //     'Кол-во сделок в стадиях Архив и Завершение сотрудничества',
  //     'ВСЕГО',
  //   ];
  //   $listData = [
  //     $arHeadersAll
  //   ];

  //   foreach ($this->deals['DEALS_BY_RESPONSIBLE'][$productID] as $userID => $stagesArr) {

  //     $totalDealsCount = 0;
  //     $finalStatusCount = 0;
  //     $finalStatusStages = [
  //       'C22:APOLOGY',
  //       'C22:LOSE',
  //     ];
  //     foreach ($finalStatusStages as $stageId) {
  //       if (is_array($stagesArr[$stageId])) {
  //         $finalStatusCount += count($stagesArr[$stageId]);
  //       }
  //     }
  //     $allStatusStages = [
  //       'C22:UC_GR0G8F',
  //       'C22:UC_44PJ08',
  //       'C22:NEW',
  //       'C22:PREPARATION',
  //       'C22:PREPAYMENT_INVOIC',
  //       'C22:EXECUTING',
  //       'C22:FINAL_INVOICE',
  //       'C22:UC_QP7BZQ',
  //       'C22:UC_XJPR5R',
  //       'C22:UC_4Z82CF',
  //       'C22:APOLOGY',
  //       'C22:LOSE',
  //     ];
  //     foreach ($allStatusStages as $stageId) {
  //       if (isset($stagesArr[$stageId])) {
  //         $totalDealsCount += count($stagesArr[$stageId]);
  //       }
  //     }
  //     $listString = [
  //       $this->users[$userID]['LAST_NAME'].' '.$this->users[$userID]['NAME'].' '.$this->users[$userID]['SECOND_NAME'],
  //       $this->users[$userID]['UNIT_SECTION']['NAME'],
  //       (isset($stagesArr['C22:UC_GR0G8F']) ? count($stagesArr['C22:UC_GR0G8F']) : 0),
  //       (isset($stagesArr['C22:UC_44PJ08']) ? count($stagesArr['C22:UC_44PJ08']) : 0),
  //       (isset($stagesArr['C22:NEW']) ? count($stagesArr['C22:NEW']) : 0),
  //       (isset($stagesArr['C22:PREPARATION']) ? count($stagesArr['C22:PREPARATION']) : 0),
  //       (isset($stagesArr['C22:PREPAYMENT_INVOIC']) ? count($stagesArr['C22:PREPAYMENT_INVOIC']) : 0),
  //       (isset($stagesArr['C22:EXECUTING']) ? count($stagesArr['C22:EXECUTING']) : 0),
  //       (isset($stagesArr['C22:FINAL_INVOICE']) ? count($stagesArr['C22:FINAL_INVOICE']) : 0),
  //       (isset($stagesArr['C22:UC_QP7BZQ']) ? count($stagesArr['C22:UC_QP7BZQ']) : 0),
  //       (isset($stagesArr['C22:UC_XJPR5R']) ? count($stagesArr['C22:UC_XJPR5R']) : 0),
  //       (isset($stagesArr['C22:UC_4Z82CF']) ? count($stagesArr['C22:UC_4Z82CF']) : 0),
  //       $finalStatusCount,
  //       $totalDealsCount,
  //     ];
  //     $listData[] = $listString;
  //   }

  //   return $listData;
  // }
}
