<?php

class mailerShopBackend_customers_listHandler extends waEventHandler
{
    public function execute(&$params)
    {
        if (isset($params['hash'])) {
            $hash = $params['hash'];
            $hash_ar = explode('/', $hash, 2);
            if (!empty($hash_ar[1])) {
                $hash_ar[1] = str_replace('/', 'ESCAPED_SLASH', $hash_ar[1]);
            }
            $hash = implode('/', $hash_ar);
            $col_hash = '';
            if (strpos($hash, 'search/') === 0) {
                $col_hash = str_replace('search/', 'shop_customers/', $hash);
            } else if (preg_match('/^([a-z_0-9]*)\//', $hash, $match)) {
                $col_hash = str_replace($match[1] . '/', "shop_customers/{$match[1]}=", $hash);
            } else {
                $col_hash = 'shop_customers/' . $hash;
            }

            if ($col_hash) {
                $col = new waContactsCollection(str_replace('ESCAPED_SLASH', '\\/', $col_hash));
                $col->prepare(true);
                $title = $col->getTitle();

                $html_templ = <<<HTML
<script>
    function :handler() {
        var title = ':title';
        var hash = ':hash';
        var campaign = {
            id: '',
            data: {
                subject: title,
                body: ''
            }
        };
        function logError(r) {
            $('#:loading_link').hide();
            if (console && console.error) {
                console.error(['Error occurs', r]);
            }
        };
        $('#:loading_link').show();
        $.post(':backend_url?module=campaigns&action=save', campaign, function(r) {
            if (r.status = 'ok' && parseInt(r.data, 10)) {
                var campaign_id = parseInt(r.data, 10);
                $.post(':backend_url?module=campaigns&action=recipients', {
                    campaign_id: campaign_id,
                    add_values:['/' + hash]
                }, function(r) {
                    location.href = ':backend_url#/campaigns/recipients/' + campaign_id + '/';
                }).error(function(r) {
                    logError(r);
                });
            } else {
                logError(r);
            }
        }, 'json').error(function(r) {
            logError(r);
        });
    };
</script>
<input type="button" onclick=":handler();" value=":buton_value"> <i class="icon16 loading" id=":loading_link" style="display:none;"></i>
HTML;
                $replace = array(
                    ':handler' => uniqid('sendToMailer'),
                    ':hash' => str_replace('ESCAPED_SLASH', '\\\\\\/', $col_hash),
                    ':title' => $title,
                    ':backend_url' => wa()->getRootUrl(true).wa()->getConfig()->getBackendUrl()."/mailer/",
                    ':buton_value' => _w('Send newsletter in Mailer'),
                    ':loading_link' => uniqid('m-shop-backend-customers-list-loading')
                );
                return array(
                    'top_li' => str_replace(array_keys($replace), array_values($replace), $html_templ)
                );

            }
        }

        return null;

    }

}