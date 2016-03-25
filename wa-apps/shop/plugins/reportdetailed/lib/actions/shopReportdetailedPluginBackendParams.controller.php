<?php

class shopReportdetailedPluginBackendParamsController extends waViewController
{
    public function execute()
    {
        $data = waRequest::post();
        if(isset($data['set_params'])){
            $setting_model = new waAppSettingsModel();
            $columns = isset($data['columns'])?$data['columns']:array();
            $columns = json_encode($columns);
            $states = isset($data['states'])?$data['states']:array();
            $states = json_encode($states);
            $setting_model->set(array('shop', 'reportdetailed'), 'states', $states);
            $setting_model->set(array('shop', 'reportdetailed'), 'columns', $columns);
            $setting_model->set(array('shop', 'reportdetailed'), 'group', isset($data['group'])?$data['group']:'days');
            $setting_model->set(array('shop', 'reportdetailed'), 'order_date', isset($data['order_date'])?$data['order_date']:'create_datetime');
            $setting_model->set(array('shop', 'reportdetailed'), 'shipping', json_encode(isset($data['shipping'])?$data['shipping']:array()));
            $setting_model->set(array('shop', 'reportdetailed'), 'payment', json_encode(isset($data['payment'])?$data['payment']:array()));
        }
        $this->executeAction(new shopReportdetailedPluginBackendReportAction());
    }
}
