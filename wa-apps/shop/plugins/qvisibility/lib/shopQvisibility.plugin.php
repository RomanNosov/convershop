<?php
class shopQvisibilityPlugin extends shopPlugin
{
    public function backendProducts()
    {
        $view = wa()->getView();
        $view->assign('plugin_url', $this->getPluginStaticUrl());
        $view->assign('plugin_version', $this->getVersion());

        return array(
            'toolbar_section' => $view->fetch($this->path.'/templates/Toolbar.html')
        );
    }
}