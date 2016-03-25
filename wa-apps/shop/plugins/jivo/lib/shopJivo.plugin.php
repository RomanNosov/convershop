<?php

class shopJivoPlugin extends shopPlugin {

    public function frontendHead() {
        if ($this->getSettings('show_plugin') && $this->getSettings('custom_widget_pos')>0) {
            $head = "\n<!-- JivoChat Plugin -->\n";
            $head .= '<link href="' . $this->getPluginStaticUrl(true) . 'css/jivosite.css" rel="stylesheet">';
            $head .= '<script src="' . $this->getPluginStaticUrl(true) . 'js/jivosite.js" type="text/javascript"></script>';
            $head .= "\n<!-- JivoChat Plugin -->\n";
            return $head;
        }
    }

    public function frontendFooter() {
        $view = wa()->getView();
        $jivo = wa()->getPlugin('jivo');
        if ($jivo->getSettings('show_plugin')) {
            $custom_widget_settings = array(
                'custom_widget_pos' => (int) $this->getSettings('custom_widget_pos'),
                'custom_widget_online_text' => $this->getSettings('custom_widget_online_text'),
                'custom_widget_offline_text' => $this->getSettings('custom_widget_offline_text'),
                'custom_widget_bg_color' => $this->getSettings('custom_widget_bg_color'),
                'custom_widget_font_color' => $this->getSettings('custom_widget_font_color')
            );
            $code_dir = $jivo->getPath();
            if (file_exists($code_dir . 'Jivo_edited.html')) {
                return '<script type="text/javascript">jivo_custom_widget_settings = ' . json_encode($custom_widget_settings) . '</script>' .
                        $view->fetch($code_dir . 'Jivo_edited.html');
            } else {
                return '<script type="text/javascript">alert("' . _wp('JivoChat code is not set') . '.\n' . _wp('Add JivoChat code in the plugin settings') . '.");</script>';
            }
        }
    }

    public function getPath() {
        return wa()->getDataPath('plugins' . DIRECTORY_SEPARATOR . 'jivo', false) . DIRECTORY_SEPARATOR;
    }

    public function textFilter($text = "") {
        $text = strip_tags($text);
        $text = substr($text, 0, 255);
        $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        return $text;
    }

}
