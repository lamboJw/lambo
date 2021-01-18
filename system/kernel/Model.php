<?php


namespace system\kernel;


use RuntimeException;

class Model extends BaseModel
{
    protected string $db;               //数据库
    protected string $tableName;        //表名
    protected string $keyName = 'id';   //主键列名
    protected array $able_columns = [];    //允许load方法仅获取某些字段
    protected array $deny_columns = [];    //不允许load方法获取某些字段
    protected string $add_time_col = 'add_time';    //创建时间列名
    protected string $edit_time_col = 'edit_time';  //更新时间列名
    protected bool $timestamp = false;  //是否自动管理事件

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
    public function getInfo($where, $columns = "*", $join = null)
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
    public function getList($where, $columns = "*", $join = null, &$count = false)
    {
        if (empty($where)) return false;
        if($columns == ["*"]) $columns = "*";
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


}