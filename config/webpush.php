<?php

return [
    'vapid' => [
        'subject' => env('VAPID_SUBJECT', 'mailto:admin@smk.sch.id'),
        'public_key' => env('VAPID_PUBLIC_KEY', 'BNslcMrJxbaVRrQDQ1EwdW9jp_4mbCDLnsDiGaf9Y-lomBRSqyo7NfrbbJQfMYbApsvVzi14OZtogYlkNyYje1I'),
        'private_key' => env('VAPID_PRIVATE_KEY', 'Phodmm-uTVlVjULgdyr0YbgMKqaQsEcIvBTD5Kvedzo'),
    ],
];
