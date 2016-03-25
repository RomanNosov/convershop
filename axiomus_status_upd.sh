#!/bin/bash
curl http://convershop.ru/data/axiomusCheckout/?type=updateStatusPack > /srv/convershop/web/axiomus_status.log
curl http://convershop.ru/data/axiomusCheckout/?type=updateRegions >> /srv/convershop/web/axiomus_status.log
php /srv/convershop/web/cli.php shop gdeposylkaUpdate >> /srv/convershop/web/axiomus_status.log
