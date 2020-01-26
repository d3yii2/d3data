<?php


namespace d3yii2\d3data;

use Yii;
use yii\base\Component;
use yii\db\QueryInterface;

class D3DataProvider extends Component
{

    /** @var QueryInterface */
    public $query;

    /** @var D3DataColumn[] */
    public $columns;

    public $dataColumnClass;

    /** @var string[]  */
    public $rowKeyColumns = [];

    /** @var string[]  */
    public $tableColumnFields = [];

    /** @var string[]  */
    public $tableAggregateColumns = [];

    public $genTableColumns = [];

    public $table = [];

    public $filter = [];

    public function init(): void
    {
        parent::init();
        if(!$this->dataColumnClass){
            $this->dataColumnClass = D3DataColumn::class;
        }
        $this->initColumns();
    }

    public function initColumns(): void
    {
        foreach ($this->columns as $i => $column) {
            if (is_string($column)) {
                $column = [
                    'name' => $column
                ];
            }
                $column = Yii::createObject(array_merge([
                    'class' => $this->dataColumnClass,
                    'dataProvider' => $this,
                ], $column));

            if (!$column->visible) {
                unset($this->columns[$i]);
                continue;
            }
            $this->columns[$i] = $column;
        }

    }

    public function getGeneratedColumns(): array
    {
        $list = [];
        foreach ($this->genTableColumns as $columnName => $columnDef){
            if($columnDef['type'] === 'key') {
                $keyColumnDefinition = $this->columns[$columnName];
                $header = $keyColumnDefinition->label;
                if(is_callable($header)){
                    $header = $header($columnDef['value']);
                }
                $list[] = [
                    'header' => $header,
                    'attribute' => $columnName,
                ];
                continue;
            }
            $aggrColumnDefinition = $this->columns[$columnDef['aggregateColumn']];
            $header = $aggrColumnDefinition->label;
            if(is_callable($header)){
                $header = $header($columnDef['value']);
            }
            $list[] = [
                'header' => $header,
                'attribute' => $columnName,
            ];
        }
        return $list;
    }

    public function getTable(): array
    {
        $data = $this->query->asArray()->all();
        foreach($this->rowKeyColumns as $columnName){
            $this->genTableColumns[$columnName] = [
                'type' => 'key'
            ];
        }
        foreach($data as $rowKey => $row){
            $keyValueList = [];

            foreach($this->rowKeyColumns as $columnName){
                $keyValueList[] = $row[$columnName];
                $this->columns[$columnName]->saveValue($row[$columnName]);
            }


            /**
             * filtreeshana
             * @var bool $filterOk
             */
            $filterOk = true;
            foreach($this->filter as $filterColumnName => $filterColumnValue){
                if($filterColumnValue && $row[$filterColumnName] !== $filterColumnValue){
                    $filterOk = false;
                }
            }
            if(!$filterOk){
                continue;
            }



            $key = implode('-',$keyValueList);
            foreach($this->rowKeyColumns as $columnName){
                $this->table[$key][$columnName] = $this->columns[$columnName]->getCellValue($row,$rowKey);
            }
            foreach($this->tableColumnFields as $columnName) {
                foreach($this->tableAggregateColumns as $aggregateColumn) {
                    $columnKey = $row[$columnName] . '#' . $aggregateColumn;
                    if(!isset($this->genTableColumns[$columnKey])){
                        $this->genTableColumns[$columnKey] = [
                            'type' => 'aggregate',
                            'columnName' => $columnName,
                            'aggregateColumn' => $aggregateColumn,
                            'value' => $row[$columnName]
                        ];
                    }
                    if (!isset($table[$key][$columnKey])) {
                        $this->table[$key][$columnKey] = $row[$aggregateColumn];
                    } else {
                        $this->table[$key][$columnKey] += $row[$aggregateColumn];
                    }
                }
            }
        }
        foreach($this->table as $key => $row){
            foreach($this->genTableColumns as $columnKey => $columnDef){
                if(!isset($this->table[$key][$columnKey])){
                    $this->table[$key][$columnKey] = null;
                }
            }
        }

        return $this->table;
    }


}