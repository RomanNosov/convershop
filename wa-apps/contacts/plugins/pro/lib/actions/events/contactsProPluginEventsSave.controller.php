<?php

class contactsProPluginEventsSaveController extends waJsonController
{    
    public function execute()
    {
        if (wa()->getUser()->getRights('contacts', 'backend') <= 1) {
            throw new waRightsException(_w('Access denied'));
        }
        
        $id = $this->getRequest()->request('id', null, waRequest::TYPE_INT);
        $data = $this->getData();
        if (!$id) {
            $this->response = $this->addEvent($data);
        } else {
            $this->response = $this->editEvent($id, $data);
        }
        
        if ($this->response['id']) {
            if (!empty($data['notification'])) {
                $notificatoin = $this->getNotificationByEvent($this->response['id']);
                if ($notificatoin['id']) {
                    $this->editNotification($notificatoin['id'], $data['notification']);
                } else {
                    $notificatioin_id = $this->addNotification($this->response['id'], $data['notification']);
                    $event_params = array(
                        'type' => 'event',
                        'id' => $notificatioin_id,
                        'plugin' => 'pro'
                    );
                    wa('contacts')->event('notification_save', $event_params);
                }
            } else {
                $notificatoin = $this->getNotificationByEvent($this->response['id']);
                if ($notificatoin['id']) {
                    $this->deleteNotification($notificatoin['id']);
                }
            }
        }
        
    }
    
    public function getData()
    {
        $data = array();
        $data_r = $this->getRequest()->request('data');
        $data['contact_id'] = array_map('intval', ifset($data_r['contact_id'], array()));
        unset($data_r['contact_id']);
        
        $data_r['start_datetime'] = $this->parseDatetime($data_r['start_datetime']);
        $data_r['end_datetime'] = $this->parseDatetime($data_r['end_datetime']);
        
        $data['start_datetime'] = $this->formatDatetime($data_r['start_datetime']);
        $start_datetime = $data_r['start_datetime'];
        unset($data_r['start_datetime']);
        
        $data['end_datetime'] = $this->formatDatetime($data_r['end_datetime']);
        unset($data_r['end_datetime']);
        
        $data['repeat'] = null;
        $repeat = null;
        if (!empty($data_r['repeat']) && in_array($data_r['repeat'], array('day', 'week', 'month', 'year'))) {
            $data['repeat'] = $data_r['repeat'];
            $repeat = $data_r['repeat'];
        }
        unset($data_r['repeat']);
        
        foreach ($data_r as $k => $v) {
            if (!empty($v)) {
                $data[$k] = $v;
            }
        }
        
        if (strtotime($data['end_datetime']) < strtotime($data['start_datetime'])) {
            unset($data['end_datetime']);
        }
        
        $notification = $this->getRequest()->request('notification');
        $prior = $notification['prior'];
        
        $valid = false;
        if ($prior) {
            if ($start_datetime['date'] && in_array($prior, array('minutes_15', 'minutes_60', 'days_0', 'days_1', 'days_7'))) {
                if ($start_datetime['hour']) {
                    if ($repeat === 'day' && in_array($prior, array('minutes_15', 'minutes_60', 'days_0'))) {
                        $valid = true;
                    } else if ($repeat === 'week' && in_array($prior, array('minutes_15', 'minutes_60', 'days_0', 'days_1'))) {
                        $valid = true;
                    } else {
                        $valid = true;
                    }
                } else {
                    if ($repeat === 'day' && in_array($prior, array('days_0'))) {
                        $valid = true;
                    } else if ($repeat === 'week' && in_array($prior, array('days_0', 'days_1'))) {
                        $valid = true;
                    } else if (in_array($prior, array('days_0', 'days_1', 'days_7'))) {
                        $valid = true;
                    }
                }
            }
        }
        if (!$valid) {
            $prior = '';
        }
        if ($prior) {
            $data['notification'] = array();
            if ($prior === 'minutes_15') {
                $data['notification']['prior_minutes'] = 15;
            }
            if ($prior === 'minutes_60') {
                $data['notification']['prior_minutes'] = 60;
            }
            if ($prior === 'days_0') {
                $data['notification']['prior_days'] = 0;
            }
            if ($prior === 'days_1') {
                $data['notification']['prior_days'] = 1;
            }
            if ($prior === 'days_7') {
                $data['notification']['prior_days'] = 7;
            }
        }
        
        return $data;
    }
    
    // helper for parsing datetime from post
    private function parseDatetime($datetime)
    {
        $datetime['time'] = $datetime['time'] ? $datetime['time'] : '00:00';
        $parsed = date_parse($datetime['time']);
        unset($datetime['time']);
        $datetime['hour'] = $parsed['hour'];
        $datetime['minute'] = $parsed['minute'];
        return $datetime;
    }
    
    private function formatDatetime($datetime) 
    {        
        $dt = '';
        $empty = true;
        foreach ($datetime as $k => $v) {
            if (!empty($v)) {
                $empty = false;
                break;
            }
        }
        if ($empty) {
            return null;
        }
        $dt .= ($datetime['date'] ? date('Y-m-d', strtotime($datetime['date'])) : date('Y-m-d'));
        $dt .= ' ';
        
        if ($datetime['hour']) {
            $datetime['hour'] = (int) $datetime['hour'];
            if ($datetime['hour'] < 10) {
                $datetime['hour'] = '0' . $datetime['hour'];
            }
            $dt .= $datetime['hour'];
        } else {
            $dt .= '00';
        }
        $dt .= ':';
        if ($datetime['minute']) {
            $datetime['minute'] = (int) $datetime['minute'];
            if ($datetime['minute'] < 10) {
                $datetime['minute'] = '0' . $datetime['minute'];
            }
            $dt .= $datetime['minute'];
        } else {
            $dt .= '00';
        }
        $dt .= ':00';
        
        return $dt;
    }
    
    public function addEvent($data)
    {
        $m = new contactsEventModel();
        $event_id = $m->add($data);
        $event = $m->getById($event_id);
        $this->logAction('event_add', null, ifset($data['contact_id'][0]), $event['contact_id']);
        return $event;
    }
    
    public function editEvent($id, $data)
    {
        $m = new contactsEventModel();
        $m->edit($id, $data);
        $event = $m->getById($id);
        $this->logAction('event_edit', null, ifset($data['contact_id'][0]), $event['contact_id']);
        return $event;
    }
    
    public function getNotificationByEvent($event_id)
    {
        $nem = new contactsNotificationEventsModel();
        return $nem->getNotificationItemByEvent($event_id, wa()->getUser()->getId());
    }
    
    public function addNotification($event_id, $notification) 
    {
        $nem = new contactsNotificationEventsModel();
        $notification['event_id'] = $event_id;
        return $nem->add($notification);
    }
    
    public function editNotification($notification_id, $notification)
    {
        $nem = new contactsNotificationEventsModel();
        $nem->update($notification_id, $notification);
    }
    
    public function deleteNotification($notification_id)
    {
        $nem = new contactsNotificationEventsModel();
        return $nem->delete($notification_id);
    }
}