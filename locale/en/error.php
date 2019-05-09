<?php return [
    'code-400' => 'The address you passed on ({uri}) is incorrect.',
    'code-403' => 'Access to this part of the site ({uri}) is closed to you.',
    'code-404' => 'No results found for {uri}.',
    'code-500' => join("\r\n", [
        'Sorry, the site is temporarily unavailable.',
        'Our programmers again broke something: (',
        'For every 5 minutes of failure - we will fire one programmer.',
        'Please try again in 5 minutes.'
    ]),
    'code-503' => join("\r\n", [
        'Sorry, the site is temporarily unavailable.',
        'Technical work is being carried out.',
        'Please try again in 5 minutes.'
    ]),
    'suggestions' => join("\r\n", [
        'Possible solutions:',
        '&mdash; check the spelling of the address',
        '&mdash; use the search (search form above)',
        '&mdash; go to <a href="/">home</a> and use the'
    ]),
    'back' => 'Back to previous page',
    'home' => 'To home page',
    'general' => 'Oops... error...',
    'ex-required' => 'Value[%s:%s=%s] should not be empty'
];