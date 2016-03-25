/*
 Коллбек-функция, вызывается сразу после того, как
 JivoSite окончательно загрузился
 */
function jivo_onLoadCallback() {
    
    $html='<div id="jivo_custom_widget"><span id="jivo_custom_widget_icon">B</span><span id="jivo_custom_widget_text">'+jivo_custom_widget_settings.custom_widget_offline_text+'</span></div>';
    $('body').append($html);
    
    $('#jivo_custom_widget').click(function(){
        jivo_api.open();
    });
    
    if (jivo_custom_widget_settings.custom_widget_pos == 2){
        $('#jivo_custom_widget').addClass('jivo_widget_right');
    }    
    
    if (jivo_config.chat_mode == "online") {        
        $('#jivo_custom_widget_text').html(jivo_custom_widget_settings.custom_widget_online_text);
        $('#jivo_custom_widget_icon').html('A');
    }
    
    $('#jivo_custom_widget').fadeIn();    
    $('#jivo_custom_widget').css({'background-color':jivo_custom_widget_settings.custom_widget_bg_color,'color':jivo_custom_widget_settings.custom_widget_font_color});
}

/*
 Коллбек-функции jivo_onOpen и jivo_onClose вызываеются всегда,
 когда окно чата JivoSite разворачивается или сворвачивается
 пользователем, либо по правилу активного приглашения.
 */
function jivo_onOpen() {
    // Если чат развернут - скрываем ярлык
    $('#jivo_custom_widget').fadeOut();
}
function jivo_onClose() {
    // Если чат свернут - показываем ярлык
    $('#jivo_custom_widget').fadeIn();
}