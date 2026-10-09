<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::routes(['middleware' => ['auth:sanctum_admin']]);

require __DIR__.'/dev-tools/web.php';
