<?php


namespace d3yii2\d3data;

use Closure;
use yii\base\Component;

class D3DataColumn extends Component
{

    /**
     * @var string
     */
    public $name;

    /**
     * @var string|Closure
     */
    public $label;


    /** @var D3DataProvider */
    public $dataProvider;

    /** @var bool */
    public $visible = true;

    public $valuesList = [];

    /**
     * @var Closure an anonymous function that is used to determine the value to display in the current column.
     */
    public $value;


    public function getCellValue(array $row, int $index)
    {
        if(!$this->value){
            return $row[$this->name];
        }
        if(is_callable($this->value)) {
            return call_user_func($this->value, $row,  $index, $this);
        }

        return '-';
    }

    public function saveValue($value): void
    {
        if(!$value){
            return;
        }
        if(!in_array($value,$this->valuesList, false)){
            $this->valuesList[$value] = $value;
        }
    }

    public function getFilterList(): array
    {
        return $this->valuesList;
    }
}