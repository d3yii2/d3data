<?php


namespace d3yii2\d3data;


class D3DataListColumn extends D3DataColumn
{

    /** @var array */
    public  $list;


    public function getCellValue(array $row, int $index)
    {
        return $this->list[$row[$this->name]]?? ($row[$this->name]?' ??? ':' - ');
    }

    public function getFilterList(): array
    {
        $list = [];
        foreach($this->valuesList as $value){
            $list[$value] = $this->list[$value]??' ??? ';
        }
        return $list;
    }
}