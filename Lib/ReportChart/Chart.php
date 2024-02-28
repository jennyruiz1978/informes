<?php
/**
 * This file is part of Informes plugin for FacturaScripts
 * Copyright (C) 2022-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Plugins\Informes\Lib\ReportChart;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Plugins\Informes\Model\Report;

/**
 * Description of AreaChart
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
abstract class Chart
{
    /** @var Report */
    protected $report;

    abstract public function render(int $height = 0): string;

    abstract protected function getData(): array;

    public function __construct(Report $report)
    {
        $this->report = $report;
    }

    protected function getColors(int $num): array
    {
        $colors = [];
        for ($i = 0; $i < $num; $i++) {
            $option = mt_rand(0, 3);
            switch ($option) {
                case 0:
                    $colors[] = '0, ' . mt_rand(0, 255) . ', ' . mt_rand(0, 255);
                    break;

                case 1:
                    $colors[] = mt_rand(0, 255) . ', 0, ' . mt_rand(0, 255);
                    break;

                case 2:
                    $colors[] = mt_rand(0, 255) . ', ' . mt_rand(0, 255) . ', 0';
                    break;

                default:
                    $colors[] = mt_rand(0, 255) . ', ' . mt_rand(0, 255) . ', ' . mt_rand(0, 255);
                    break;
            }
        }

        return $colors;
    }

    protected function getDataSources(): array
    {
        $dataBase = new DataBase();
        $sql = $this->getSql($this->report);
        if (empty($sql) || !$dataBase->tableExists($this->report->table)) {
            return [];
        }

        $sources = [$this->report->name => $dataBase->select($sql)];

        $comparedReport = new Report();
        if (!empty($this->report->compared) && $comparedReport->loadFromCode($this->report->compared)) {
            $sources[$comparedReport->name] = $dataBase->select($this->getSql($comparedReport));
        }

        return $sources;
    }

    protected function getSql(Report $report): string
    {
        if (empty($report->table) || empty($report->xcolumn)) {
            return '';
        }

        return strtolower(FS_DB_TYPE) == 'postgresql' ?
            $this->getSqlPostgreSQL($report) :
            $this->getSqlMySQL($report);
    }

    protected function getSqlMySQL(Report $report): string
    {
        $xCol = $report->xcolumn;
        switch ($report->xoperation) {
            case 'DAY':
                $xCol = "DATE_FORMAT(" . $report->xcolumn . ", '%Y-%m-%d')";
                break;

            case 'WEEK':
                $xCol = "DATE_FORMAT(" . $report->xcolumn . ", '%Y-%u')";
                break;

            case 'MONTH':
                $xCol = "DATE_FORMAT(" . $report->xcolumn . ", '%m')";
                break;

            case 'MONTHS':
                $xCol = "DATE_FORMAT(" . $report->xcolumn . ", '%Y-%m')";
                break;

            case 'UNIXTIME_DAY':
                $xCol = "DATE_FORMAT(FROM_UNIXTIME(" . $report->xcolumn . "), '%Y-%m-%d')";
                break;

            case 'UNIXTIME_WEEK':
                $xCol = "DATE_FORMAT(FROM_UNIXTIME(" . $report->xcolumn . "), '%Y-%u')";
                break;

            case 'UNIXTIME_MONTH':
                $xCol = "DATE_FORMAT(FROM_UNIXTIME(" . $report->xcolumn . "), '%Y-%m')";
                break;

            case 'UNIXTIME_YEAR':
                $xCol = "DATE_FORMAT(FROM_UNIXTIME(" . $report->xcolumn . "), '%Y')";
                break;

            case 'YEAR':
                $xCol = "DATE_FORMAT(" . $report->xcolumn . ", '%Y')";
                break;
        }

        $yCol = empty($report->ycolumn) ? 'COUNT(*)' : 'SUM(' . $report->ycolumn . ')';

        return 'SELECT ' . $xCol . ' as xcol, ' . $yCol . ' as ycol FROM ' . $report->table
            . $report->getSqlFilters() . ' GROUP BY xcol ORDER BY xcol ASC;';
    }

    protected function getSqlPostgreSQL(Report $report): string
    {
        $xCol = $report->xcolumn;
        switch ($report->xoperation) {
            case 'DAY':
                $xCol = "to_char(" . $report->xcolumn . ", 'YY-MM-DD')";
                break;

            case 'WEEK':
                $xCol = "to_char(" . $report->xcolumn . ", 'YY-WW')";
                break;

            case 'MONTH':
                $xCol = "to_char(" . $report->xcolumn . ", 'MM')";
                break;

            case 'MONTHS':
                $xCol = "to_char(" . $report->xcolumn . ", 'YY-MM')";
                break;

            case 'UNIXTIME_DAY':
                $xCol = "to_char(FROM_UNIXTIME(" . $report->xcolumn . "), 'YY-MM-DD')";
                break;

            case 'UNIXTIME_WEEK':
                $xCol = "to_char(FROM_UNIXTIME(" . $report->xcolumn . "), 'YY-WW')";
                break;

            case 'UNIXTIME_MONTH':
                $xCol = "to_char(FROM_UNIXTIME(" . $report->xcolumn . "), 'YY-MM')";
                break;

            case 'UNIXTIME_YEAR':
                $xCol = "to_char(FROM_UNIXTIME(" . $report->xcolumn . "), 'YY')";
                break;

            case 'YEAR':
                $xCol = "to_char(" . $report->xcolumn . ", 'YY')";
                break;
        }

        $yCol = empty($report->ycolumn) ? 'COUNT(*)' : 'SUM(' . $report->ycolumn . ')';

        return 'SELECT ' . $xCol . ' as xcol, ' . $yCol . ' as ycol FROM ' . $report->table
            . $report->getSqlFilters() . ' GROUP BY xcol ORDER BY xcol ASC;';
    }
}
