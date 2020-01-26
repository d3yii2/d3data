#D3 Data"

Yii2 module for pivot reporting. 

## Features

 - get data from db by query
 - define columns
 - from records create pivot table. 
 - output pivot field unique values list for filters
 - filter data 
 

## Installation

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
$ composer require d3yii2/d3data "*"
```

or add

```
"d3yii2/d3data": "*"
```

to the `require` section of your `composer.json` file.


## Usage

```php
<?php



class ReportGrid
{

    public const F_STORE = 'store';
    public const F_MANUFACTURED = 'manufactured';
    public const F_PRODUCT = 'product';
    public const F_PACKS_COUNT = 'packsCount';
    /** @var D3DataProvider */
    public $dataProvider;

    public function __construct(int $productId, string $manufacturedDate )
    {

        $storeList = StoreDictionary::getList($this->sysCompanyId);
        $this->dataProvider = new D3DataProvider([
            'rowKeyColumns' => [self::F_PRODUCT,self::F_MANUFACTURED],
            'tableColumnFields' => [self::F_STORE],
            'tableAggregateColumns' => [self::F_PACKS_COUNT],
            'query' => $this->query(),
            'filter' => [
                self::F_PRODUCT => (string)$productId,
                self::F_MANUFACTURED => $manufacturedDate,
            ],
            'columns' => [
                self::F_STORE => [
                    'class' => D3DataListColumn::class,
                    'name' => self::F_STORE,
                    'label' => 'Noliktava',
                    'list' => StoreDictionary::getList($this->sysCompanyId)
                ],
                self::F_MANUFACTURED => [
                    'class' => D3DataDateColumn::class,
                    'name' => self::F_MANUFACTURED,
                    'label' => 'Ražots',
                ],
                self::F_PRODUCT => [
                    'class' => D3DataListColumn::class,
                    'name' => self::F_PRODUCT,
                    'label' => 'Produkts',
                    'list' => CwclProductsDictionary::getListAll()
                ],
                self::F_PACKS_COUNT => [
                    'name' => self::F_PACKS_COUNT,
                    'label' => static function(string $value) use ($storeList){
                        return $storeList[$value] ?? $value;
                    }
                ],
            ]
        ]);
    }


    public function getTable(): array
    {
        return $this->dataProvider->getTable();
    }

    public function getProductFilterItems(int $selectedId, string $manufacturedDate): array
    {
        $items = [];
        $filterList = [ 0 => 'Visi']
            + $this
                ->dataProvider
                ->columns[self::F_PRODUCT]
                ->getFilterList();
        foreach($filterList as $id => $label
        ){

                $items[] = [
                    'label' => $label,
                    'url' => [
                        '',
                        'productId' => $id,
                        'manufacturedDate' => $manufacturedDate
                    ],
                    'selected' => $id === $selectedId
                ];

        }
        return $items;
    }

    public function getManufacturedFilterItems(string $selected, int $productId): array
    {
        $items = [];
        $filterList = [ '' => 'Visi']
            + $this
                ->dataProvider
                ->columns[self::F_MANUFACTURED]
                ->getFilterList();
        foreach($filterList as $id => $label
        ){

            $items[] = [
                'label' => $label,
                'url' => [
                    '',
                    'manufacturedDate' => $id,
                    'productId' => $productId
                ],
                'selected' => $id === $selected
            ];

        }
        return $items;

    }

    public function getColumns(): array
    {
        return $this->dataProvider->getGeneratedColumns();
    }
    public function query(): StoreTransactionsQuery
    {
        return StoreTransactions::find()
            ->select([
                self::F_STORE => 'stack.store_id',
                self::F_PRODUCT => 'product.product_id',
                self::F_MANUFACTURED => 'product.manufacture_date',
                self::F_PACKS_COUNT => 'COUNT(*)'
            ])
            ;
    }
}

```
### controller
```php
    /**
     * @param int $productId
     * @param string $manufacturedDate
     * @return string|Response
     * @throws Exception
     */
    public function actionStackGrid(int $productId = 0, string $manufacturedDate = '')
    {
        $logic = new ReportGrid( $productId, $manufacturedDate);

        return $this->render('stack_grid', [
            'data' => $logic->getTable(),
            'columns' => $logic->getColumns(),
            'productFilterItems' => $logic->getProductFilterItems($productId, $manufacturedDate),
            'manufacturedFilterItems' => $logic->getManufacturedFilterItems($manufacturedDate, $productId),
        ]);
    }
```

