<?php

class shopQvisibilityProductsCollection extends shopProductsCollection
{
    public function getTypeIds()
    {
        $sql = $this->getSQL();
        $sql = 'SELECT DISTINCT p.type_id '.$sql;
        $type_ids = $this->getModel()->query($sql)->fetchAll(null, true);
        return array_filter($type_ids);
    }


    public function getProducts($fields = "*", $offset = 0, $limit = null, $escape = true)
    {
        if (is_bool($limit)) {
            $escape = $limit;
            $limit = null;
        }
        if ($limit === null) {
            if ($offset) {
                $limit = $offset;
                $offset = 0;
            } else {
                $limit = 50;
            }
        }

        $sql = $this->getSQL();

        // for dynamic set
        if ($this->hash[0] == 'set' && !empty($this->info['id']) && $this->info['type'] == shopSetModel::TYPE_DYNAMIC) {
            $this->count();
            if ($offset + $limit > $this->count) {
                $limit = $this->count - $offset;
            }
        }

        $sql = "SELECT ".($this->joins && !$this->group_by ? 'DISTINCT ' : '').$this->getFields($fields)." ".$sql;
        $sql .= $this->_getGroupBy();
        if ($this->having) {
            $sql .= " HAVING ".implode(' AND ', $this->having);
        }
        $sql .= $this->_getOrderBy();
        $sql .= " LIMIT ".($offset ? $offset.',' : '').(int) $limit;

        $data = $this->getModel()->query($sql)->fetchAll('id');
        if (!$data) {
            return array();
        }
        return $data;
    }
}