<?php

class shopJivoPluginBackendSaveController extends waJsonController {

    public function execute() {
        try {
            $jivo = wa()->getPlugin('jivo');
            $show_plugin = waRequest::post('show_plugin', 0, waRequest::TYPE_INT);
            $custom_widget_pos = waRequest::post('custom_widget_pos', 0, waRequest::TYPE_INT);
            $custom_widget_online_text = waRequest::post('custom_widget_online_text', '', waRequest::TYPE_STRING_TRIM);
            $custom_widget_offline_text = waRequest::post('custom_widget_offline_text', '', waRequest::TYPE_STRING_TRIM);
            $custom_widget_bg_color = waRequest::post('custom_widget_bg_color', '', waRequest::TYPE_STRING_TRIM);
            $custom_widget_font_color = waRequest::post('custom_widget_font_color', '', waRequest::TYPE_STRING_TRIM);
            
            if(empty($custom_widget_bg_color)) $custom_widget_bg_color = '#c12613';
            if(empty($custom_widget_font_color)) $custom_widget_font_color = '#ffffff';
            
            if (!preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $custom_widget_bg_color) || !preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $custom_widget_font_color)) {
		throw new waException(_wp('Invalid value of background color or font color'));
	    }
            
            //Save
            $jivo->saveSettings(array(
                'show_plugin' => $show_plugin,
                'custom_widget_pos' => $custom_widget_pos,
                'custom_widget_online_text' => $jivo->textFilter($custom_widget_online_text),
                'custom_widget_offline_text' => $jivo->textFilter($custom_widget_offline_text),
                'custom_widget_bg_color' => $custom_widget_bg_color,
                'custom_widget_font_color' => $custom_widget_font_color
            ));

            $code = waRequest::post('code');
            if (!$code)
                throw new waException(_wp('The JivoChat code is not specified').'.');
            $code_file = $jivo->getPath() . 'Jivo_edited.html';
            $f = fopen($code_file, 'w');
            $error_msg = _wp('Unable to save the JivoChat code, check the write permission').' '. $code_file;
            if (!$f)
                throw new waException($error_msg);
            if (!fwrite($f, $code))
                throw new waException($error_msg);
            fclose($f);

            $this->response['message'] = _wp('Saved');
        } catch (Exception $e) {
            $this->setError($e->getMessage());
        }
    }

}