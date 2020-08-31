<?php
/**
 * @author: liaofei<136327134@qq.com>
 * @day: 2020/7/1
 */

namespace app\services\system\config;


use app\dao\system\config\SystemConfigDao;
use app\services\BaseServices;
use crmeb\exceptions\AdminException;
use crmeb\services\FormBuilder;

/**
 * 系统配置
 * Class SystemConfigServices
 * @package app\services\system\config
 * @method count(array $where = []) 获取指定条件下的count
 * @method save(array $data) 保存数据
 * @method get(int $id, ?array $field = []) 获取一条数据
 * @method update($id, array $data, ?string $key = null) 修改数据
 * @method delete(int $id, ?string $key = null) 删除数据
 * @method getUploadTypeList(string $configName) 获取上传配置中的上传类型
 */
class SystemConfigServices extends BaseServices
{
    /**
     * form表单句柄
     * @var FormBuilder
     */
    protected $builder;

    /**
     * 表单数据切割符号
     * @var string
     */
    protected $cuttingStr = '=>';

    /**
     * SystemConfigServices constructor.
     * @param SystemConfigDao $dao
     */
    public function __construct(SystemConfigDao $dao, FormBuilder $builder)
    {
        $this->dao = $dao;
        $this->builder = $builder;
    }

    /**
     * 获取单个系统配置
     * @param string $configName
     * @param null $default
     * @return mixed|null
     */
    public function getConfigValue(string $configName, $default = null)
    {
        return json_decode($this->dao->getConfigValue($configName), true) ?: $default;
    }

    /**
     * 获取全部配置
     * @param array $configName
     * @return array
     */
    public function getConfigAll(array $configName = [])
    {
        return array_map(function ($item) {
            return json_decode($item, true);
        }, $this->dao->getConfigAll($configName));
    }

    /**
     * 获取配置并分页
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getConfigList(array $where)
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getConfigList($where, $page, $limit);
        $count = $this->dao->count($where);
        foreach ($list as &$item) {
            $item['value'] = $item['value'] ? json_decode($item['value'], true) ?: '' : '';
            if ($item['type'] == 'radio' || $item['type'] == 'checkbox') {
                $item['value'] = $this->getRadioOrCheckboxValueInfo($item['menu_name'], $item['value']);
            }
            if ($item['type'] == 'upload' && !empty($item['value'])) {
                $srr = explode(',', $item['value']);
                foreach ($srr as $key => $value) {
                    $tidy_srr[$key]['filepath'] = $value;
                    $tidy_srr[$key]['filename'] = basename($value);
                }
                $item['value'] = $tidy_srr;
            }
        }
        return compact('count', 'list');
    }

    /**
     * 获取单选按钮或者多选按钮的显示值
     * @param $menu_name
     * @param $value
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getRadioOrCheckboxValueInfo(string $menu_name, $value): string
    {
        $option = [];
        $config_one = $this->dao->getOne(['menu_name' => $menu_name]);
        if (!$config_one) {
            return '';
        }
        $parameter = explode("\n", $config_one['parameter']);
        foreach ($parameter as $k => $v) {
            if (isset($v) && strlen($v) > 0) {
                $data = explode('=>', $v);
                $option[$data[0]] = $data[1];
            }
        }
        $str = '';
        if (is_array($value)) {
            foreach ($value as $v) {
                $str .= $option[$v] . ',';
            }
        } else {
            $str .= !empty($value) ? $option[$value] ?? '' : $option[0] ?? '';
        }
        return $str;
    }

    /**
     * 获取系统配置信息
     * @param int $tabId
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getReadList(int $tabId)
    {
        $info = $this->dao->getConfigTabAllList($tabId);
        foreach ($info as $k => $v) {
            if (!is_null(json_decode($v['value'])))
                $info[$k]['value'] = json_decode($v['value'], true);
            if ($v['type'] == 'upload' && !empty($v['value'])) {
                if ($v['upload_type'] == 1 || $v['upload_type'] == 3) $info[$k]['value'] = explode(',', $v['value']);
            }
        }
        return $info;
    }

    /**
     * 创建单行表单
     * @param string $type
     * @param array $data
     * @return array
     */
    public function createTextForm(string $type, array $data)
    {
        $formbuider = [];
        switch ($type) {
            case 'input':
                $data['value'] = json_decode($data['value'], true) ?: '';
                $formbuider[] = $this->builder->input($data['menu_name'], $data['info'], $data['value'])->info($data['desc'])->placeholder($data['desc'])->col(13);
                break;
            case 'number':
                $data['value'] = json_decode($data['value'], true) ?: 0;
                $formbuider[] = $this->builder->number($data['menu_name'], $data['info'], $data['value'])->info($data['desc']);
                break;
            case 'dateTime':
                $formbuider[] = $this->builder->dateTime($data['menu_name'], $data['info'], $data['value'])->info($data['desc']);
                break;
            case 'color':
                $data['value'] = json_decode($data['value'], true) ?: '';
                $formbuider[] = $this->builder->color($data['menu_name'], $data['info'], $data['value'])->info($data['desc']);
                break;
            default:
                $data['value'] = json_decode($data['value'], true) ?: '';
                $formbuider[] = $this->builder->input($data['menu_name'], $data['info'], $data['value'])->info($data['desc'])->placeholder($data['desc'])->col(13);
                break;
        }
        return $formbuider;
    }

    /**
     * 创建多行文本框
     * @param array $data
     * @return mixed
     */
    public function createTextareaForm(array $data)
    {
        $data['value'] = json_decode($data['value'], true) ?: '';
        $formbuider[] = $this->builder->textarea($data['menu_name'], $data['info'], $data['value'])->placeholder($data['desc'])->info($data['desc'])->rows(6)->col(13);
        return $formbuider;
    }

    /**
     * 创建当选表单
     * @param array $data
     * @return array
     * @throws \FormBuilder\exception\FormBuilderException
     */
    public function createRadioForm(array $data)
    {
        $formbuider = [];
        $data['value'] = json_decode($data['value'], true) ?: '0';
        $parameter = explode("\n", $data['parameter']);
        $options = [];
        if ($parameter) {
            foreach ($parameter as $v) {
                if (strstr($v, $this->cuttingStr) !== false) {
                    $pdata = explode($this->cuttingStr, $v);
                    $options[] = ['label' => $pdata[1], 'value' => $pdata[0]];
                }
            }
            $formbuider[] = $this->builder->radio($data['menu_name'], $data['info'], $data['value'])->options($options)->info($data['desc'])->col(13);
        }
        return $formbuider;
    }

    /**
     * 创建上传组件表单
     * @param int $type
     * @param array $data
     * @return array
     */
    public function createUpoadForm(int $type, array $data)
    {
        $formbuider = [];
        switch ($type) {
            case 1:
                $data['value'] = json_decode($data['value'], true) ?: '';
                $formbuider[] = $this->builder->frameImageOne($data['menu_name'], $data['info'], $this->url('admin/widget.images/index', ['fodder' => $data['menu_name']], true), $data['value'])
                    ->icon('ios-image')->width('60%')->height('435px')->info($data['desc'])->col(13);
                break;
            case 2:
                $data['value'] = json_decode($data['value'], true) ?: [];
                $formbuider[] = $this->builder->frameImages($data['menu_name'], $data['info'], $this->url('admin/widget.images/index', ['fodder' => $data['menu_name']], true), $data['value'])
                    ->maxLength(5)->icon('ios-image')->width('60%')->height('435px')
                    ->info($data['desc'])->col(13);
                break;
            case 3:
                $data['value'] = json_decode($data['value'], true);
                $formbuider[] = $this->builder->uploadFileOne($data['menu_name'], $data['info'], $this->url('/adminapi/file/upload/1', ['type' => 1]), $data['value'])
                    ->name('file')->info($data['desc'])->col(13)->headers([
                        'Authori-zation' => app()->request->header('Authori-zation'),
                    ]);
                break;
        }
        return $formbuider;
    }

    /**
     * 创建单选框
     * @param array $data
     * @return array
     * @throws \FormBuilder\exception\FormBuilderException
     */
    public function createCheckboxForm(array $data)
    {
        $formbuider = [];
        $data['value'] = json_decode($data['value'], true) ?: [];
        $parameter = explode("\n", $data['parameter']);
        $options = [];
        if ($parameter) {
            foreach ($parameter as $v) {
                if (strstr($v, $this->cuttingStr) !== false) {
                    $pdata = explode($this->cuttingStr, $v);
                    $options[] = ['label' => $pdata[1], 'value' => $pdata[0]];
                }
            }
            $formbuider[] = $this->builder->checkbox('value', $data['info'], $data['value'])->options($options)->info($data['desc'])->col(13);
        }
        return $formbuider;
    }

    /**
     * 创建选择框表单
     * @param array $data
     * @return array
     * @throws \FormBuilder\exception\FormBuilderException
     */
    public function createSelectForm(array $data)
    {
        $formbuider = [];
        $data['value'] = json_decode($data['value'], true) ?: [];
        $parameter = explode("\n", $data['parameter']);
        $options = [];
        if ($parameter) {
            foreach ($parameter as $v) {
                if (strstr($v, $this->cuttingStr) !== false) {
                    $pdata = explode($this->cuttingStr, $v);
                    $options[] = ['label' => $pdata[1], 'value' => $pdata[0]];
                }
            }
            $formbuider[] = $this->builder->select($data['menu_name'], $data['info'], $data['value'])->options($options)->info($data['desc'])->col(13);
        }
        return $formbuider;
    }

    /**
     * 获取系统配置表单
     * @param int $id
     * @param array $formData
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function createConfigForm(int $tabId, array $formData = [])
    {
        $list = $this->dao->getConfigTabAllList($tabId);
        $formbuider = [];
        foreach ($list as $data) {
            switch ($data['type']) {
                case 'text'://文本框
                    $formbuider = array_merge($formbuider, $this->createTextForm($data['input_type'], $data));
                    break;
                case 'textarea'://多行文本框
                    $formbuider = array_merge($formbuider, $this->createTextareaForm($data));
                    break;
                case 'radio'://单选框
                    $formbuider = array_merge($formbuider, $this->createRadioForm($data));
                    break;
                case 'upload'://文件上传
                    $formbuider = array_merge($formbuider, $this->createUpoadForm((int)$data['upload_type'], $data));
                    break;
                case 'checkbox'://多选框
                    $formbuider = array_merge($formbuider, $this->createCheckboxForm($data));
                    break;
                case 'select'://多选框
                    $formbuider = array_merge($formbuider, $this->createSelectForm($data));
                    break;
            }
        }
        return $formbuider;
    }

    /**
     * 系统配置form表单创建
     * @param int $tabId
     * @return array
     * @throws \FormBuilder\exception\FormBuilderException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getConfigForm(int $tabId)
    {
        /** @var SystemConfigTabServices $service */
        $service = app()->make(SystemConfigTabServices::class);
        $title = $service->value(['id' => $tabId], 'title');
        return create_form($title, $this->createConfigForm($tabId), $this->url('/setting/config/save_basics'), 'POST');
    }

    /**
     * 修改配置获取form表单
     * @param int $id
     * @return array
     * @throws \FormBuilder\exception\FormBuilderException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function editConfigForm(int $id)
    {
        $menu = $this->dao->get($id)->getData();
        if (!$menu) {
            throw new AdminException('修改数据不存在!');
        }
        /** @var SystemConfigTabServices $service */
        $service = app()->make(SystemConfigTabServices::class);
        $formbuider = [];
        $formbuider[] = $this->builder->input('menu_name', '字段变量', $menu['menu_name'])->disabled(1);
        $formbuider[] = $this->builder->hidden('type', $menu['type']);
        $formbuider[] = $this->builder->select('config_tab_id', '分类', (string)$menu['config_tab_id'])->setOptions($service->getSelectForm());
        $formbuider[] = $this->builder->input('info', '配置名称', $menu['info'])->autofocus(1);
        $formbuider[] = $this->builder->input('desc', '配置简介', $menu['desc']);
        switch ($menu['type']) {
            case 'text':
                $menu['value'] = json_decode($menu['value'], true);
                $formbuider[] = $this->builder->select('input_type', '类型', $menu['input_type'])->setOptions([
                    ['value' => 'input', 'label' => '文本框']
                    , ['value' => 'dateTime', 'label' => '时间']
                    , ['value' => 'color', 'label' => '颜色']
                    , ['value' => 'number', 'label' => '数字']
                ]);
                //输入框验证规则
                $formbuider[] = $this->builder->input('value', '默认值', $menu['value']);
                if (!empty($menu['required'])) {
                    $formbuider[] = $this->builder->number('width', '文本框宽(%)', $menu['width']);
                    $formbuider[] = $this->builder->input('required', '验证规则', $menu['required'])->placeholder('多个请用,隔开例如：required:true,url:true');
                }
                break;
            case 'textarea':
                $menu['value'] = json_decode($menu['value'], true);
                //多行文本
                if (!empty($menu['high'])) {
                    $formbuider[] = $this->builder->textarea('value', '默认值', $menu['value'])->rows(5);
                    $formbuider[] = $this->builder->number('width', '文本框宽(%)', $menu['width']);
                    $formbuider[] = $this->builder->number('high', '多行文本框高(%)', $menu['high']);
                } else {
                    $formbuider[] = $this->builder->input('value', '默认值', $menu['value']);
                }
                break;
            case 'radio':
                $formbuider = array_merge($formbuider, $this->createRadioForm($menu));
                //单选和多选参数配置
                if (!empty($menu['parameter'])) {
                    $formbuider[] = $this->builder->textarea('parameter', '配置参数', $menu['parameter'])->placeholder("参数方式例如:\n1=白色\n2=红色\n3=黑色");
                }
                break;
            case 'checkbox':
                $formbuider = array_merge($formbuider, $this->createCheckboxForm($menu));
                //单选和多选参数配置
                if (!empty($menu['parameter'])) {
                    $formbuider[] = $this->builder->textarea('parameter', '配置参数', $menu['parameter'])->placeholder("参数方式例如:\n1=白色\n2=红色\n3=黑色");
                }
                break;
            case 'upload':
                $formbuider = array_merge($formbuider, $this->createUpoadForm(($menu['upload_type']), $menu));
                //上传类型选择
                if (!empty($menu['upload_type'])) {
                    $formbuider[] = $this->builder->radio('upload_type', '上传类型', $menu['upload_type'])->options([['value' => 1, 'label' => '单图'], ['value' => 2, 'label' => '多图'], ['value' => 3, 'label' => '文件']]);
                }
                break;
        }
        $formbuider[] = $this->builder->number('sort', '排序', $menu['sort']);
        $formbuider[] = $this->builder->radio('status', '状态', $menu['status'])->options([['value' => 1, 'label' => '显示'], ['value' => 2, 'label' => '隐藏']]);
        return create_form('编辑字段', $formbuider, $this->url('/setting/config/' . $id), 'PUT');
    }

    /**
     * 字段状态
     * @return array
     */
    public function formStatus(): array
    {
        return [['value' => 1, 'label' => '显示'], ['value' => 2, 'label' => '隐藏']];
    }

    /**
     * 选择文文件类型
     * @return array
     */
    public function uploadType(): array
    {
        return [
            ['value' => 1, 'label' => '单图']
            , ['value' => 2, 'label' => '多图']
            , ['value' => 3, 'label' => '文件']
        ];
    }

    /**
     * 选择文本框类型
     * @return array
     */
    public function textType(): array
    {
        return [
            ['value' => 'input', 'label' => '文本框']
            , ['value' => 'dateTime', 'label' => '时间']
            , ['value' => 'color', 'label' => '颜色']
            , ['value' => 'number', 'label' => '数字']
        ];
    }

    /**
     * 获取创建配置规格表单
     * @param int $type
     * @param int $tab_id
     * @return array
     */
    public function createFormRule(int $type, int $tab_id): array
    {
        /** @var SystemConfigTabServices $service */
        $service = app()->make(SystemConfigTabServices::class);
        $formbuider = [];
        $form_type = '';
        $info_type = [];
        $parameter = [];
        switch ($type) {
            case 0://文本框
                $form_type = 'text';
                $info_type = $this->builder->select('input_type', '类型')->setOptions($this->textType());
                $parameter[] = $this->builder->input('value', '默认值');
                $parameter[] = $this->builder->number('width', '文本框宽(%)', 100);
                $parameter[] = $this->builder->input('required', '验证规则')->placeholder('多个请用,隔开例如：required:true,url:true');
                break;
            case 1://多行文本框
                $form_type = 'textarea';
                $parameter[] = $this->builder->textarea('value', '默认值');
                $parameter[] = $this->builder->number('width', '文本框宽(%)', 100);
                $parameter[] = $this->builder->number('high', '多行文本框高(%)', 5);
                break;
            case 2://单选框
                $form_type = 'radio';
                $parameter[] = $this->builder->textarea('parameter', '配置参数')->placeholder("参数方式例如:\n1=>男\n2=>女\n3=>保密");
                $parameter[] = $this->builder->input('value', '默认值');
                break;
            case 3://文件上传
                $form_type = 'upload';
                $parameter[] = $this->builder->radio('upload_type', '上传类型', 1)->options($this->uploadType());
                break;
            case 4://多选框
                $form_type = 'checkbox';
                $parameter[] = $this->builder->textarea('parameter', '配置参数')->placeholder("参数方式例如:\n1=>白色\n2=>红色\n3=>黑色");
                break;
            case 5://下拉框
                $form_type = 'select';
                $parameter[] = $this->builder->textarea('parameter', '配置参数')->placeholder("参数方式例如:\n1=>白色\n2=>红色\n3=>黑色");
                break;
        }
        if ($form_type) {
            $formbuider[] = $this->builder->hidden('type', $form_type);
            $formbuider[] = $this->builder->select('config_tab_id', '分类', $tab_id)->setOptions($service->getSelectForm());
            if ($info_type) {
                $formbuider[] = $info_type;
            }
            $formbuider[] = $this->builder->input('info', '配置名称')->autofocus(1);
            $formbuider[] = $this->builder->input('menu_name', '字段变量')->placeholder('例如：site_url');
            $formbuider[] = $this->builder->input('desc', '配置简介');
            $formbuider = array_merge($formbuider, $parameter);
            $formbuider[] = $this->builder->number('sort', '排序', 0);
            $formbuider[] = $this->builder->radio('status', '状态', 1)->options($this->formStatus());
        }
        return create_form('添加字段', $formbuider, $this->url('/setting/config'), 'POST');
    }

    /**
     * radio 和 checkbox规则的判断
     * @param $data
     * @return bool
     */
    public function valiDateRadioAndCheckbox($data)
    {
        $option = [];
        $option_new = [];
        $data['parameter'] = str_replace("\r\n", "\n", $data['parameter']);//防止不兼容
        $parameter = explode("\n", $data['parameter']);
        if (count($parameter) < 2) {
            throw new AdminException('请输入正确格式的配置参数');
        }
        foreach ($parameter as $k => $v) {
            if (isset($v) && !empty($v)) {
                $option[$k] = explode('=>', $v);
            }
        }
        if (count($option) < 2) {
            throw new AdminException('请输入正确格式的配置参数');
        }
        $bool = 1;
        foreach ($option as $k => $v) {
            $option_new[$k] = $option[$k][0];
            foreach ($v as $kk => $vv) {
                $vv_num = strlen($vv);
                if (!$vv_num) {
                    $bool = 0;
                }
            }
        }
        if (!$bool) {
            throw new AdminException('请输入正确格式的配置参数');
        }
        $num1 = count($option_new);//提取该数组的数目
        $arr2 = array_unique($option_new);//合并相同的元素
        $num2 = count($arr2);//提取合并后数组个数
        if ($num1 > $num2) {
            throw new AdminException('请输入正确格式的配置参数');
        }
        return true;
    }
}
