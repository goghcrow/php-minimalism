<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/5/30
 * Time: 下午2:34
 */

namespace Minimalism\PHPDump\Util;


class AsciiTable
{
    const VERTICE = '+';
    const HORIZON = '-';
    const VERTICAL = '|';
    const SPACE = " ";
    const BR = "\n";
    const PADDING_TYPE = STR_PAD_RIGHT;

    private $columnWidth;

    public function draw($rows, $maxColumn = 10, $maxCellLen = 18)
    {
        if (empty($rows)) {
            return;
        }

        $this->trunkCell($rows, $maxCellLen);
        $tables = $this->tableChunk($rows, $maxColumn);
        foreach ($tables as $table) {
            $this->drawTable($table);
        }
    }

    private function trunkCell(&$rows, $maxCellLen)
    {
        $maxCellLen = intval(max(3, $maxCellLen));

        foreach ($rows as &$row) {
            foreach ($row as &$item) {
                if (strlen($item) > $maxCellLen) {
                    $item = substr($item, 0, $maxCellLen - 3) . "...";
                }
            }
            unset($item);
        }
        unset($row);
    }

    // TODO chunk by width
    private function tableChunk($rows, $size)
    {
        $tables = [];
        foreach ($rows as $row) {
            foreach (array_chunk($row, $size, true) as $i => $subRow) {
                $tables[$i][] = $subRow;
            }
        }
        return $tables;
    }

    private function drawTable($rows)
    {
        $this->calculateColumnWidth($rows);

        $this->drawHorizontalLine();

        $header = array_combine(array_keys($rows[0]), array_keys($rows[0]));
        $this->drawRow($header);
        $this->drawHorizontalLine();

        foreach ($rows as $row) {
            $this->drawRow($row);
        }

        $this->drawHorizontalLine();
    }

    private function drawRow(array $row)
    {
        $items = [];
        foreach($this->columnWidth as $field => $width) {
            $items[] = str_pad($row[$field], $width, static::SPACE, static::PADDING_TYPE);
        }
        echo static::VERTICAL, static::SPACE,
            implode(static::SPACE . static::VERTICAL . static::SPACE, $items),
            static::SPACE, static::VERTICAL, static::BR;
    }

    private function drawHorizontalLine()
    {
        $items = [];
        foreach ($this->columnWidth as $field => $width) {
            $items[] = str_repeat(static::HORIZON, $width + 2);
        }
        echo static::VERTICE, implode(static::VERTICE, $items), static::VERTICE, static::BR;
    }

    private function calculateColumnWidth($rows)
    {
        $strlen = function_exists("mb_strlen") ? "\\mb_strlen" : "\\strlen";

        $title = array_keys($rows[0]);

        $colWidth = [];
        foreach ($title as $field) {
            $column = array_column($rows, $field);
            $column[] = $field;
            $colWidth[$field] = max(array_map($strlen, $column));
        }
        $this->columnWidth = $colWidth;
    }
}