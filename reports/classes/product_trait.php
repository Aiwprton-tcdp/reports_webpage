<?php

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php');
use Bitrix\Main\Application;
\Bitrix\Main\Loader::includeModule('crm');

class ProductTrait
{
  private $date_from = null;
  private $date_to = null;
  private $category_id = null;
  public $users_ids = [];
  public $deals = [];
  // public $stages = [];
  // private $deals_by_users_counts = [];
  public $users = [];
  // private $history = [];

  // public $results = [];


  public function __construct($date_from, $date_to, $users_ids, $category_id = '22')
  {
    $this->date_from = new \Bitrix\Main\Type\DateTime($date_from ?? 0);
    $this->date_to = new \Bitrix\Main\Type\DateTime($date_to ?? null);
    $this->users_ids = $users_ids;
    $this->category_id = $category_id;

    $this->getDeals();
    $this->getProducts();
    $this->getUsers();
    $this->getDepartments();
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

  private function getUsers(): void
  {
    $this->users = \Bitrix\Main\UserTable::getList([
      'select' => [ 'ID', 'NAME', 'LAST_NAME', 'UF_DEPARTMENT', 'UF_SERVICE_USER' ],
      'filter' => [ 'ID' => $this->users_ids ]
    ])->fetchAll();
    // $this->users = array_map(fn($u) => [ $u['ID'] => $u ], $users);
  }

  private function getDepartments(): void
  {
    $deps = \Bitrix\Main\UserUtils::getDepartmentNames(
      array_merge(...array_map(fn($u) => $u['UF_DEPARTMENT'], $this->users))
    );

    foreach ($this->users as $key => $value) {
      // $user = $this->users[array_search($key, $this->value)];
      // $this->users[$key]['DepartmentName'] = array_map(fn($u) => $u['UF_DEPARTMENT'], $this->users);
      $this->users[$key]['DepartmentName'] = $this->selectDepartment($value['UF_DEPARTMENT'], $deps);
    }
  }
  
  private function selectDepartment($current, $all): string | null
  {
    $filtered = array_values(array_filter($all, fn($a) => in_array($a['ID'], $current)));
    $filtered3plus = array_values(array_filter($filtered, fn($f) => 2 < $f['DEPTH_LEVEL']));
    return @$filtered3plus[0]['NAME'] ?? @$filtered[0]['NAME'];
  }
}
