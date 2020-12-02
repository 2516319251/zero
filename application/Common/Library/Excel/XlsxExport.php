<?php

namespace application\Common\Library\Excel;

/**
 * 导出 excel
 * Class XlsxExport
 * @package application\Common\Library\Excel
 */
class XlsxExport
{
    /**
     * @var \XLSXWriter $_writer XLSX 对象
     */
    private $_writer;

    /**
     * 构造函数
     * XLSX constructor.
     */
    public function __construct()
    {
        $this->_writer = new \XLSXWriter();
    }

    /**
     * 写入表头
     *
     * @param array $header
     * $header = [
     *    'Date'            => 'date',          // 日期格式
     *    'Number'          => 'integer',       // 整数格式
     *    'Name'            => 'string',        // 字符串格式
     *    'Amount'          => '#0.000',        // 浮点数格式（ . 后面跟几个 0 就表示保留多少位小数）
     *    'Rate'            => '#0.00[$%-100]'  // 百分比格式
     * ]
     * @param string $sheet
     */
    public function writeHeader($header = [], $sheet = 'sheet1')
    {
        $this->_writer->writeSheetHeader($sheet, $header);
    }

    /**
     * 写入需要保存的单条数据
     *
     * @param array $data
     * @param string $sheet
     */
    public function writeRow($data = [], $sheet = 'sheet1')
    {
        // 写入数据
        $this->_writer->writeSheetRow($sheet, $data);
    }

    /**
     * 写入需要保存的数据集
     *
     * @param array $data
     * @param string $sheet
     */
    public function writeRows($data = [], $sheet = 'sheet1')
    {
        foreach ($data as $datum) {
            $this->writeRow($datum, $sheet);
        }
    }

    /**
     * 执行写入下载操作
     *
     * @param string $filename
     * @param bool $isDownloadByBrowser
     * @param string $path
     */
    public function download($filename = '', $isDownloadByBrowser = true, $path = '')
    {
        ini_set('display_errors', 0);
        ini_set('log_errors', 1);
        error_reporting(E_ALL & ~E_NOTICE);

        // 文件名
        if (empty($filename)) {
            $filename = 'excel_' . date('YmdHis');
        } else {
            $filename = $filename . '_' . date('YmdHis');
        }

        // 输出文档
        if ($isDownloadByBrowser) {
            // 设置 header，用于浏览器下载
            header('Content-disposition: attachment; filename="'.\XLSXWriter::sanitize_filename($filename . '.xlsx').'"');
            header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
            header('Content-Transfer-Encoding: binary');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');

            $this->_writer->writeToStdOut();
            exit(0);
        } else {
            $this->_writer->writeToFile($path . '/' . $filename . '.xlsx');
        }
    }
}
