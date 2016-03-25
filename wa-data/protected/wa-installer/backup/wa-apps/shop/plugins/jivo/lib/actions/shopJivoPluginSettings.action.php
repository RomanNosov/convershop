<?php

class shopJivoPluginSettingsAction extends waViewAction {

    public function execute() {
	$jivo = wa()->getPlugin('jivo');
        $code_dir = $jivo->getPath();
        if (file_exists($code_dir.'Jivo_edited.html')) {
            $this->view->assign('code', file_get_contents($code_dir.'Jivo_edited.html'));
        } else {
            $this->view->assign('code', '');
        }        
	$this->view->assign('show_plugin', (int) $jivo->getSettings('show_plugin'));
	$this->view->assign('custom_widget_pos', (int) $jivo->getSettings('custom_widget_pos'));
        $this->view->assign('custom_widget_online_text', $jivo->getSettings('custom_widget_online_text'));
        $this->view->assign('custom_widget_offline_text', $jivo->getSettings('custom_widget_offline_text'));
        $this->view->assign('custom_widget_bg_color', $jivo->getSettings('custom_widget_bg_color'));
        $this->view->assign('custom_widget_font_color', $jivo->getSettings('custom_widget_font_color'));
    }
}