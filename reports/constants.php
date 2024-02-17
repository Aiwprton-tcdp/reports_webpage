<?

\Bitrix\Main\Loader::includeModule('crm');

const FILTER_PRESETS = [
  'lastWeek' => [
    'name' => 'Последняя неделя',
    'fields' => [
      'DATE_MODIFY' => \Bitrix\Main\UI\Filter\DateType::LAST_7_DAYS,
      'REPORT_TYPE_ID' => '1',
    ]
  ], 'lastMonth' => [
    'name' => 'Последний месяц',
    'default' => 'true',
    'fields' => [
      'DATE_MODIFY' => \Bitrix\Main\UI\Filter\DateType::LAST_30_DAYS,
      'REPORT_TYPE_ID' => '1',
    ]
  ], 'lastQuarter' => [
    'name' => 'Последний квартал',
    'fields' => [
      'DATE_MODIFY' => \Bitrix\Main\UI\Filter\DateType::LAST_90_DAYS,
      'REPORT_TYPE_ID' => '1',
    ]
  ],
];

const FILTERS = [
  [
    'id' => 'REPORT_TYPE_ID',
    'name' => 'Тип отчёта',
    'type' => 'list',
    'items' => [
      '1' => 'По сделкам',
      '2' => 'По продуктам',
    ],
    // 'value'=> '1',
    'required' => true,
    'default' => true
  ], [
    'id' => 'DATE_MODIFY',
    'name' => 'Дата обновления',
    'type' => 'date',
    'required' => true,
    'default' => true
  ], [
    'id' => 'ASSIGNED_BY_ID',
    'name' => 'Ответственный',
    'type' => 'entity_selector',
    'params' => [
      'multiple' => 'Y',
      'dialogOptions' => [
        'height' => 240,
        'context' => 'filter',
        'entities' => [
          [
            'id' => 'user',
            'options' => [
              'inviteEmployeeLink' => false
            ],
          ],
          [
            'id' => 'department',
          ]
        ]
      ],
    ],
    'required' => true,
    'default' => true
  ],
];

const COLUMNS = [
  [
    'id' => 'ID',
    'name' => 'ID',
    'default' => true,
    'sticked' => true,
    'width' => 50,
    'resizeable' => false,
  ], [
    'id' => 'TITLE',
    'name' => 'Название',
    'default' => true,
  ], [
    'id' => 'STAGE_ID',
    'name' => 'ID стадии',
    'width' => 150,
    'resizeable' => false,
  ], [
    'id' => 'ASSIGNED_BY_ID',
    'name' => 'ID ответственного',
    'default' => true,
    'width' => 250,
    'resizeable' => false,
  ], [
    'id' => 'DATE_CREATE',
    'name' => 'Дата создания',
    'default' => true,
    'width' => 150,
    'resizeable' => false,
  ], [
    'id' => 'DATE_MODIFY',
    'name' => 'Дата изменения',
    'width' => 150,
    'resizeable' => false,
  ]
];