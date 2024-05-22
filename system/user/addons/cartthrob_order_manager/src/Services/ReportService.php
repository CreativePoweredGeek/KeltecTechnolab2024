<?php

namespace CartThrob\OrderManager\Services;

require_once PATH_THIRD . 'cartthrob_order_manager/vendor/autoload.php';

use Box\Spout\Common\Entity\Row;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use League\Csv\Writer;

class ReportService
{
    /**
     * @param $title
     * @param $settings
     * @param $type
     * @return \CartThrob\OrderManager\Model\OrderReport
     */
    public function create($title, $settings, $type)
    {
        $report = ee('Model')->make('cartthrob_order_manager:OrderReport', [
            'settings' => $settings,
            'report_title' => $title,
            'type' => $type,
        ]);

        $report->save();

        return $report;
    }

    /**
     * @param null $reportId
     */
    public function delete($reportId)
    {
        $report = ee('Model')->get('cartthrob_order_manager:OrderReport')
            ->filter('id', $reportId)
            ->first();

        if ($report) {
            $report->delete();
        }
    }

    /**
     * @param $rows
     * @param $headers
     * @param $totals
     * @param $type
     * @param null $filename
     * @throws \Box\Spout\Common\Exception\IOException
     * @throws \Box\Spout\Common\Exception\InvalidArgumentException
     * @throws \Box\Spout\Writer\Exception\WriterNotOpenedException
     */
    public function download($rows, $headers, $totals, $type, $filename = null)
    {
        $ext = ($type == 'csv') ? 'csv' : 'xlsx';

        $writer = ($type == 'csv')
                    ? WriterEntityFactory::createCSVWriter()
                    : WriterEntityFactory::createXLSXWriter();

        $writer->openToBrowser(($filename ?? 'export') . '.' . $ext);

        // First create header row
        $headerRow = $this->createHeaderRow($headers);
        $writer->addRow($headerRow);

        // Then data
        $excelRows = $this->createDataRows($rows);
        $writer->addRows($excelRows);

        $writer->close();
        exit;
    }

    /**
     * @param array $data
     * @param array $headers
     * @param null $filename
     * @param bool $return
     * @param string $format
     * @return Response|void
     * @throws \League\Csv\CannotInsertRecord
     */
    public function exportCsv($data = [], $headers = [], $filename = null, $return = false, $format = 'csv')
    {
        if (!is_array($data) || empty($data)) {
            return;
        }

        $writer = Writer::createFromPath('php://temp', 'w+');
        if (!is_array($headers) && !empty($headers)) {
            $writer->insertOne($headers);
        }

        $writer->insertAll($data);

        $writer->output($filename ?? 'export.csv');

        exit;
    }

    /**
     * @param array $data
     * @param array $headers
     * @param null $filename
     * @param bool $return
     */
    public function export_excel($data = [], $headers = [], $filename = null, $return = false)
    {
        $writer = WriterEntityFactory::createXLSXWriter();
    }

    /**
     * @param array $data
     * @param array $headers
     * @param null $filename
     * @param bool $return_data
     * @param string $format
     * @return string|null
     */
    private function export_csv(array $data, $headers = [], $filename = null, $return_data = false, $format = 'xls')
    {
        if (!$filename) {
            $filename = 'data';
        }

        $content = null;

        if (is_array($data) && !empty($data)) {
            $rows = [];

            $headers_count = count($headers);
            // data = rows of data key_count == row_count.
            $columns_count = count(array_keys($data[0]));

            foreach ($data as $key => $value) {
                if (empty($value)) {
                    continue;
                }
                $rows[] = $this->format_csv($value, $format);
            }

            if ($headers_count && $columns_count == $headers_count) {
                $content .= $this->format_csv($headers, $format);
            }

            $content .= implode('', $rows);
        }

        if ($content) {
            if ($return_data) {
                return $content;
            } else {
                ee()->load->helper('download');
                force_download($filename . '.' . $format, $content);
            }
        }
    }

    /**
     * @param $row
     * @param string $format
     * @return bool|string
     */
    private function format_csv($row, $format = 'xls')
    {
        static $fp = false;

        if ($fp === false) {
            // see http://php.net/manual/en/wrappers.php.php
            $fp = fopen('php://temp', 'r+');
        } else {
            rewind($fp);
        }

        // don't love this
        foreach ($row as $key => $value) {
            $row[$key] = str_replace(["\r", "\n"], ' ', $value);
        }

        // ascii 9 is tab, and ascii 0 is NULL
        // http://www.asciitable.com/
        $delimiter = $format === 'csv' ? ',' : chr(9);
        $enclosure = $format === 'csv' ? '"' : chr(0);

        if (fputcsv($fp, $row, $delimiter, $enclosure) === false) {
            return false;
        }

        rewind($fp);
        $csv = fgets($fp);

        return $csv;
    }

    /**
     * @param $headers
     * @return Row
     */
    private function createHeaderRow($headers)
    {
        $cells = [];

        foreach ($headers as $header) {
            $cells[] = WriterEntityFactory::createCell($header['header'] ?? $header);
        }

        return WriterEntityFactory::createRow($cells);
    }

    /**
     * @param $data
     * @return array
     */
    private function createDataRows($data)
    {
        $rows = [];

        $dataToParse = $data['order_data'] ?? $data['rows'] ?? $data;

        foreach ($dataToParse as $row) {
            $cells = [];

            foreach ($row as $fieldName => $rowData) {
                if ($fieldName == 'actions') {
                    $rowData = '';
                }

                $cell = WriterEntityFactory::createCell($rowData ?? '');
                $cells[] = $cell;
            }

            $rows[] = WriterEntityFactory::createRow($cells);
        }

        return $rows;
    }
}
