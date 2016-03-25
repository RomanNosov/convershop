<?php

class logsBackendSettingsAction extends waViewAction
{
    public function execute()
    {
        $controls = array(
            _w('Enable PHP error log') => waHtmlControl::getControl(waHtmlControl::CHECKBOX, 'settings[php_log]', array(
                'value'       => 1,
                'checked'     => logsHelper::getPhpLogSetting(),
                'description' => '<span class="hint">'
                    ._w('PHP error messages will be saved to file <tt>wa-log/<b>php.log</b></tt>.').'<br><br>'
                    .'<b>'._w('Enable this setting if you cannot, or do not want to, edit files on your server.').'</b><br>'
                    .sprintf(
                        _w('Otherwise add the following lines to your file <tt class>%s</tt>:'),
                        wa()->getConfig()->getRootPath().'/<b>.htaccess</b>'
                    )
                    .'<br><br>'
                    .'<tt>php_flag display_errors Off<br>
                        php_value error_reporting 2147483647<br>
                        php_flag log_errors On<br>
                        php_value error_log ./wa-log/php.log</tt>'
                    .'</span>'
            )),
        );
        $this->view->assign('controls', $controls);
    }
}
