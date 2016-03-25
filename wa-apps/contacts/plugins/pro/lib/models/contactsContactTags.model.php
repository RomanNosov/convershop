<?php

class contactsContactTagsModel extends waModel
{
    protected $table = 'contacts_contact_tags';
    
    /**
     *
     * Assign tags to contact. Tags just assign to contact (without removing if exist for concrete contact)
     * @param array|int $contact_id
     * @param array|int $tag_id
     */
    public function assign($contact_id, $tag_id)
    {
        // define existing tags
        $sql = "SELECT * FROM {$this->table} ";
        $where = $this->getWhereByField('contact_id', $contact_id);
        if ($where) {
            $sql .= " WHERE $where";
        }
        $existed_tags = array();
        foreach ($this->query($sql) as $item) {
            $existed_tags[$item['contact_id']][$item['tag_id']] = true;
        }

        // accumulate candidate for adding
        $add = array();
        foreach ((array)$tag_id as $t_id) {
            foreach ((array)$contact_id as $p_id) {
                if (!isset($existed_tags[$p_id][$t_id])) {
                    $add[] = array('contact_id' => $p_id, 'tag_id' => $t_id);
                }
            }
        }

        // adding itself
        if ($add) {
            $this->multipleInsert($add);
        }

        // recounting counters for this tags
        $tag_model = new contactsTagModel();
        $tag_model->recount($tag_id);

    }
    
    /**
     * @param int|array $contact_id
     * @param int|array $tag_id
     */
    public function delete($contact_id, $tag_id)
    {
        if (!$contact_id) {
            return false;
        }
        $contact_id = (array)$contact_id;

        // delete tags
        $this->deleteByField(array('contact_id' => $contact_id, 'tag_id' => $tag_id));
        // decrease count for tags
        $tag_model = new contactsTagModel();
        $tag_model->recount($tag_id);

    }
    
    
    /**
     * Tag tag of contact(s)
     * @param int|array $contact_id
     * @return array()
     */
    public function getTags($contact_id)
    {
        if (!$contact_id) {
            return array();
        }

        $sql = "
            SELECT t.id, t.name
            FROM ".$this->table." rt
            JOIN contacts_tag t ON rt.tag_id = t.id
            WHERE rt.contact_id IN (i:id)
        ";
        return $this->query($sql, array('id' => $contact_id))->fetchAll('id', true);
    }
    
    public function deleteByContacts($contact_ids)
    {
        $this->deleteByField('contact_id', array_map('intval', (array) $contact_ids));
    }
    
}

