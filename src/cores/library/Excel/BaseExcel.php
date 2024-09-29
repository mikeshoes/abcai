<?php

namespace cores\library\Excel;

use cores\library\Excel\Exception\TemplateException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use think\exception\ValidateException;
use think\file\UploadedFile;
use think\helper\Str;

abstract class BaseExcel
{
    use FailureRowCollection;

    protected array $properties = [];
    private array $data;
    protected array $rules = [];
    protected array $messages = [];
    protected array $fields = [];
    protected array $titles = [];
    protected int $headingRow = 0;
    protected int $maxRow = 1000;
    protected string $headerExplain = '';
    protected string $fileName;
    protected string $sheetName;
    protected bool $interruptOnFail = false;

    public function properties(): array
    {
        return $this->properties;
    }

    public function setData($data)
    {
        $this->data = $data;
    }

    public function getData(): array
    {
        return $this->data;
    }

    protected function beforeDownload(Spreadsheet $spreadsheet)
    {
    }

    public function download()
    {
        $spreadSheet = new Spreadsheet();
        $spreadSheet->getActiveSheet()->setTitle($this->sheetName);
        // 添加属性
        $this->addProperties($spreadSheet);
        // 添加头数据
        $this->addHeaderRow($spreadSheet);
        // 添加数据
        $this->addData($spreadSheet);
        // 允许外部修改spreadsheet的接口
        $this->beforeDownload($spreadSheet);
        // 输出流
        $this->output($spreadSheet);
    }

    /**
     * @throws TemplateException
     */
    public function loadTemplate(UploadedFile $file)
    {
        if (empty($this->titles)) {
            throw new TemplateException("模板title必填");
        }

        $spreadSheet = IOFactory::load($file->getRealPath());
        // 校验模板数据
        $this->validTitle($spreadSheet);

        // 初始化存储行数据的数组
        $this->validData($spreadSheet);
    }

    private function validData(Spreadsheet $spreadsheet)
    {
        $activeSheet = $spreadsheet->getActiveSheet();
        $highestRow = $activeSheet->getHighestDataRow();
        $data = [];
        if ($highestRow > $this->maxRow) {
            throw new ValidateException("超过当前允许导入的最大数据行数[{$this->maxRow}]");
        }
        foreach ($activeSheet->getRowIterator($this->headingRow + 1, $highestRow) as $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false); // 包括空单元格
            $temData = [];
            foreach ($cellIterator as $cell) {
                $temData[] = $cell->getFormattedValue(); // 获取单元格的值
            }
            $validData = array_combine(array_keys($this->titles), $temData);
            $validator = new \think\Validate();
            $validator->message($this->messages);
            $validator->batch(true);
            if ($validator->check($validData, $this->rules)) {
                $data[] = $validData;
            } else if ($this->interruptOnFail) {
                $this->addFailure($row->getRowIndex(), $validData, $validator->getError());
                break;
            } else {
                $this->addFailure($row->getRowIndex(), $validData, $validator->getError());
            }
        }
        if ($this->hasErrors()) {
            throw new ValidateException(implode('\\r\\n', $this->getErrors()));
        }

        $this->setData($data);
    }

    private function validTitle(Spreadsheet $spreadsheet)
    {
        $titleData = [];
        // 获取指定行的单元格数据
        $activeSheet = $spreadsheet->getActiveSheet();
        foreach ($activeSheet->getRowIterator($this->headingRow, $this->headingRow) as $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false); // 包括空单元格
            foreach ($cellIterator as $cell) {
                $titleData[] = $cell->getValue(); // 获取单元格的值
            }
        }

        if (array_values($this->titles) !== $titleData) {
            throw new TemplateException("文档模板不正确");
        }
    }

    private function output(Spreadsheet $spreadsheet)
    {
        // 创建 Xlsx 文件流
        // 清除输出缓冲区
        ob_end_clean();
        // 设置响应头
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header(vsprintf('Content-Disposition: attachment; filename="%s"', [rawurlencode($this->fileName)]));
        header('Cache-Control: max-age=0');
        header('Pragma: public');

        // 创建 Xlsx 文件流并输出到 PHP 输出流
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        // 终止脚本以防止其他输出
        exit;
    }

    private function addHeaderRow(Spreadsheet $spreadsheet)
    {
        if (empty($this->titles) && empty($this->headerExplain)) {
            return;
        }

        $worksheet = $spreadsheet->getActiveSheet();
        $headers = [];
        $needMerge = false;
        if (!empty($this->headerExplain)) {
            $headers[] = [$this->headerExplain];
            $this->explainStyle($worksheet, 'A1');
            $needMerge = true;
        }

        if (!empty($this->titles)) {
            $headers[] = array_values($this->titles);
            // title列增加效果
            $row = count($headers);
            $columnIndex = count($this->titles);
            $column = Coordinate::stringFromColumnIndex($columnIndex);
            $cellRange = "A{$row}:{$column}{$row}";
            $this->headerStyle($worksheet, $cellRange, $row);
            if ($needMerge) {
                $this->explainStyle($worksheet, "A1:{$column}1", 1);
            }
        }
        $worksheet->fromArray($headers);
    }

    private function explainStyle($sheet, $headerRange, $row = 1)
    {
        // 设置字体样式：加粗、字体大小、字体颜色
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getFont()->setSize(14);
        $sheet->getStyle($headerRange)->getFont()->getColor()->setARGB(Color::COLOR_RED); // 设置字体为白色
        // 设置背景颜色：设置背景为渐变色或纯色
        $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID);
        $sheet->getStyle($headerRange)->getFill()->getStartColor()->setARGB(Color::COLOR_YELLOW); // 设置背景为黄色
        $sheet->mergeCells($headerRange);
        // 设置宽度和高度
        $sheet->getRowDimension($row)->setRowHeight(50); // 设置第title行的高度
        // 设置文本对齐方式：水平居中、垂直居中
        $sheet->getStyle($headerRange)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
    }

    private function headerStyle($sheet, $headerRange, $row = 1)
    {
        // 设置字体样式：加粗、字体大小、字体颜色
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getFont()->setSize(14);
        $sheet->getStyle($headerRange)->getFont()->getColor()->setARGB(Color::COLOR_WHITE); // 设置字体为白色
        // 设置背景颜色：设置背景为渐变色或纯色
        $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID);
        $sheet->getStyle($headerRange)->getFill()->getStartColor()->setARGB(Color::COLOR_CYAN); // 设置背景为绿色

        // 设置边框样式
        $sheet->getStyle($headerRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle($headerRange)->getBorders()->getAllBorders()->getColor()->setARGB(Color::COLOR_BLACK); // 设置黑色边框

        // 设置文本对齐方式：水平居中、垂直居中
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle($headerRange)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        // 设置宽度和高度
        $sheet->getRowDimension($row)->setRowHeight(30); // 设置第title行的高度
        $cellRange = Coordinate::extractAllCellReferencesInRange($headerRange);
        // 遍历该范围内的所有列
        foreach ($cellRange as $key => $item) {
            // 将列的索引转换为字母
            list($columnLetter,) = Coordinate::coordinateFromString($item);
            $sheet->getColumnDimension($columnLetter)->setAutoSize(true); // 设置列宽自适应
        }
    }

    private function addData(Spreadsheet $spreadsheet)
    {
        if (empty($this->data)) {
            return;
        }

        $worksheet = $spreadsheet->getActiveSheet();
        $row = $worksheet->getHighestDataRow('A');
        // 格式化数据，仅格式化title中的key
        $data = array_map(function ($item) {
            return array_map(function ($key) use ($item) {
                return $item[$key] ?? array_values($item)[$key] ?? '';
            }, array_keys($this->titles));
        }, $this->data);

        $worksheet->fromArray($data, null, 'A' . ($row + 1));
    }

    private function addProperties(Spreadsheet $spreadsheet)
    {
        $properties = $spreadsheet->getProperties();
        foreach ($this->properties as $property => $value) {
            $method = 'set' . Str::studly($property);
            if (method_exists($properties, $method)) {
                call_user_func_array([$properties, $method], is_array($value) ? $value : [$value]);
            }
        }
    }

    public function setName($fileName)
    {
        $this->fileName = $fileName;
    }
}