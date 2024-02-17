<?php

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php');
\Bitrix\Main\Loader::includeModule('crm');

class ReportTrait
{
  private $date_from = null;
  private $date_to = null;
  private $category_id = null;
  private $users_ids = [];
  public $deals = [];
  public $stages = [];
  private $deals_by_users_counts = [];
  private $users = [];
  private $history = [];

  public $results = [];


  public function __construct($date_from, $date_to, $users_ids, $category_id = '22')
  {
    $this->date_from = new \Bitrix\Main\Type\DateTime($date_from ?? 0);
    $this->date_to = new \Bitrix\Main\Type\DateTime($date_to ?? null);
    $this->users_ids = $users_ids;
    $this->category_id = $category_id;

    $this->getDeals();
    $this->getDealStages();
    $this->getDealsUsersCount();
    $this->getUsers();
    $this->getDealStageHistories();
    $this->prepareResults();
  }


  private function getDealStageHistories(): void
  {
    $this->history = \Bitrix\Crm\History\Entity\DealStageHistoryTable::getList([
      'select' => [ 'ID', 'STAGE_ID', 'OWNER_ID', 'CREATED_TIME', 'START_DATE' ],
      'filter' => [
        '=TYPE_ID' => 2,
        '=CATEGORY_ID' => $this->category_id,
        '>=START_DATE' => $this->date_from,
        '<=START_DATE' => $this->date_to,
      ],
      'order' => [ 'ID' => 'DESC' ],
    ])->fetchAll();
  }

  private function getDeals(): void
  {
    $filter = [];
    if (count($this->users_ids) > 0)
    {
      $filter['@ASSIGNED_BY_ID'] = $this->users_ids;
    }

    $this->deals = \Bitrix\Crm\DealTable::getList([
      'filter' => $filter,
      'select' => [ 'ID', 'STAGE_ID', 'ASSIGNED_BY_ID' ],
    ])->fetchAll();
  }

  private function getDealStages(): void
  {
    $this->stages = \Bitrix\Crm\Category\DealCategory::getStageList($this->category_id);
  }

  // вычисление количества сделок по стадиям и ответственным
  private function getDealsUsersCount(): void
  {
    foreach ($this->users_ids as $ui) {
      $data = [];
      foreach ($this->stages as $stage) {
        $data[$stage] = 0;
      }

      foreach ($this->deals as $deal) {
        $data[$this->stages[$deal['STAGE_ID']]] += $deal['ASSIGNED_BY_ID'] == $ui;
      }

      $this->deals_by_users_counts[$ui] = array_values($data);
    }
  }

  private function getUsers(): void
  {
    $this->users = \Bitrix\Main\UserTable::getList([
      'select' => [ 'ID', 'NAME', 'LAST_NAME', 'UF_DEPARTMENT' ],
      'filter' => [ 'ID' => $this->users_ids ]
    ])->fetchAll();
  }
  
  private function prepareResults(): void
  {
    $deps = \Bitrix\Main\UserUtils::getDepartmentNames(
      array_merge(...array_map(fn($u) => $u['UF_DEPARTMENT'], $this->users))
    );

    foreach ($this->deals_by_users_counts as $key => $value) {
      $user = $this->users[array_search($key, $this->users_ids)];
      $name = "{$user['LAST_NAME']} {$user['NAME']}";
      $department = $this->selectDepartment($user['UF_DEPARTMENT'], $deps);
      $cr = $this->collectCRData($key);
      $data = [$name, $department, ...$value, ...$cr];
      array_push($this->results, $data);
    }
  }
  
  private function selectDepartment($current, $all): string | null
  {
    $filtered = array_values(array_filter($all, fn($a) => in_array($a['ID'], $current)));
    $filtered3plus = array_values(array_filter($filtered, fn($f) => 2 < $f['DEPTH_LEVEL']));
    return @$filtered3plus[0]['NAME'] ?? @$filtered[0]['NAME'];
  }
  
  private function collectCRData($user_id): array
  {
    $user_deals = array_filter($this->deals, fn($d) => $d['ASSIGNED_BY_ID'] == $user_id);

    $were_leads_ids = $this->getIdsByStage($user_deals, 'C22:UC_GR0G8F');
    $were_briefs_ids = $this->getIdsByStage($user_deals, 'C22:NEW');
    $were_new_clients_ids = $this->getIdsByStage($user_deals, 'C22:UC_4Z82CF');
    $were_contracts_ids = $this->getIdsByStage($user_deals, 'C22:UC_XJPR5R');

    $leads_count = count($were_leads_ids);
    $deals_count = count($were_briefs_ids);

    // $lead_client_cr = count($were_leads_ids) / (count($were_new_clients_ids) || 1);
    // $lead_contract_cr = count($were_leads_ids) / (count($were_contracts_ids) || 1);
    // $brief_contract_cr = count($were_briefs_ids) / (count($were_contracts_ids) || 1);
    $lead_client_cr = count($were_new_clients_ids) / (count($were_leads_ids) || 1);
    $lead_contract_cr = count($were_contracts_ids) / (count($were_leads_ids) || 1);
    $brief_contract_cr = count($were_contracts_ids) / (count($were_briefs_ids) || 1);
    
    return [
      $leads_count,
      $deals_count,
      $lead_client_cr * 100,
      $lead_contract_cr * 100,
      $brief_contract_cr * 100,
    ];
  }

  private function getIdsByStage($user_deals, $stage): array
  {
    $user_deals_ids = array_map(fn($ud) => $ud['ID'], $user_deals);
    $stage_histories = array_filter($this->history, fn($h) =>
      $h['STAGE_ID'] == $stage && in_array($h['OWNER_ID'], $user_deals_ids));
      return array_values(array_map(fn($sh) => $sh['OWNER_ID'], $stage_histories));
  }
}
