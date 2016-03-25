<?php

/**
 * Class contactsFormModel
 * Model for SignUp(?) forms
 */
class contactsFormModel extends waModel
{
    protected $table = 'contacts_form';

    public function saveToFile($form)
    {
        $default = null;
        $filename = wa()->getDataPath('form-default.txt',true);
        if ( ($default = fopen($filename, 'w')) ) {
            fwrite($default, serialize($form));
            fclose($default);
        }
    }

    public function loadFromFile()
    {
        $filename = wa()->getDataPath('form-default.txt',true);
        $default = null;
        $form = null;
        if ( @($default = fopen($filename, 'r')) ) {
            $form = fread($default, filesize($filename));
            fclose($default);
            $form = unserialize($form);
        }
        return $form;
    }

    public function save($form_id, $form)
    {
        $form_id = $this->insert(array(
            'id' => $form_id,
            'name' => $form['name'],
            'create_datetime' => date("Y-m-d H:i:s"),
            'contact_id' => wa()->getUser()->getId(),
        ), 1);

        return $form_id;
    }

    public function delete($form_id)
    {
        return $this->deleteById($form_id);
    }

}