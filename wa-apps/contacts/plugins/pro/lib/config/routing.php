<?php

return array(
    'data/regions/' => 'frontend/regions/',
    'signup/' => 'frontend/signup',
    'getsignupformjs/<form_id:\d+>/<no_js:\d+>/' => 'frontend/getSignupFormJs',
    'getsignupformhtml/<form_id:\d+>/<absolute>/<css>/<iframe>/' => 'frontend/getSignupFormHtml',
    'confirmemail/<hash>/' => 'frontend/confirmEmail',
);