<?php

namespace cores\library\Excel;

use cores\library\Excel\Exception\TemplateException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use think\exception\ValidateException;
use think\facade\Validate;
use think\file\UploadedFile;
use think\helper\Str;
use think\Response;

abstract class BaseExcel
{
    use FailureRowCollection;

    protected array $properties = [];
    private array $data;
    protected array $rules = [];
    protected array $messages = [];
    protected array $fields = [];
    protected array $titles = [];
    private int $headingRow = 0;
    protected string $headerExplain = '';
    protected string $fileName;
    protected string $sheetName;
    protected bool $interruptOnFail = false;

    public function __construct(
        $data = [],
        $titles = [],
        string $fileName = '',
        string $headerExplain = '',
        array $properties = [],
        string $sheetName = ''
    )
    {
        $this->titles = $titles;
        $this->fileName = $fileName;
        $this->properties = $properties;
        $this->data = $data;
        $this->headerExplain = $headerExplain;
        $this->sheetName = $sheetName;
        if (!empty($this->titles)) {
            $this->headingRow++;
        }
        if (!empty($this->headerExplain)) {
            $this->headingRow++;
        }
    }

    public function properties(): array
    {
        return $this->properties;
    }

    protected function beforeDownload(Spreadsheet $spreadsheet)
    {
    }

    public function download(): Response
    {
        $spreadSheet = new Spreadsheet();
        $spreadSheet->getActiveSheet()->setTitle($this->sheetName);
        // 添加属性
        $this->addProperties($spreadSheet);
        // 允许外部修改spreadsheet的接口
        $this->beforeDownload($spreadSheet);
        // 添加头数据
        $this->addHeaderRow($spreadSheet);
        // 添加数据
        $this->addData($spreadSheet);
        // 输出流
        return $this->output($spreadSheet);
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
        foreach ($activeSheet->getRowIterator($this->headingRow + 1, $highestRow) as $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false); // 包括空单元格
            $temData = [];
            foreach ($cellIterator as $cell) {
                $temData[] = $cell->getValue(); // 获取单元格的值
            }

            $validData = array_combine(array_keys($this->titles), $temData);
            $validator = Validate::message($this->messages);
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
            throw new ValidateException($this->getErrors());
        }

        $this->data = $data;
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
        // 响应头用于文件下载
        return Response::create(function () use ($spreadsheet) {
            // 创建 Xlsx 文件流
            $writer = new Xlsx($spreadsheet);
            // 输出到 PHP 输出流
            $writer->save('php://output');
        }, 'file')->name($this->fileName);
    }

    private function addHeaderRow(Spreadsheet $spreadsheet)
    {
        if (empty($this->titles) && empty($this->headerExplain)) {
            return;
        }

        $worksheet = $spreadsheet->getActiveSheet();
        $headers = [];
        if (!empty($this->headerExplain)) {
            $headers[] = $this->headerExplain;
        }

        if (!empty($this->titles)) {
            $headers[] = array_values($this->titles);
        }
        $worksheet->fromArray($headers);
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