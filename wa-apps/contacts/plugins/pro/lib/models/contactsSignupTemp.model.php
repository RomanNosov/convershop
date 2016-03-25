<?php

/**
 * Class contactsSignupTempModel
 * @desc stores data for users, who did not confirm their email
 */
class contactsSignupTempModel extends waModel
{
    protected $table = 'contacts_signup_temp';

    public function getByHash($hash)
    {
        if (strlen($hash) != 32) {
            return false;
        }
        else {
            return $this->getByField('hash', $hash);
        }
    }

    public function save($hash, $data)
    {
        return $this->insert(array(
            'hash' => $hash,
            'data' => serialize($data),
            'create_datetime' => date('Y-m-d H:i:s')
            ), 2);
    }

    public function deleteByHash($hash)
    {
        if (strlen($hash) != 32) {
            return false;
        }
        else {
            $this->deleteByField('hash', $hash);
        }
    }
}