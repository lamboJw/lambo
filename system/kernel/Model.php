<?php


namespace system\kernel;


use PDOStatement;
use RuntimeException;

class Model extends BaseModel
{
    //数据库
    protected string $db;

    //表名
    protected string $tableName;

    //主键列名
    protected string $keyName = 'id';

    //允许load方法仅获取某些字段
    protected array $able_columns = [];

    //不允许load方法获取某些字段
    protected array $deny_columns = [];

    //创建时间列名
    protected string $add_time_col = 'add_time';

    //更新时间列名
    protected string $edit_time_col = 'edit_time';

    //是否自动管理时间
    protected bool $timestamp = true;

    //时间类型
    protected int $time_type = MODEL_DATETIME;

    public function __construct()
    {
        if (empty($this->db)) {
            throw new RuntimeException('未指定数据库');
        }
        parent::__construct($this->db);
    }

    /**
     * 获取单条数据
     * @param string|int|array $where 查询条件
     * @param string|array $columns 查询字段
     * @param null|array $join 连表
     * @return array|bool
     */
    public function getInfo($where, $columns = '*', $join = null)
    {
        if (empty($where)) {
            return false;
        }
        if (!is_array($where)) {
            $where = [$this->keyName => $where];
        }
        if (empty($join)) {
            $join = $columns;
            $columns = $where;
            $where = null;
        }
        return $this->get($this->tableName, $join, $columns, $where);
    }

    /**
     * @param array $where 查询条件
     * @param array|string $columns 查询字段
     * @param null|array $join 连表查询
     * @param $count @总数量
     * @return array|bool
     */
    public function getList(array $where, $columns = '*', $join = null, &$count = false)
    {
        if (empty($where)) return false;
        if ($columns == ['*']) $columns = '*';
        if ($count !== false) {
            $count_where = $where;
            foreach ($count_where as $k => $v) {
                if (strtoupper($k) == 'LIMIT') {
                    unset($count_where[$k]);
                }
            }
            if (empty($join)) {
                $join = $columns;
                $columns = $where;
                $where = null;
            }
            $count = $this->count($this->tableName, $join, $columns, $where);
        }
        if (empty($join)) {
            $join = $columns;
            $columns = $where;
            $where = null;
        }
        return $this->select($this->tableName, $join, $columns, $where);
    }

    /**
     * 查询数据列表，包含分页信息
     * @param array $where 查询条件
     * @param int $page 页数
     * @param array $option 选项
     * @return array
     */
    public function getListWithPage(array $where, int $page, array $option = [])
    {
        $opt = [
            'pagesize' => 20,
            'page_name' => 'page',
            'columns' => '*',
            'join' => null,
        ];
        $opt = array_merge($opt, $option);
        $count = 0;
        $offset = ($page - 1) * $opt['pagesize'];
        foreach ($where as $k => $v) {
            if (strtoupper($k) == 'LIMIT') {
                unset($where[$k]);
            }
        }
        $where['LIMIT'] = [$offset, $opt['pagesize']];
        $list = $this->getList($where, $opt['columns'], $opt['join'], $count);
        return [
            'list' => $list,
            'count' => $count,
            'page' => $page,
            'pagesize' => $opt['pagesize'],
            'page_name' => $opt['page_name']
        ];
    }

    /**
     * 根据时间类型获取时间
     * @return false|int|string
     */
    private function get_time()
    {
        switch ($this->time_type) {
            case MODEL_DATETIME:
                return date('Y-m-d H:i:s');
                break;
            case MODEL_DATE:
                return date('Y-m-d');
                break;
            case MODEL_UNIX_TIMESTAMP:
                return time();
                break;
            default:
                throw new RuntimeException('Model使用了未定义的时间类型');
        }
    }

    /**
     * @param array $data 插入数据
     * @return string
     */
    public function add($data)
    {
        if (empty($data)) {
            return false;
        }
        if (!isset($data[0])) {   //单条插入
            if ($this->timestamp) {
                $time = $this->get_time();
                $data[$this->add_time_col] = $time;
                $data[$this->edit_time_col] = $time;
            }
        } else {      //多条插入
            if ($this->timestamp) {
                $time = $this->get_time();
                foreach ($data as &$item) {
                    $item[$this->add_time_col] = $time;
                    $item[$this->edit_time_col] = $time;
                }
                unset($item);
            }
        }
        $re = $this->insert($this->tableName, $data);
        if (!empty($this->error()) && $this->error()[0] !== '00000') {
            throw new RuntimeException(implode(',', $this->error()));
        } else {
            return $re;
        }
    }

    /**
     * @param array $data 更新数据
     * @param array $where 更新条件
     * @return int
     */
    public function edit($data, $where)
    {
        if (empty($data) || empty($where)) {
            return false;
        }
        if ($this->timestamp) {
            $data[$this->edit_time_col] = $this->get_time();
        }
        $re = $this->update($this->tableName, $data, $where);
        if (!empty($this->error()) && $this->error()[0] !== '00000') {
            throw new RuntimeException(implode(',', $this->error()));
        } else {
            return $re->rowCount();
        }
    }

    /**
     * @param array $where 删除条件
     * @return int
     */
    public function del($where)
    {
        if (empty($where)) {
            return false;
        }
        $re = $this->delete($this->tableName, $where);
        return $re->rowCount();
    }

    /**
     * 获取当前表的字段名
     * @return array
     */
    public function columns()
    {
        $config = config("database.{$this->db}");
        $table_schema = $config['database'] ?? 'test';
        $result = $this->query("select COLUMN_NAME from information_schema.COLUMNS where table_name = '{$this->tableName}' and table_schema = '{$table_schema}';")->fetchAll();
        $columns = [];
        foreach ($result as $item) {
            $columns[] = $item['COLUMN_NAME'];
        }
        return $columns;
    }

    /**
     * 根据字段名获取表单提交的数据
     * @return array
     */
    public function load()
    {
        $columns = $this->columns();
        $data = [];
        foreach ($columns as $column) {
            if (!empty($this->able_columns) && !in_array($column, $this->able_columns)) {
                continue;
            }
            if (!empty($this->deny_columns) && in_array($column, $this->deny_columns)) {
                continue;
            }
            $data[$column] = request($column);
            if ($data[$column] === null) {
                unset($data[$column]);
            }
        }
        return $data;
    }

    /**
     * 自动判断插入或更新
     * @param $data
     * @return string | int
     */
    public function save($data)
    {
        if (array_key_exists($this->keyName, $data) && !empty($data[$this->keyName])) {
            // 主键值不为空，更新
            $key = $data[$this->keyName];
            unset($data[$this->keyName]);
            return $this->edit($data, [$this->keyName => $key]);
        } else {            //否则直接插入
            return $this->add($data);
        }
    }

    /**
     * 根据条件查询记录，判断更新或插入
     * @param array $where 判断是否存在的条件
     * @param array $data 更新或插入的数据
     * @return string | int
     */
    public function updateOrInsert($where, $data)
    {
        $count = $this->count($this->tableName, '*', $where);
        if ($count > 0) {
            return $this->edit($data, $where);
        } else {
            return $this->add($data);
        }
    }
}