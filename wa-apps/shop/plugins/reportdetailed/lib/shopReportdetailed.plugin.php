<?php
/**
 * @author Vadim
 * 
 * @license http://www.webasyst.com/terms/#eula Webasyst Commercial
 * @version 1.0.0
 */

/**
 * Main plugin
 *
 * @package webasyst.shop.plugin.syrreporders
 */
class shopReportdetailedPlugin extends shopPlugin
{

    /**
     * Handler for backend_reports hook
     * 
     * @return array
     */
    public function backendReports()
    {
        $view = wa()->getView();
        $settings = $this->getSettings();
        $view->assign('settings', $settings);
        $view->assign('jplot', $this->getPluginStaticUrl().'js/jqplot.dateWAxisRenderer.js');
        $content = $view->fetch($this->path . "/templates/menuitem.html");
        return array('menu_li' => $content);
    }

}
